<?php

namespace App\Console\Commands;

use App\Models\ScheduledExam;
use App\Models\ScheduledExamRoom;
use App\Models\TimeSlot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExamsGenerateSchedule extends Command
{
    protected $signature = 'exams:generate-schedule {exam_session_id} {--extend-days : Allow extending beyond 15 days} {--enable-merging : Enable group merging for same modules}';
    protected $description = 'Generate optimized exam schedule (Greedy + Cohort Merging + Conflict Graph)';

    // Cache for performance
    private array $roomMetadata = [];
    private array $professorsByDept = [];

    // Tracking arrays
    private array $professorSlotUsage = [];
    private array $professorWorkload = [];
    private array $conflictStatistics = [];

    // Track which exams are in which slot
    private array $examsPerSlot = [];

    // Track which rooms are occupied (by exam key)
    private array $roomOccupiedByExam = [];

    // Merging statistics
    private int $mergedExamCount = 0;
    private int $originalExamCount = 0;

    public function handle(): int
    {
        $startTime = microtime(true);
        $examSessionId = (int) $this->argument('exam_session_id');
        $enableMerging = $this->option('enable-merging');

        $this->info("🚀 Starting schedule generation for exam_session_id={$examSessionId}");
        if ($enableMerging) {
            $this->info("🔀 Group merging ENABLED (Cohort/Massive Batch Strategy)");
        }

        // 1. Load Time Slots
        $timeSlots = TimeSlot::query()
            ->where('exam_session_id', $examSessionId)
            ->orderBy('exam_date')
            ->orderBy('slot_index')
            ->get();

        if ($timeSlots->isEmpty()) {
            $this->error("❌ No time_slots found. Run exams:generate-slots first.");
            return self::FAILURE;
        }

        $slotsByDate = $timeSlots->groupBy('exam_date');
        $this->info("📅 Available: {$slotsByDate->count()} days × 4 slots = " . $timeSlots->count() . " total slots");

        // 2. Load Metadata (Rooms/Profs)
        $this->loadMetadata();

        // 3. Fetch Data & Build Exam Units
        $examUnits = $this->buildExamUnits($examSessionId);
        if (empty($examUnits)) {
            $this->warn("⚠️  No exam units with inscriptions found.");
            return self::SUCCESS;
        }
        $this->originalExamCount = count($examUnits);

        // 4. Merge Logic (Cohort Merging)
        if ($enableMerging) {
            $examUnits = $this->mergeExamUnits($examUnits, $examSessionId);
            $this->info("📊 After merging: " . count($examUnits) . " exams (reduced from {$this->originalExamCount})");
        } else {
            $this->info("📊 Total exams to schedule: " . count($examUnits));
        }

        // 5. Build Conflict Graph
        [$exams, $neighbors] = $this->buildConflictGraph($examSessionId, $examUnits);

        // 6. Sort (Degree Saturation)
        $exams = $this->sortExamsByDifficulty($exams);

        // 7. Validate Capacity
        $totalCapacity = array_sum(array_column($this->roomMetadata, 'capacity'));
        $maxStudents = empty($exams) ? 0 : max(array_column($exams, 'student_count'));

        if ($maxStudents > $totalCapacity) {
            $this->error("❌ Largest exam needs {$maxStudents} seats, total capacity is {$totalCapacity}.");
            return self::FAILURE;
        }

        // 8. Initialize Tracking State
        $this->initializeProfessorTracking();
        foreach ($timeSlots as $slot) {
            $this->examsPerSlot[$slot->id] = [];
            $this->roomOccupiedByExam[$slot->id] = [];
            foreach ($this->roomMetadata as $roomId => $room) {
                $this->roomOccupiedByExam[$slot->id][$roomId] = null;
            }
        }

        // 9. Clean & Prepare
        $this->cleanPreviousSchedule($examSessionId);
        $remainingRooms = $this->initializeRoomAvailability($timeSlots);

        // 10. Run Greedy Schedule
        $result = $this->greedySchedule(
            $exams,
            $neighbors,
            $slotsByDate,
            $timeSlots,
            $remainingRooms,
            $examSessionId
        );

        // 11. Handle Result
        if (!$result['success']) {
            $this->error("❌ " . $result['message']);
            $this->displayConflictStatistics();

            // Log failure
            DB::table('performance_logs')->insert([
                'exam_session_id' => $examSessionId,
                'exam_count' => count($exams),
                'duration_seconds' => round(microtime(true) - $startTime, 2),
                'algorithm' => 'greedy_cohort_merging',
                'success' => false,
                'error_message' => $result['message'],
                'created_at' => now(),
            ]);

            return self::FAILURE;
        }

        // 12. Persist
        $this->persistSchedule(
            $result['scheduled_exams'],
            $result['scheduled_rooms'],
            $result['scheduled_professors'],
            $examSessionId
        );

        $duration = round(microtime(true) - $startTime, 2);
        $this->reportPerformance($duration, $examSessionId, count($exams), $enableMerging);

        return self::SUCCESS;
    }

    // ============================================
    // 🧠 CORE LOGIC: COHORT MERGING
    // ============================================

    private function mergeExamUnits(array $examUnits, int $examSessionId): array
    {
        $this->info("🔀 Running Cohort Merging (Massive Batch Strategy)...");

        // Group by Module (Assumes Module ID is specific to Specialty/Year)
        $examsByModule = [];
        foreach ($examUnits as $exam) {
            $examsByModule[$exam->module_id][] = $exam;
        }

        $mergedExams = [];
        $stats = ['batches' => 0, 'singles' => 0];

        foreach ($examsByModule as $moduleId => $groups) {
            // Sort groups by size DESC
            usort($groups, fn($a, $b) => $b->student_count <=> $a->student_count);

            $batches = [];

            // NOTE: We have removed the 70 capacity limit.
            // We now try to merge ALL groups into one batch unless there is a conflict.
            // The Room Allocator will handle splitting large batches across multiple rooms.
            $MAX_BATCH_SIZE = 5000; // Safety limit, effectively infinite for your use case

            foreach ($groups as $group) {
                $placed = false;

                // Try to fit current group into an existing batch
                foreach ($batches as &$batch) {
                    // Check 1: Overly massive size safety check
                    if ($batch['total_students'] + $group->student_count > $MAX_BATCH_SIZE)
                        continue;

                    // Check 2: Student Overlap (The only hard constraint now)
                    $currentBatchGroupIds = array_map(fn($g) => $g->group_id, $batch['items']);
                    if ($this->checkStudentOverlap($moduleId, [...$currentBatchGroupIds, $group->group_id], $examSessionId)) {
                        continue;
                    }

                    // Fit found!
                    $batch['items'][] = $group;
                    $batch['total_students'] += $group->student_count;
                    $placed = true;
                    break;
                }
                unset($batch);

                // If not placed, start a new batch
                if (!$placed) {
                    $batches[] = [
                        'items' => [$group],
                        'total_students' => (int) $group->student_count
                    ];
                }
            }

            // Convert batches to Exam objects
            foreach ($batches as $batch) {
                $primaryGroup = $batch['items'][0];
                $allGroupIds = array_map(fn($g) => (int) $g->group_id, $batch['items']);

                $mergedExam = (object) [
                    'module_id' => $primaryGroup->module_id,
                    'group_id' => $primaryGroup->group_id, // Key based on first group
                    'group_ids' => $allGroupIds,
                    'department_id' => $primaryGroup->department_id,
                    'student_count' => $batch['total_students'],
                    'is_merged' => count($allGroupIds) > 1,
                ];

                $mergedExams[] = $mergedExam;

                if (count($allGroupIds) > 1)
                    $stats['batches']++;
                else
                    $stats['singles']++;
            }
        }

        $this->info("✅ Merging Complete: {$stats['batches']} cohorts created, {$stats['singles']} single exams.");
        $this->mergedExamCount = $stats['batches'];

        return $mergedExams;
    }

    private function checkStudentOverlap(int $moduleId, array $groupIds, int $examSessionId): bool
    {
        $groupIdsStr = '{' . implode(',', $groupIds) . '}';
        // Using explicit casting for PostgreSQL array compatibility
        $overlap = DB::selectOne("
            SELECT COUNT(DISTINCT s.id) as overlap_count
            FROM students s
            JOIN inscriptions i ON i.student_id = s.id
            WHERE i.module_id = ?
              AND i.exam_session_id = ?
              AND s.group_id = ANY(?::int[])
            GROUP BY s.id
            HAVING COUNT(DISTINCT s.group_id) > 1
        ", [$moduleId, $examSessionId, $groupIdsStr]);

        return ($overlap->overlap_count ?? 0) > 0;
    }

    // ============================================
    // 🧠 CORE LOGIC: CONFLICT GRAPH WITH LOOKUP
    // ============================================

    private function buildConflictGraph(int $examSessionId, array $examUnits): array
    {
        // 1. Initialize Exams and build the Lookup Map
        $exams = [];
        $neighbors = [];
        $groupToExamKeyMap = []; // Map: module_id:group_id -> exam_key

        foreach ($examUnits as $row) {
            // Key is always based on the Primary Group ID
            $key = $row->module_id . ':' . $row->group_id;

            $exams[$key] = [
                'module_id' => (int) $row->module_id,
                'group_id' => (int) $row->group_id,
                'group_ids' => $row->group_ids ?? [(int) $row->group_id],
                'is_merged' => $row->is_merged ?? false,
                'department_id' => (int) $row->department_id,
                'student_count' => (int) $row->student_count,
                'degree' => 0,
            ];
            $neighbors[$key] = [];

            // CRITICAL: Map ALL groups in this exam to this key
            foreach ($exams[$key]['group_ids'] as $gId) {
                $lookupKey = $row->module_id . ':' . $gId;
                $groupToExamKeyMap[$lookupKey] = $key;
            }
        }

        // 2. Fetch raw conflicts (Atomic level: Module A Group 1 vs Module B Group 2)
        // Note: Using text casting for PostgreSQL compatibility
        $conflictRows = DB::select("
            SELECT 
                i1.module_id::text || ':' || s.group_id::text AS a,
                i2.module_id::text || ':' || s.group_id::text AS b
            FROM inscriptions i1
            INNER JOIN inscriptions i2 
                ON i2.student_id = i1.student_id 
               AND i2.exam_session_id = i1.exam_session_id
               AND i2.module_id > i1.module_id
            INNER JOIN students s ON s.id = i1.student_id
            WHERE i1.exam_session_id = ?
            GROUP BY a, b
        ", [$examSessionId]);

        // 3. Map atomic conflicts to Merged Exam Keys
        foreach ($conflictRows as $row) {
            // Resolve the "Parent Exam" for both sides of the conflict
            $examKeyA = $groupToExamKeyMap[$row->a] ?? null;
            $examKeyB = $groupToExamKeyMap[$row->b] ?? null;

            // Only register if both exist and are different exams
            if ($examKeyA && $examKeyB && $examKeyA !== $examKeyB) {
                if (!isset($neighbors[$examKeyA][$examKeyB])) {
                    $neighbors[$examKeyA][$examKeyB] = true;
                    $neighbors[$examKeyB][$examKeyA] = true;
                    $exams[$examKeyA]['degree']++;
                    $exams[$examKeyB]['degree']++;
                }
            }
        }

        return [$exams, $neighbors];
    }

    // ============================================
    // 🧠 CORE LOGIC: GREEDY SCHEDULER
    // ============================================

    private function greedySchedule(
        array $exams,
        array $neighbors,
        $slotsByDate,
        $timeSlots,
        array &$remainingRooms,
        int $examSessionId
    ): array {
        $assignedDate = [];
        $scheduledExams = [];
        $scheduledRooms = [];
        $scheduledProfessors = [];

        $progressBar = $this->output->createProgressBar(count($exams));
        $progressBar->start();

        foreach ($exams as $examKey => $exam) {
            $needed = $exam['student_count'];
            $deptId = $exam['department_id'];
            $placed = false;

            foreach ($slotsByDate as $examDate => $slotsOfDay) {
                // Check Day Conflict (1 exam per day per student)
                $conflictOnDate = false;
                foreach ($neighbors[$examKey] ?? [] as $nb => $_) {
                    if (($assignedDate[$nb] ?? null) === $examDate) {
                        $conflictOnDate = true;
                        $this->trackConflict($examKey, $examDate, 'student_conflict_on_day');
                        break;
                    }
                }

                if ($conflictOnDate)
                    continue;

                foreach ($slotsOfDay as $slot) {
                    // Check Slot Conflict (Hard collision)
                    $conflictInSlot = false;
                    foreach ($this->examsPerSlot[$slot->id] as $scheduledExamKey => $_) {
                        if (isset($neighbors[$examKey][$scheduledExamKey])) {
                            $conflictInSlot = true;
                            break;
                        }
                    }
                    if ($conflictInSlot)
                        continue;

                    // Allocate Rooms
                    $roomResult = $this->tryAllocateSeatsWithPriority(
                        $remainingRooms[$slot->id],
                        $needed,
                        $deptId,
                        $examKey,
                        $slot->id
                    );

                    if ($roomResult === null)
                        continue;

                    // Allocate Professors
                    $professors = $this->findAvailableProfessors(
                        $deptId,
                        $roomResult['invigilators'],
                        $examDate,
                        $slot->id,
                        $examKey
                    );

                    if ($professors === null) {
                        // Rollback room hold
                        foreach ($roomResult['allocation'] as $roomId => $seats) {
                            $this->roomOccupiedByExam[$slot->id][$roomId] = null;
                        }
                        continue;
                    }

                    // SUCCESS
                    $assignedDate[$examKey] = $examDate;
                    $this->examsPerSlot[$slot->id][$examKey] = $exam;
                    $placed = true;

                    $scheduledExams[] = [
                        'exam_session_id' => $examSessionId,
                        'module_id' => $exam['module_id'],
                        'group_id' => $exam['group_id'],
                        'time_slot_id' => $slot->id,
                        'student_count' => $exam['student_count'],
                        'status' => 'draft',
                        'created_at' => now(),
                        'updated_at' => now(),
                        // Internal meta-data for persistence
                        '__exam_key' => $examKey,
                        '__group_ids' => $exam['group_ids'],
                        '__is_merged' => $exam['is_merged'],
                    ];

                    foreach ($roomResult['allocation'] as $roomId => $seats) {
                        $remainingRooms[$slot->id][$roomId] -= $seats;
                        $scheduledRooms[] = [
                            '__exam_key' => $examKey,
                            'room_id' => $roomId,
                            'seats_allocated' => $seats,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    foreach ($professors as $profId) {
                        $scheduledProfessors[] = [
                            '__exam_key' => $examKey,
                            'professor_id' => $profId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    break 2; // Break slots and days loops
                }
            }

            if (!$placed) {
                $progressBar->finish();
                $this->newLine();
                return [
                    'success' => false,
                    'message' => "Could not place exam {$examKey} (Module {$exam['module_id']}, Size {$exam['student_count']})"
                ];
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        return [
            'success' => true,
            'scheduled_exams' => $scheduledExams,
            'scheduled_rooms' => $scheduledRooms,
            'scheduled_professors' => $scheduledProfessors,
        ];
    }

    // ============================================
    // 💾 PERSISTENCE LOGIC
    // ============================================

    private function persistSchedule(array $scheduledExams, array $scheduledRooms, array $scheduledProfessors, int $examSessionId): void
    {
        DB::transaction(function () use ($scheduledExams, $scheduledRooms, $scheduledProfessors, $examSessionId) {

            $examRecordsToInsert = [];
            $examKeyMapping = []; // Maps original exam_key to array of DB record keys

            foreach ($scheduledExams as $scheduledExam) {
                $examKey = $scheduledExam['__exam_key'];
                $groupIds = $scheduledExam['__group_ids'] ?? [$scheduledExam['group_id']];

                // If merged, create separate records for each group sharing the slot
                foreach ($groupIds as $groupId) {
                    $recordKey = $scheduledExam['module_id'] . ':' . $groupId;

                    $examRecordsToInsert[] = [
                        'exam_session_id' => $scheduledExam['exam_session_id'],
                        'module_id' => $scheduledExam['module_id'],
                        'group_id' => $groupId,
                        'time_slot_id' => $scheduledExam['time_slot_id'],
                        'student_count' => $scheduledExam['student_count'],
                        'status' => $scheduledExam['status'],
                        'created_at' => $scheduledExam['created_at'],
                        'updated_at' => $scheduledExam['updated_at'],
                        '__record_key' => $recordKey,
                    ];

                    if (!isset($examKeyMapping[$examKey])) {
                        $examKeyMapping[$examKey] = [];
                    }
                    $examKeyMapping[$examKey][] = $recordKey;
                }
            }

            // Insert Exams
            $cleanExamRows = array_map(function ($r) {
                unset($r['__record_key']);
                return $r;
            }, $examRecordsToInsert);
            ScheduledExam::insert($cleanExamRows);

            // Fetch IDs for relation mapping
            $idMap = ScheduledExam::query()
                ->where('exam_session_id', $examSessionId)
                ->get(['id', 'module_id', 'group_id'])
                ->mapWithKeys(fn($e) => [($e->module_id . ':' . $e->group_id) => $e->id])
                ->all();

            // Insert Rooms (Linked to ALL group records for that exam)
            $finalRoomRows = [];
            foreach ($scheduledRooms as $r) {
                $originalExamKey = $r['__exam_key'];
                $recordKeys = $examKeyMapping[$originalExamKey] ?? [];

                foreach ($recordKeys as $recordKey) {
                    if (isset($idMap[$recordKey])) {
                        $finalRoomRows[] = [
                            'scheduled_exam_id' => $idMap[$recordKey],
                            'room_id' => $r['room_id'],
                            'seats_allocated' => $r['seats_allocated'], // Shared allocation
                            'created_at' => $r['created_at'],
                            'updated_at' => $r['updated_at'],
                        ];
                    }
                }
            }
            ScheduledExamRoom::insert($finalRoomRows);

            // Insert Professors
            $finalProfRows = [];
            foreach ($scheduledProfessors as $p) {
                $originalExamKey = $p['__exam_key'];
                $recordKeys = $examKeyMapping[$originalExamKey] ?? [];

                foreach ($recordKeys as $recordKey) {
                    if (isset($idMap[$recordKey])) {
                        $finalProfRows[] = [
                            'scheduled_exam_id' => $idMap[$recordKey],
                            'professor_id' => $p['professor_id'],
                            'created_at' => $p['created_at'],
                            'updated_at' => $p['updated_at'],
                        ];
                    }
                }
            }
            DB::table('scheduled_exam_professors')->insert($finalProfRows);
        });

        $this->info("✅ Schedule persisted");
    }

    // ============================================
    // 🛠️ HELPER METHODS
    // ============================================

    private function loadMetadata(): void
    {
        $rooms = DB::table('rooms')
            ->select('id', 'capacity', 'department_id', 'name', 'type')
            ->where('is_active', true)
            ->get();

        foreach ($rooms as $r) {
            $this->roomMetadata[(int) $r->id] = [
                'id' => (int) $r->id,
                'capacity' => (int) $r->capacity,
                'department_id' => $r->department_id ? (int) $r->department_id : null,
                'name' => $r->name,
                'type' => $r->type,
            ];
        }

        $profs = DB::table('professors')
            ->select('id', 'department_id')
            ->where('status', 'active')
            ->get();

        foreach ($profs as $prof) {
            $deptId = (int) $prof->department_id;
            if (!isset($this->professorsByDept[$deptId])) {
                $this->professorsByDept[$deptId] = [];
            }
            $this->professorsByDept[$deptId][] = (int) $prof->id;
        }

        $this->info("✅ Loaded: " . count($this->roomMetadata) . " rooms, " . count($profs) . " professors");
    }

    private function buildExamUnits(int $examSessionId): array
    {
        return DB::select("
            SELECT
                mg.module_id,
                mg.group_id,
                m.department_id,
                COUNT(DISTINCT i.id) AS student_count
            FROM module_group mg
            INNER JOIN modules m ON m.id = mg.module_id
            INNER JOIN students s ON s.group_id = mg.group_id
            INNER JOIN inscriptions i 
                ON i.student_id = s.id
                AND i.module_id = mg.module_id
                AND i.exam_session_id = ?
            GROUP BY mg.module_id, mg.group_id, m.department_id
            HAVING COUNT(DISTINCT i.id) > 0
        ", [$examSessionId]);
    }

    private function sortExamsByDifficulty(array $exams): array
    {
        uasort($exams, function ($x, $y) {
            // Primary: Degree (Saturation Degree)
            $c = $y['degree'] <=> $x['degree'];
            if ($c !== 0)
                return $c;

            // Secondary: Size (Largest First)
            return $y['student_count'] <=> $x['student_count'];
        });

        return $exams;
    }

    private function initializeProfessorTracking(): void
    {
        foreach ($this->professorsByDept as $deptProfs) {
            foreach ($deptProfs as $profId) {
                $this->professorWorkload[$profId] = 0;
                $this->professorSlotUsage[$profId] = [];
            }
        }
    }

    private function initializeRoomAvailability($timeSlots): array
    {
        $remainingRooms = [];
        foreach ($timeSlots as $ts) {
            $remainingRooms[$ts->id] = [];
            foreach ($this->roomMetadata as $roomId => $room) {
                $remainingRooms[$ts->id][$roomId] = $room['capacity'];
            }
        }
        return $remainingRooms;
    }

    private function cleanPreviousSchedule(int $examSessionId): void
    {
        DB::transaction(function () use ($examSessionId) {
            DB::table('scheduled_exam_professors')
                ->whereIn('scheduled_exam_id', function ($q) use ($examSessionId) {
                    $q->select('id')->from('scheduled_exams')->where('exam_session_id', $examSessionId);
                })->delete();

            ScheduledExamRoom::query()
                ->whereHas('scheduledExam', fn($q) => $q->where('exam_session_id', $examSessionId))
                ->delete();

            ScheduledExam::query()
                ->where('exam_session_id', $examSessionId)
                ->delete();
        });
    }

    private function tryAllocateSeatsWithPriority(
        array $remainingCapByRoomId,
        int $needed,
        int $deptId,
        string $examKey,
        int $slotId
    ): ?array {
        $tiers = ['own' => [], 'shared' => [], 'overflow' => []];

        foreach ($remainingCapByRoomId as $roomId => $remCap) {
            if ($remCap <= 0 || !isset($this->roomMetadata[$roomId]))
                continue;

            // Check if room is strictly occupied by another exam in this slot
            if ($this->roomOccupiedByExam[$slotId][$roomId] !== null)
                continue;

            $room = $this->roomMetadata[$roomId];
            $capacity = $room['capacity'];

            if ($room['department_id'] === $deptId) {
                $tiers['own'][$roomId] = $capacity;
            } elseif ($room['department_id'] === null) {
                $tiers['shared'][$roomId] = $capacity;
            } else {
                $tiers['overflow'][$roomId] = $capacity;
            }
        }

        $allocation = [];
        $left = $needed;

        foreach (['own', 'shared', 'overflow'] as $tier) {
            if ($left <= 0)
                break;
            arsort($tiers[$tier]); // Largest rooms first

            foreach ($tiers[$tier] as $roomId => $cap) {
                if ($left <= 0)
                    break;

                // Take full capacity or just what's needed
                // Note: We "burn" the room for this slot. It becomes occupied.
                $take = min($cap, $left);

                if ($take > 0) {
                    $allocation[$roomId] = $take;
                    $left -= $take;
                    $this->roomOccupiedByExam[$slotId][$roomId] = $examKey;
                }
            }
        }

        if ($left > 0) {
            // Rollback
            foreach ($allocation as $roomId => $seats) {
                $this->roomOccupiedByExam[$slotId][$roomId] = null;
            }
            $this->trackConflict($examKey, $slotId, 'insufficient_room_capacity', $needed - $left);
            return null;
        }

        return [
            'allocation' => $allocation,
            'invigilators' => count($allocation),
        ];
    }

    private function findAvailableProfessors(
        int $deptId,
        int $invigilatorsNeeded,
        string $examDate,
        int $slotId,
        string $examKey
    ): ?array {
        $candidates = $this->professorsByDept[$deptId] ?? [];

        if (count($candidates) < $invigilatorsNeeded) {
            $this->trackConflict($examKey, $slotId, 'insufficient_professors', count($candidates));
            return null;
        }

        $slotsToday = TimeSlot::where('exam_date', $examDate)->pluck('id')->all();
        $available = [];

        foreach ($candidates as $profId) {
            $todayCount = 0;
            foreach ($slotsToday as $sid) {
                $todayCount += $this->professorSlotUsage[$profId][$sid] ?? 0;
            }

            if ($todayCount >= 3)
                continue; // Max 3 per day
            if (isset($this->professorSlotUsage[$profId][$slotId]))
                continue; // Already busy in slot

            $available[] = $profId;
        }

        if (count($available) < $invigilatorsNeeded) {
            $this->trackConflict($examKey, $slotId, 'professors_maxed_out', count($available));
            return null;
        }

        // Prioritize professors with lower workload
        usort($available, fn($a, $b) => $this->professorWorkload[$a] <=> $this->professorWorkload[$b]);

        $selected = array_slice($available, 0, $invigilatorsNeeded);

        foreach ($selected as $profId) {
            $this->professorSlotUsage[$profId][$slotId] = 1;
            $this->professorWorkload[$profId]++;
        }

        return $selected;
    }

    private function trackConflict(string $examKey, $context, string $reason, int $detail = 0): void
    {
        if (!isset($this->conflictStatistics[$reason])) {
            $this->conflictStatistics[$reason] = ['count' => 0, 'examples' => []];
        }

        $this->conflictStatistics[$reason]['count']++;
        if (count($this->conflictStatistics[$reason]['examples']) < 3) {
            $this->conflictStatistics[$reason]['examples'][] = $examKey;
        }
    }

    private function displayConflictStatistics(): void
    {
        if (empty($this->conflictStatistics))
            return;

        $this->newLine();
        $this->warn("📊 Why scheduling failed:");
        foreach ($this->conflictStatistics as $reason => $data) {
            $examples = implode(', ', array_slice($data['examples'], 0, 2));
            $this->line("  • {$reason}: {$data['count']}x (e.g. {$examples})");
        }
    }

    private function reportPerformance(float $duration, int $examSessionId, int $examCount, bool $mergingEnabled): void
    {
        $this->newLine();
        $this->info("⏱️  {$duration}s for {$examCount} exams");

        if ($mergingEnabled && $this->mergedExamCount > 0) {
            $reductionPct = round(($this->originalExamCount - $examCount) / $this->originalExamCount * 100, 1);
            $this->info("🔀 Merging reduced exams by {$reductionPct}% ({$this->originalExamCount} → {$examCount})");
        }

        DB::table('performance_logs')->insert([
            'exam_session_id' => $examSessionId,
            'exam_count' => $examCount,
            'duration_seconds' => $duration,
            'algorithm' => 'greedy_cohort_merging',
            'success' => true,
            'created_at' => now(),
        ]);

        $this->displayWorkloadStats();
    }

    private function displayWorkloadStats(): void
    {
        $workloads = array_values($this->professorWorkload);
        if (empty($workloads))
            return;

        $min = min($workloads);
        $max = max($workloads);
        $avg = round(array_sum($workloads) / count($workloads), 2);

        $this->newLine();
        $this->info("👥 Professor Workload: min={$min}, max={$max}, avg={$avg}");
    }
}
