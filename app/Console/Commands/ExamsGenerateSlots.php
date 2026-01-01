<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ExamSession;
use App\Models\TimeSlot;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class ExamsGenerateSlots extends Command
{
    protected $signature = 'exams:generate-slots {exam_session_id}';
    protected $description = 'Generate 4 fixed time slots per day for an exam session (excluding Fridays).';

    public function handle(): int
    {
        $examSessionId = (int) $this->argument('exam_session_id');

        $session = ExamSession::query()->findOrFail($examSessionId);

        $slotTimes = [
            1 => ['09:40:00', '11:10:00'],
            2 => ['11:20:00', '12:50:00'],
            3 => ['13:00:00', '14:30:00'],
            4 => ['14:40:00', '16:10:00'],
        ];

        DB::transaction(function () use ($session, $slotTimes, $examSessionId) {
            TimeSlot::query()
                ->where('exam_session_id', $examSessionId)
                ->delete();

            $period = CarbonPeriod::create($session->starts_on, $session->ends_on);

            $rows = [];

            foreach ($period as $date) {
                // Exclude only Friday
                if ($date->dayOfWeek === 5) { // 5 = Friday in Carbon
                    continue;
                }

                foreach ($slotTimes as $slotIndex => [$startsAt, $endsAt]) {
                    $rows[] = [
                        'exam_session_id' => $examSessionId,
                        'exam_date' => $date->toDateString(),
                        'slot_index' => $slotIndex,
                        'starts_at' => $startsAt,
                        'ends_at' => $endsAt,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            TimeSlot::query()->insert($rows);
        });

        $this->info("Time slots generated for exam_session_id={$examSessionId} (Fridays excluded).");
        return self::SUCCESS;
    }
}
