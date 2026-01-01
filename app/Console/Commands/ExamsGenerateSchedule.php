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
    protected $description = 'Generate an MVP exam schedule (greedy) for an exam session.';

    public function handle(): int
    {
        $examSessionId = (int) $this->argument('exam_session_id');

        // 0) Ensure time slots exist
        $timeSlots = TimeSlot::query()
            ->where('exam_session_id', $examSessionId)
            ->orderBy('exam_date')
            ->orderBy('slot_index')
            ->get();

        if ($timeSlots->isEmpty()) {
            $this->error("No time_slots found for exam_session_id={$examSessionId}. Run exams:generate-slots first.");
            return self::FAILURE;
        }

        // Group slots by date for easy "day" iteration
        $slotsByDate = $timeSlots->groupBy('exam_date');

        // 1) Extract exam units + student_count (ONLY those with inscriptions)
        // exam_key = module_id:group_id
        $examUnits = DB::select("
            SELECT
                mg.module_id,
                mg.group_id,
                COUNT(i.id) AS student_count
            FROM module_group mg
            INNER JOIN students s
                ON s.group_id = mg.group_id
            INNER JOIN inscriptions i
                ON i.student_id = s.id
               AND i.module_id = mg.module_id
               AND i.exam_session_id = ?
            GROUP BY mg.module_id, mg.group_id
            HAVING COUNT(i.id) > 0
        ", [$examSessionId]);

        if (empty($examUnits)) {
            $this->warn("No exam units with inscriptions found for exam_session_id={$examSessionId}.");
            return self::SUCCESS;
        }

        // 2) Build conflicts between exam units (share at least 1 student)
        // We'll build conflicts at the exam-unit level using student's group_id.
        $conflictRows = DB::select("
            SELECT
                CONCAT(i1.module_id, ':', s.group_id) AS a,
                CONCAT(i2.module_id, ':', s.group_id) AS b,
                COUNT(*) AS shared_students
            FROM inscriptions i1
            INNER JOIN inscriptions i2
                ON i2.student_id = i1.student_id
               AND i2.exam_session_id = i1.exam_session_id
               AND i2.module_id > i1.module_id
            INNER JOIN students s
                ON s.id = i1.student_id
            WHERE i1.exam_session_id = ?
            GROUP BY a, b
        ", [$examSessionId]);

        // 3) Normalize into adjacency list
        $exams = [];      // exam_key => ['module_id'=>, 'group_id'=>, 'student_count'=>, 'degree'=>]
        $neighbors = [];  // exam_key => [neighbor_key => true]

        foreach ($examUnits as $row) {
            $key = $row->module_id . ':' . $row->group_id;
            $exams[$key] = [
                'module_id' => (int) $row->module_id,
                'group_id' => (int) $row->group_id,
                'student_count' => (int) $row->student_count,
                'degree' => 0,
            ];
            $neighbors[$key] = [];
        }

        foreach ($conflictRows as $row) {
            $a = $row->a;
            $b = $row->b;

            // Only keep edges between exams we actually schedule (those with inscriptions)
            if (!isset($exams[$a], $exams[$b])) {
                continue;
            }

            $neighbors[$a][$b] = true;
            $neighbors[$b][$a] = true;

            $exams[$a]['degree']++;
            $exams[$b]['degree']++;
        }

        // 4) Sort exams: hardest first (degree desc, then student_count desc)
        uasort($exams, function ($x, $y) {
            $c = $y['degree'] <=> $x['degree'];
            return $c !== 0 ? $c : ($y['student_count'] <=> $x['student_count']);
        });

        // 5) Prepare rooms
        $rooms = DB::select("
            SELECT id, capacity
            FROM rooms
            WHERE is_active = true
            ORDER BY capacity DESC
        ");

        $totalSeatCapacityAllRooms = array_sum(array_map(fn($r) => (int) $r->capacity, $rooms));
        if ($totalSeatCapacityAllRooms <= 0) {
            $this->error("No active rooms or total capacity is zero.");
            return self::FAILURE;
        }

        // 6) Clean previous schedule (idempotent MVP)
        DB::transaction(function () use ($examSessionId) {
            ScheduledExamRoom::query()
                ->whereHas('scheduledExam', fn($q) => $q->where('exam_session_id', $examSessionId))
                ->delete();

            ScheduledExam::query()
                ->where('exam_session_id', $examSessionId)
                ->delete();
        });

        // 7) Greedy scheduling:
        // - First assign a date (no conflicts same date).
        // - Then choose one of the 4 slots on that date where rooms can seat everyone.
        $assignedDate = []; // exam_key => exam_date

        // Track per slot remaining rooms for that slot:
        // remainingRooms[time_slot_id] = [room_id => remaining_capacity]
        $remainingRooms = [];

        // Pre-init per slot room availability
        foreach ($timeSlots as $ts) {
            $remainingRooms[$ts->id] = [];
            foreach ($rooms as $room) {
                $remainingRooms[$ts->id][(int) $room->id] = (int) $room->capacity;
            }
        }

        $scheduledExamRows = [];
        $scheduledExamRoomRows = [];

        foreach ($exams as $examKey => $exam) {
            $needed = $exam['student_count'];

            if ($needed > $totalSeatCapacityAllRooms) {
                $this->error("Impossible: exam {$examKey} needs {$needed} seats, but total capacity is {$totalSeatCapacityAllRooms}.");
                return self::FAILURE;
            }

            $placed = false;

            foreach ($slotsByDate as $examDate => $slotsOfDay) {
                // Check day-conflicts: none of the neighbors can already be on same examDate
                $conflictOnDate = false;
                foreach ($neighbors[$examKey] ?? [] as $nb => $_) {
                    if (($assignedDate[$nb] ?? null) === $examDate) {
                        $conflictOnDate = true;
                        break;
                    }
                }
                if ($conflictOnDate) {
                    continue;
                }

                // Try each of the 4 slots of the day and see if we can allocate seats
                foreach ($slotsOfDay as $slot) {
                    $alloc = $this->tryAllocateSeats($remainingRooms[$slot->id], $needed);
                    if ($alloc === null) {
                        continue;
                    }

                    // Success: record assignment
                    $assignedDate[$examKey] = $examDate;
                    $placed = true;

                    // Create ScheduledExam row (defer insert until end)
                    $scheduledExamRows[] = [
                        'exam_session_id' => $examSessionId,
                        'module_id' => $exam['module_id'],
                        'group_id' => $exam['group_id'],
                        'time_slot_id' => $slot->id,
                        'student_count' => $exam['student_count'],
                        'status' => 'draft',
                        'created_at' => now(),
                        'updated_at' => now(),
                        '__exam_key' => $examKey, // temp key for mapping
                    ];

                    // Apply allocation and record ScheduledExamRoom rows (defer mapping after insert)
                    foreach ($alloc as $roomId => $seats) {
                        $remainingRooms[$slot->id][$roomId] -= $seats;

                        $scheduledExamRoomRows[] = [
                            '__exam_key' => $examKey, // temp key for mapping
                            'room_id' => $roomId,
                            'seats_allocated' => $seats,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    break 2; // break slot loop + day loop
                }
            }

            if (!$placed) {
                $this->error("Could not place exam {$examKey} within available days/slots (given current constraints).");
                return self::FAILURE;
            }
        }

        // 8) Persist (bulk)
        // Insert scheduled_exams, retrieve ids by unique key (session, module, group)
        DB::transaction(function () use ($scheduledExamRows, $scheduledExamRoomRows, $examSessionId) {
            $cleanExamRows = array_map(function ($r) {
                $tmp = $r;
                unset($tmp['__exam_key']);
                return $tmp;
            }, $scheduledExamRows);

            ScheduledExam::query()->insert($cleanExamRows);

            $idMap = ScheduledExam::query()
                ->where('exam_session_id', $examSessionId)
                ->get(['id', 'module_id', 'group_id'])
                ->mapWithKeys(fn($e) => [($e->module_id . ':' . $e->group_id) => $e->id])
                ->all();

            $finalRoomRows = [];
            foreach ($scheduledExamRoomRows as $r) {
                $examKey = $r['__exam_key'];
                $finalRoomRows[] = [
                    'scheduled_exam_id' => $idMap[$examKey],
                    'room_id' => $r['room_id'],
                    'seats_allocated' => $r['seats_allocated'],
                    'created_at' => $r['created_at'],
                    'updated_at' => $r['updated_at'],
                ];
            }

            ScheduledExamRoom::query()->insert($finalRoomRows);
        });

        $this->info("Schedule generated for exam_session_id={$examSessionId}.");
        return self::SUCCESS;
    }

    /**
     * Try to allocate "needed" seats using remaining room capacities (splitting allowed).
     * Returns array roomId => seatsAllocated, or null if impossible in this slot.
     */
    private function tryAllocateSeats(array $remainingCapByRoomId, int $needed): ?array
    {
        $allocation = [];
        $left = $needed;

        // Greedy: take from biggest remaining rooms first
        arsort($remainingCapByRoomId);

        foreach ($remainingCapByRoomId as $roomId => $cap) {
            if ($left <= 0) {
                break;
            }
            if ($cap <= 0) {
                continue;
            }

            $take = min($cap, $left);
            if ($take > 0) {
                $allocation[(int) $roomId] = $take;
                $left -= $take;
            }
        }

        return $left === 0 ? $allocation : null;
    }
}
