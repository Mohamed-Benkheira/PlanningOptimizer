<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExamsTestComplete extends Command
{
    protected $signature = 'exams:test {exam_session_id}';
    protected $description = 'Complete test suite for exam scheduling system';

    private int $examSessionId;
    private array $results = [];

    public function handle(): int
    {
        $this->examSessionId = (int) $this->argument('exam_session_id');

        $this->results = [
            'passed' => 0,
            'failed' => 0,
            'warnings' => 0,
            'critical' => 0
        ];

        $this->line("");
        $this->line("EXAM SCHEDULE VALIDATION REPORT");
        $this->line("Session ID: {$this->examSessionId}");
        $this->line(str_repeat("=", 60));
        $this->line("");

        // Run all tests
        $this->testDataIntegrity();
        $this->testHardConstraints();
        $this->testSoftConstraints();
        $this->testPerformance();
        $this->testRoomUsage();
        $this->testProfessorWorkload();
        $this->testStudentDistribution();

        // Final report
        $this->displayFinalReport();

        return $this->results['failed'] === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function testDataIntegrity(): void
    {
        $this->line("");
        $this->info("1. DATA INTEGRITY CHECK");
        $this->line(str_repeat("-", 60));

        $checks = [
            'scheduled_exams' => DB::table('scheduled_exams')
                ->where('exam_session_id', $this->examSessionId)
                ->count(),
            'scheduled_rooms' => DB::table('scheduled_exam_rooms')
                ->whereIn('scheduled_exam_id', function ($q) {
                    $q->select('id')->from('scheduled_exams')
                        ->where('exam_session_id', $this->examSessionId);
                })
                ->count(),
            'scheduled_professors' => DB::table('scheduled_exam_professors')
                ->whereIn('scheduled_exam_id', function ($q) {
                    $q->select('id')->from('scheduled_exams')
                        ->where('exam_session_id', $this->examSessionId);
                })
                ->count(),
        ];

        $this->line("Total scheduled exams: {$checks['scheduled_exams']}");
        $this->line("Total room allocations: {$checks['scheduled_rooms']}");
        $this->line("Total professor assignments: {$checks['scheduled_professors']}");
        $this->line("");

        // Check orphans
        $orphanRooms = DB::select("
            SELECT COUNT(*) as cnt
            FROM scheduled_exam_rooms ser
            LEFT JOIN scheduled_exams se ON se.id = ser.scheduled_exam_id
            WHERE se.id IS NULL
        ")[0]->cnt;

        if ($orphanRooms > 0) {
            $this->error("[FAIL] Found {$orphanRooms} orphaned room allocations");
            $this->results['failed']++;
        } else {
            $this->info("[PASS] No orphaned records detected");
            $this->results['passed']++;
        }

        // Check all exams have rooms
        $examsWithoutRooms = DB::select("
            SELECT COUNT(*) as cnt
            FROM scheduled_exams se
            LEFT JOIN scheduled_exam_rooms ser ON ser.scheduled_exam_id = se.id
            WHERE se.exam_session_id = ? AND ser.id IS NULL
        ", [$this->examSessionId])[0]->cnt;

        if ($examsWithoutRooms > 0) {
            $this->error("[FAIL] {$examsWithoutRooms} exams missing room allocation");
            $this->results['failed']++;
        } else {
            $this->info("[PASS] All exams have room allocations");
            $this->results['passed']++;
        }

        // Check all exams have professors
        $examsWithoutProfs = DB::select("
            SELECT COUNT(*) as cnt
            FROM scheduled_exams se
            LEFT JOIN scheduled_exam_professors sep ON sep.scheduled_exam_id = se.id
            WHERE se.exam_session_id = ? AND sep.id IS NULL
        ", [$this->examSessionId])[0]->cnt;

        if ($examsWithoutProfs > 0) {
            $this->error("[FAIL] {$examsWithoutProfs} exams missing professor assignment");
            $this->results['failed']++;
        } else {
            $this->info("[PASS] All exams have professor assignments");
            $this->results['passed']++;
        }
    }

    private function testHardConstraints(): void
    {
        $this->line("");
        $this->info("2. MANDATORY CONSTRAINTS VALIDATION");
        $this->line(str_repeat("-", 60));

        // CONSTRAINT 1: Max 1 exam per student per day
        $studentViolations = DB::select("
            SELECT COUNT(*) as violation_count
            FROM (
                SELECT 
                    s.id,
                    ts.exam_date,
                    COUNT(*) as exams_same_day
                FROM students s
                JOIN inscriptions i ON i.student_id = s.id
                JOIN scheduled_exams se ON se.module_id = i.module_id AND se.group_id = s.group_id
                JOIN time_slots ts ON ts.id = se.time_slot_id
                WHERE i.exam_session_id = ?
                GROUP BY s.id, ts.exam_date
                HAVING COUNT(*) > 1
            ) violations
        ", [$this->examSessionId])[0]->violation_count;

        if ($studentViolations > 0) {
            $this->error("[CRITICAL] Student Scheduling Conflict: {$studentViolations} students scheduled for multiple exams on same day");
            $this->results['failed']++;
            $this->results['critical']++;
        } else {
            $this->info("[PASS] Student Scheduling: Maximum 1 exam per student per day");
            $this->results['passed']++;
        }

        // CONSTRAINT 2: Max 3 exams per professor per day
        $profViolations = DB::select("
            SELECT COUNT(*) as violation_count
            FROM (
                SELECT 
                    p.id,
                    ts.exam_date,
                    COUNT(*) as exams_same_day
                FROM professors p
                JOIN scheduled_exam_professors sep ON sep.professor_id = p.id
                JOIN scheduled_exams se ON se.id = sep.scheduled_exam_id
                JOIN time_slots ts ON ts.id = se.time_slot_id
                WHERE se.exam_session_id = ?
                GROUP BY p.id, ts.exam_date
                HAVING COUNT(*) > 3
            ) violations
        ", [$this->examSessionId])[0]->violation_count;

        if ($profViolations > 0) {
            $this->error("[CRITICAL] Professor Workload Violation: {$profViolations} professors assigned more than 3 exams per day");
            $this->results['failed']++;
            $this->results['critical']++;
        } else {
            $this->info("[PASS] Professor Workload: Maximum 3 exams per professor per day");
            $this->results['passed']++;
        }

        // CONSTRAINT 3: Room capacity not exceeded
        $capacityOverflows = DB::select("
            SELECT COUNT(*) as cnt
            FROM (
                SELECT 
                    r.id,
                    r.code,
                    r.capacity,
                    ts.id as time_slot_id,
                    ts.exam_date,
                    ts.slot_index,
                    SUM(ser.seats_allocated) as total_allocated,
                    COUNT(DISTINCT se.id) as exam_count
                FROM scheduled_exam_rooms ser
                JOIN scheduled_exams se ON se.id = ser.scheduled_exam_id
                JOIN time_slots ts ON ts.id = se.time_slot_id
                JOIN rooms r ON r.id = ser.room_id
                WHERE se.exam_session_id = ?
                GROUP BY r.id, r.code, r.capacity, ts.id, ts.exam_date, ts.slot_index
                HAVING SUM(ser.seats_allocated) > r.capacity
            ) overflow
        ", [$this->examSessionId])[0]->cnt;

        if ($capacityOverflows > 0) {
            $this->error("[CRITICAL] Room Capacity Exceeded: {$capacityOverflows} room/time slot combinations over capacity");
            $this->results['failed']++;
            $this->results['critical']++;

            $details = DB::select("
                SELECT 
                    r.code,
                    r.capacity,
                    ts.exam_date,
                    ts.slot_index,
                    SUM(ser.seats_allocated) as total_allocated
                FROM scheduled_exam_rooms ser
                JOIN scheduled_exams se ON se.id = ser.scheduled_exam_id
                JOIN time_slots ts ON ts.id = se.time_slot_id
                JOIN rooms r ON r.id = ser.room_id
                WHERE se.exam_session_id = ?
                GROUP BY r.id, r.code, r.capacity, ts.id, ts.exam_date, ts.slot_index
                HAVING SUM(ser.seats_allocated) > r.capacity
                LIMIT 3
            ", [$this->examSessionId]);

            $this->line("");
            $this->line("   Examples of capacity violations:");
            foreach ($details as $d) {
                $overflow = $d->total_allocated - $d->capacity;
                $this->line("   - Room {$d->code} on {$d->exam_date} slot {$d->slot_index}: {$d->total_allocated}/{$d->capacity} seats (+{$overflow} overflow)");
            }
        } else {
            $this->info("[PASS] Room Capacity: All rooms within maximum capacity");
            $this->results['passed']++;
        }

        // CONSTRAINT 4: Professor department match
        $deptMismatch = DB::select("
            SELECT COUNT(*) as cnt
            FROM scheduled_exam_professors sep
            JOIN scheduled_exams se ON se.id = sep.scheduled_exam_id
            JOIN modules m ON m.id = se.module_id
            JOIN professors p ON p.id = sep.professor_id
            WHERE se.exam_session_id = ? 
              AND p.department_id != m.department_id
        ", [$this->examSessionId])[0]->cnt;

        if ($deptMismatch > 0) {
            $this->warn("[WARN] Department Alignment: {$deptMismatch} professors assigned outside their department");
            $this->results['warnings']++;
        } else {
            $this->info("[PASS] Department Alignment: All professors match exam department");
            $this->results['passed']++;
        }
    }

    private function testSoftConstraints(): void
    {
        $this->line("");
        $this->info("3. OPTIMIZATION QUALITY METRICS");
        $this->line(str_repeat("-", 60));

        // Department priority for rooms
        $deptPriority = DB::select("
            SELECT 
                COUNT(CASE WHEN r.department_id = m.department_id THEN 1 END) as own_dept,
                COUNT(CASE WHEN r.department_id IS NULL THEN 1 END) as shared,
                COUNT(CASE WHEN r.department_id IS NOT NULL AND r.department_id != m.department_id THEN 1 END) as overflow,
                COUNT(*) as total
            FROM scheduled_exam_rooms ser
            JOIN scheduled_exams se ON se.id = ser.scheduled_exam_id
            JOIN modules m ON m.id = se.module_id
            JOIN rooms r ON r.id = ser.room_id
            WHERE se.exam_session_id = ?
        ", [$this->examSessionId])[0];

        $totalRooms = (int) ($deptPriority->total ?? 0);
        if ($totalRooms === 0) {
            $ownPct = 0.0;
            $overflowPct = 0.0;
        } else {
            $ownPct = round($deptPriority->own_dept / $totalRooms * 100, 1);
            $overflowPct = round($deptPriority->overflow / $totalRooms * 100, 1);
        }

        $this->line("Room Assignment Priority:");
        $this->line("   Department-owned rooms: {$deptPriority->own_dept} ({$ownPct}%)");
        $this->line("   Shared rooms: {$deptPriority->shared}");
        $this->line("   Other department rooms: {$deptPriority->overflow} ({$overflowPct}%)");
        $this->line("");

        if ($ownPct > $overflowPct) {
            $this->info("[PASS] Room Priority: Department rooms prioritized correctly");
            $this->results['passed']++;
        } else {
            $this->warn("[WARN] Room Priority: Could improve department room allocation");
            $this->results['warnings']++;
        }

        // Professor workload balance
        $workloadStats = DB::select("
            SELECT 
                MIN(cnt) as min_load,
                MAX(cnt) as max_load,
                ROUND(AVG(cnt), 2) as avg_load
            FROM (
                SELECT p.id, COUNT(*) as cnt
                FROM professors p
                JOIN scheduled_exam_professors sep ON sep.professor_id = p.id
                JOIN scheduled_exams se ON se.id = sep.scheduled_exam_id
                WHERE se.exam_session_id = ?
                GROUP BY p.id
            ) workloads
        ", [$this->examSessionId])[0];

        $variance = $workloadStats->max_load - $workloadStats->min_load;

        $this->line("Professor Workload Distribution:");
        $this->line("   Minimum surveillances: {$workloadStats->min_load}");
        $this->line("   Maximum surveillances: {$workloadStats->max_load}");
        $this->line("   Average surveillances: {$workloadStats->avg_load}");
        $this->line("   Workload variance: {$variance}");
        $this->line("");

        if ($variance <= 5) {
            $this->info("[PASS] Workload Balance: Well distributed (variance within acceptable range)");
            $this->results['passed']++;
        } else {
            $this->warn("[WARN] Workload Balance: High variance detected (variance: {$variance})");
            $this->results['warnings']++;
        }
    }

    private function testPerformance(): void
    {
        $this->line("");
        $this->info("4. SYSTEM PERFORMANCE");
        $this->line(str_repeat("-", 60));

        $perfLog = DB::table('performance_logs')
            ->where('exam_session_id', $this->examSessionId)
            ->orderByDesc('created_at')
            ->first();

        if (!$perfLog) {
            $this->warn("[WARN] No performance data recorded");
            $this->results['warnings']++;
            return;
        }

        $this->line("Algorithm used: {$perfLog->algorithm}");
        $this->line("Exams processed: {$perfLog->exam_count}");
        $this->line("Processing time: {$perfLog->duration_seconds} seconds");
        $this->line("Status: " . ($perfLog->success ? 'Successful' : 'Failed'));
        $this->line("");

        if ($perfLog->duration_seconds < 45) {
            $this->info("[PASS] Performance Target: Completed under 45 seconds ({$perfLog->duration_seconds}s)");
            $this->results['passed']++;
        } else {
            $this->error("[FAIL] Performance Target: Exceeded 45 second limit ({$perfLog->duration_seconds}s)");
            $this->results['failed']++;
        }
    }

    private function testRoomUsage(): void
    {
        $this->line("");
        $this->info("5. RESOURCE UTILIZATION");
        $this->line(str_repeat("-", 60));

        $roomStats = DB::select("
            SELECT 
                COUNT(DISTINCT r.id) as total_rooms,
                COUNT(DISTINCT CASE WHEN ser.id IS NOT NULL THEN r.id END) as used_rooms,
                ROUND(AVG(CAST(ser.seats_allocated AS NUMERIC) / NULLIF(r.capacity, 0) * 100), 1) as avg_fill_rate
            FROM rooms r
            LEFT JOIN scheduled_exam_rooms ser ON ser.room_id = r.id
            LEFT JOIN scheduled_exams se ON se.id = ser.scheduled_exam_id AND se.exam_session_id = ?
            WHERE r.is_active = true
        ", [$this->examSessionId])[0];

        $utilizationPct = round($roomStats->used_rooms / $roomStats->total_rooms * 100, 1);

        $this->line("Total available rooms: {$roomStats->total_rooms}");
        $this->line("Rooms utilized: {$roomStats->used_rooms} ({$utilizationPct}%)");
        $this->line("Average room fill rate: {$roomStats->avg_fill_rate}%");
        $this->line("");

        if ($utilizationPct >= 80 && $roomStats->avg_fill_rate >= 60) {
            $this->info("[PASS] Room Efficiency: Good utilization rate");
            $this->results['passed']++;
        } else {
            $this->warn("[WARN] Room Efficiency: Utilization could be improved");
            $this->results['warnings']++;
        }
    }

    private function testProfessorWorkload(): void
    {
        $this->line("");
        $this->info("6. PROFESSOR ASSIGNMENT ANALYSIS");
        $this->line(str_repeat("-", 60));

        $stats = DB::select("
            SELECT 
                COUNT(DISTINCT p.id) as total_profs,
                COUNT(DISTINCT sep.professor_id) as assigned_profs,
                COALESCE(SUM(cnt), 0) as total_surveillances,
                ROUND(COALESCE(AVG(cnt), 0), 1) as avg_surveillances
            FROM professors p
            LEFT JOIN (
                SELECT professor_id, COUNT(*) as cnt
                FROM scheduled_exam_professors sep
                JOIN scheduled_exams se ON se.id = sep.scheduled_exam_id
                WHERE se.exam_session_id = ?
                GROUP BY professor_id
            ) sep ON sep.professor_id = p.id
            WHERE p.status = 'active'
        ", [$this->examSessionId])[0];

        $assignmentRate = $stats->total_profs > 0 ? round($stats->assigned_profs / $stats->total_profs * 100, 1) : 0;

        $this->line("Total active professors: {$stats->total_profs}");
        $this->line("Professors assigned: {$stats->assigned_profs} ({$assignmentRate}%)");
        $this->line("Total surveillance assignments: {$stats->total_surveillances}");
        $this->line("Average per assigned professor: {$stats->avg_surveillances}");
        $this->line("");

        if ($assignmentRate >= 70) {
            $this->info("[PASS] Professor Utilization: Good assignment rate");
            $this->results['passed']++;
        } else {
            $this->warn("[WARN] Professor Utilization: Low assignment rate ({$assignmentRate}%)");
            $this->results['warnings']++;
        }
    }

    private function testStudentDistribution(): void
    {
        $this->line("");
        $this->info("7. STUDENT LOAD DISTRIBUTION");
        $this->line(str_repeat("-", 60));

        $dailyDistribution = DB::select("
            SELECT 
                ts.exam_date,
                COUNT(DISTINCT s.id) as students_with_exams
            FROM students s
            JOIN inscriptions i ON i.student_id = s.id
            JOIN scheduled_exams se ON se.module_id = i.module_id AND se.group_id = s.group_id
            JOIN time_slots ts ON ts.id = se.time_slot_id
            WHERE i.exam_session_id = ?
            GROUP BY ts.exam_date
            ORDER BY ts.exam_date
        ", [$this->examSessionId]);

        if (empty($dailyDistribution)) {
            $this->warn("[WARN] No student distribution data available");
            $this->results['warnings']++;
            return;
        }

        $studentCounts = array_column($dailyDistribution, 'students_with_exams');
        $stddev = $this->calculateStdDev($studentCounts);
        $avg = array_sum($studentCounts) / count($studentCounts);

        $this->line("Average students per day: " . round($avg, 0));
        $this->line("Distribution variance: " . round($stddev, 1));
        $this->line("Total exam days: " . count($dailyDistribution));
        $this->line("");

        if ($avg > 0 && $stddev / $avg < 0.3) {
            $this->info("[PASS] Student Distribution: Well balanced across exam period");
            $this->results['passed']++;
        } else {
            $this->warn("[WARN] Student Distribution: Uneven distribution detected");
            $this->results['warnings']++;
        }
    }

    private function displayFinalReport(): void
    {
        $this->line("");
        $this->line(str_repeat("=", 60));
        $this->info("VALIDATION SUMMARY");
        $this->line(str_repeat("=", 60));

        $passed = $this->results['passed'];
        $failed = $this->results['failed'];
        $warnings = $this->results['warnings'];
        $critical = $this->results['critical'];
        $total = $passed + $failed + $warnings;

        $this->line("");
        $this->line("Tests Passed: {$passed}");
        $this->line("Tests Failed: {$failed}" . ($critical > 0 ? " (including {$critical} critical)" : ""));
        $this->line("Warnings: {$warnings}");
        $this->line("Total Checks: {$total}");
        $this->line("");

        if ($critical > 0) {
            $this->error("STATUS: INVALID - Critical constraint violations detected");
            $this->line("ACTION REQUIRED: Fix critical issues before deploying schedule");
        } elseif ($failed > 0) {
            $this->error("STATUS: NEEDS IMPROVEMENT - Some tests failed");
            $this->line("RECOMMENDATION: Review and address failed checks");
        } elseif ($warnings > 0) {
            $this->warn("STATUS: VALID WITH WARNINGS - Schedule is functional");
            $this->line("RECOMMENDATION: Review warnings for optimization opportunities");
        } else {
            $this->info("STATUS: PRODUCTION READY - All validation checks passed");
            $this->line("RESULT: Schedule is ready for deployment");
        }

        $this->line("");
    }

    private function calculateStdDev(array $values): float
    {
        $count = count($values);
        if ($count === 0)
            return 0;

        $mean = array_sum($values) / $count;
        $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $values)) / $count;

        return sqrt($variance);
    }
}
