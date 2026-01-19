<?php

namespace App\Console\Commands;

use App\Models\ScheduledExam;
use App\Models\ScheduledExamRoom;
use App\Models\TimeSlot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExamsGenerateSchedule extends Command
{
    protected $signature = 'exams:generate-schedule {exam_session_id}';
    protected $description = 'Generate optimized exam schedule (Greedy + CSP)';

    private array $roomMetadata = [];
    private array $professorsByDept = [];
    private array $professorSlotUsage = [];
    private array $professorWorkload = [];
    private array $conflictStatistics = [];
    private array $examsPerSlot = [];
    private array $roomUsagePerSlot = [];

    public function handle(): int
    {
        $startTime = microtime(true);
        $examSessionId = (int) $this->argument('exam_session_id');

        $this->line("");
        $this->info("EXAM SCHEDULE GENERATION");
        $this->line("Session ID: {$examSessionId}");
        $this->line(str_repeat("=", 60));

        $timeSlots = TimeSlot::query()
            ->where('exam_session_id', $examSessionId)
            ->orderBy('exam_date')
            ->orderBy('slot_index')
            ->get();

        if ($timeSlots->isEmpty()) {
            $this->error("[ERROR] No time slots found");
            $this->line("ACTION: Run 'Generate Time Slots' first");
            return self::FAILURE;
        }

        $slotsByDate = $timeSlots->groupBy('exam_date');
        $totalSlots = $timeSlots->count();
        $this->line("");
        $this->line("Available time slots: {$slotsByDate->count()} days x 4 slots = {$totalSlots} total");

        $this->loadMetadata();
        $examUnits = $this->buildExamUnits($examSessionId);

        if (empty($examUnits)) {
            $this->warn("[WARNING] No exams found to schedule");
            return self::SUCCESS;
        }

        $examCount = count($examUnits);
        $this->line("Total exams to schedule: {$examCount}");
        $this->line("");

        [$exams, $neighbors] = $this->buildConflictGraph($examSessionId, $examUnits);
        $exams = $this->sortExamsByDifficulty($exams);

        $this->initializeProfessorTracking();

        foreach ($timeSlots as $slot) {
            $this->examsPerSlot[$slot->id] = [];
            $this->roomUsagePerSlot[$slot->id] = [];
        }

        $this->cleanPreviousSchedule($examSessionId);

        $this->line("Processing schedule optimization...");
        $result = $this->greedySchedule(
            $exams,
            $neighbors,
            $slotsByDate,
            $timeSlots,
            $examSessionId
        );

        if (!$result['success']) {
            $this->line("");
            $this->error("[FAILED] " . $result['message']);
            $this->displayConflictStatistics();
            return self::FAILURE;
        }

        $this->persistSchedule(
            $result['scheduled_exams'],
            $result['scheduled_rooms'],
            $result['scheduled_professors'],
            $examSessionId
        );

        $duration = round(microtime(true) - $startTime, 2);
        $this->reportPerformance($duration, $examSessionId, count($exams));

        return self::SUCCESS;
    }

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

        $roomCount = count($this->roomMetadata);
        $profCount = count($profs);
        $this->line("Resources loaded: {$roomCount} rooms, {$profCount} professors");
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

    private function buildConflictGraph(int $examSessionId, array $examUnits): array
    {
        // FIXED: MySQL-compatible syntax (replaced ::text and || with CONCAT)
        $conflictRows = DB::select("
            SELECT
                CONCAT(i1.module_id, ':', s.group_id) AS a,
                CONCAT(i2.module_id, ':', s.group_id) AS b,
                COUNT(DISTINCT i1.student_id) AS shared_students
            FROM inscriptions i1
            INNER JOIN inscriptions i2
                ON i2.student_id = i1.student_id
               AND i2.exam_session_id = i1.exam_session_id
               AND i2.module_id > i1.module_id
            INNER JOIN students s ON s.id = i1.student_id
            WHERE i1.exam_session_id = ?
            GROUP BY a, b
            HAVING COUNT(DISTINCT i1.student_id) > 0
        ", [$examSessionId]);

        $exams = [];
        $neighbors = [];

        foreach ($examUnits as $row) {
            $key = $row->module_id . ':' . $row->group_id;
            $exams[$key] = [
                'module_id' => (int) $row->module_id,
                'group_id' => (int) $row->group_id,
                'department_id' => (int) $row->department_id,
                'student_count' => (int) $row->student_count,
                'degree' => 0,
            ];
            $neighbors[$key] = [];
        }

        foreach ($conflictRows as $row) {
            $a = $row->a;
            $b = $row->b;

            if (!isset($exams[$a], $exams[$b])) {
                continue;
            }

            $neighbors[$a][$b] = true;
            $neighbors[$b][$a] = true;
            $exams[$a]['degree']++;
            $exams[$b]['degree']++;
        }

        return [$exams, $neighbors];
    }

    private function sortExamsByDifficulty(array $exams): array
    {
        uasort($exams, function ($x, $y) {
            $c = $y['degree'] <=> $x['degree'];
            return $c !== 0 ? $c : ($y['student_count'] <=> $x['student_count']);
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

    private function cleanPreviousSchedule(int $examSessionId): void
    {
        DB::transaction(function () use ($examSessionId) {
            DB::table('scheduled_exam_professors')
                ->whereIn('scheduled_exam_id', function ($q) use ($examSessionId) {
                    $q->select('id')
                        ->from('scheduled_exams')
                        ->where('exam_session_id', $examSessionId);
                })
                ->delete();

            ScheduledExamRoom::query()
                ->whereHas('scheduledExam', fn($q) => $q->where('exam_session_id', $examSessionId))
                ->delete();

            ScheduledExam::query()
                ->where('exam_session_id', $examSessionId)
                ->delete();
        });
    }

    private function greedySchedule(
        array $exams,
        array $neighbors,
        $slotsByDate,
        $timeSlots,
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
                $conflictOnDate = false;
                foreach ($neighbors[$examKey] ?? [] as $nb => $_) {
                    if (($assignedDate[$nb] ?? null) === $examDate) {
                        $conflictOnDate = true;
                        $this->trackConflict($examKey, $examDate, 'Student has conflicting exam on same day');
                        break;
                    }
                }

                if ($conflictOnDate) {
                    continue;
                }

                foreach ($slotsOfDay as $slot) {
                    $conflictInSlot = false;
                    foreach ($this->examsPerSlot[$slot->id] as $scheduledExamKey => $_) {
                        if (isset($neighbors[$examKey][$scheduledExamKey])) {
                            $conflictInSlot = true;
                            break;
                        }
                    }

                    if ($conflictInSlot) {
                        continue;
                    }

                    $roomResult = $this->tryAllocateRooms($needed, $deptId, $slot->id, $examKey);

                    if ($roomResult === null) {
                        continue;
                    }

                    $professors = $this->findAvailableProfessors(
                        $deptId,
                        $roomResult['invigilators'],
                        $examDate,
                        $slot->id,
                        $examKey
                    );

                    if ($professors === null) {
                        foreach ($roomResult['allocation'] as $roomId => $seats) {
                            $this->roomUsagePerSlot[$slot->id][$roomId] -= $seats;
                        }
                        continue;
                    }

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
                        '__exam_key' => $examKey,
                    ];

                    foreach ($roomResult['allocation'] as $roomId => $seats) {
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

                    break 2;
                }
            }

            if (!$placed) {
                $progressBar->finish();
                $this->newLine();
                return [
                    'success' => false,
                    'message' => "Unable to schedule: Module {$exam['module_id']}, Group {$exam['group_id']} ({$exam['student_count']} students)"
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

    private function tryAllocateRooms(int $needed, int $deptId, int $slotId, string $examKey): ?array
    {
        $tiers = ['own' => [], 'shared' => [], 'overflow' => []];

        foreach ($this->roomMetadata as $roomId => $room) {
            $used = $this->roomUsagePerSlot[$slotId][$roomId] ?? 0;
            $available = $room['capacity'] - $used;

            if ($available <= 0) {
                continue;
            }

            if ($room['department_id'] === $deptId) {
                $tiers['own'][$roomId] = $available;
            } elseif ($room['department_id'] === null) {
                $tiers['shared'][$roomId] = $available;
            } else {
                $tiers['overflow'][$roomId] = $available;
            }
        }

        $allocation = [];
        $left = $needed;

        foreach (['own', 'shared', 'overflow'] as $tier) {
            if ($left <= 0)
                break;

            arsort($tiers[$tier]);

            foreach ($tiers[$tier] as $roomId => $available) {
                if ($left <= 0)
                    break;

                $take = min($available, $left);
                $allocation[$roomId] = $take;
                $left -= $take;

                if (!isset($this->roomUsagePerSlot[$slotId][$roomId])) {
                    $this->roomUsagePerSlot[$slotId][$roomId] = 0;
                }
                $this->roomUsagePerSlot[$slotId][$roomId] += $take;
            }
        }

        if ($left > 0) {
            foreach ($allocation as $roomId => $seats) {
                $this->roomUsagePerSlot[$slotId][$roomId] -= $seats;
            }

            $this->trackConflict($examKey, $slotId, 'Insufficient room capacity available');
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
            $this->trackConflict($examKey, $slotId, 'Insufficient professors in department');
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
                continue;
            if (isset($this->professorSlotUsage[$profId][$slotId]))
                continue;

            $available[] = $profId;
        }

        if (count($available) < $invigilatorsNeeded) {
            $this->trackConflict($examKey, $slotId, 'Professors at maximum daily workload');
            return null;
        }

        usort($available, fn($a, $b) => $this->professorWorkload[$a] <=> $this->professorWorkload[$b]);

        $selected = array_slice($available, 0, $invigilatorsNeeded);

        foreach ($selected as $profId) {
            $this->professorSlotUsage[$profId][$slotId] = 1;
            $this->professorWorkload[$profId]++;
        }

        return $selected;
    }

    private function trackConflict(string $examKey, $context, string $reason): void
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

        $this->line("");
        $this->line("SCHEDULING FAILURE ANALYSIS");
        $this->line(str_repeat("-", 60));

        foreach ($this->conflictStatistics as $reason => $data) {
            $examples = implode(', ', array_slice($data['examples'], 0, 2));
            $this->line("- {$reason}: {$data['count']} occurrences");
            $this->line("  Examples: {$examples}");
        }

        $this->line("");
        $this->line("RECOMMENDATIONS:");
        $this->line("- Increase exam session duration (add more days)");
        $this->line("- Add additional rooms to capacity");
        $this->line("- Assign more professors to departments");
    }

    private function persistSchedule(array $scheduledExams, array $scheduledRooms, array $scheduledProfessors, int $examSessionId): void
    {
        DB::transaction(function () use ($scheduledExams, $scheduledRooms, $scheduledProfessors, $examSessionId) {
            $cleanExamRows = array_map(function ($r) {
                unset($r['__exam_key']);
                return $r;
            }, $scheduledExams);

            ScheduledExam::insert($cleanExamRows);

            $idMap = ScheduledExam::query()
                ->where('exam_session_id', $examSessionId)
                ->get(['id', 'module_id', 'group_id'])
                ->mapWithKeys(fn($e) => [($e->module_id . ':' . $e->group_id) => $e->id])
                ->all();

            $finalRoomRows = [];
            foreach ($scheduledRooms as $r) {
                $finalRoomRows[] = [
                    'scheduled_exam_id' => $idMap[$r['__exam_key']],
                    'room_id' => $r['room_id'],
                    'seats_allocated' => $r['seats_allocated'],
                    'created_at' => $r['created_at'],
                    'updated_at' => $r['updated_at'],
                ];
            }
            ScheduledExamRoom::insert($finalRoomRows);

            $finalProfRows = [];
            foreach ($scheduledProfessors as $p) {
                $finalProfRows[] = [
                    'scheduled_exam_id' => $idMap[$p['__exam_key']],
                    'professor_id' => $p['professor_id'],
                    'created_at' => $p['created_at'],
                    'updated_at' => $p['updated_at'],
                ];
            }

            DB::table('scheduled_exam_professors')->insert($finalProfRows);
        });

        $this->line("");
        $this->info("[SUCCESS] Schedule saved to database");
    }

    private function reportPerformance(float $duration, int $examSessionId, int $examCount): void
    {
        $this->line("");
        $this->line(str_repeat("=", 60));
        $this->info("GENERATION COMPLETE");
        $this->line(str_repeat("=", 60));
        $this->line("");
        $this->line("Processing time: {$duration} seconds");
        $this->line("Exams scheduled: {$examCount}");

        if ($duration > 45) {
            $this->warn("[WARNING] Processing time exceeded 45 second target");
            $this->line("Consider optimizing for better performance");
        } else {
            $this->info("[SUCCESS] Processing time within 45 second target");
        }

        DB::table('performance_logs')->insert([
            'exam_session_id' => $examSessionId,
            'exam_count' => $examCount,
            'duration_seconds' => $duration,
            'algorithm' => 'greedy_saturation_degree',
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
        $variance = $max - $min;

        $this->line("");
        $this->line("PROFESSOR WORKLOAD DISTRIBUTION:");
        $this->line("  Minimum: {$min} surveillances");
        $this->line("  Maximum: {$max} surveillances");
        $this->line("  Average: {$avg} surveillances");
        $this->line("  Variance: {$variance}");
        $this->line("");
    }
}
