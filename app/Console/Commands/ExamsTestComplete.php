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

        // Initialize results array
        $this->results = [
            'passed' => 0,
            'failed' => 0,
            'warnings' => 0,
            'critical' => 0
        ];

        $this->info("╔═══════════════════════════════════════════════════════════╗");
        $this->info("║  EXAM SCHEDULING SYSTEM - COMPLETE TEST SUITE            ║");
        $this->info("║  Session ID: {$this->examSessionId}                                            ║");
        $this->info("╚═══════════════════════════════════════════════════════════╝\n");

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

    // ============================================
    // TEST 1: DATA INTEGRITY
    // ============================================
    private function testDataIntegrity(): void
    {
        $this->newLine();
        $this->info("═══ TEST 1: DATA INTEGRITY ═══\n");

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

        $this->line("Scheduled Exams:      {$checks['scheduled_exams']}");
        $this->line("Room Allocations:     {$checks['scheduled_rooms']}");
        $this->line("Professor Assignments: {$checks['scheduled_professors']}");

        // Check orphans
        $orphanRooms = DB::select("
            SELECT COUNT(*) as cnt
            FROM scheduled_exam_rooms ser
            LEFT JOIN scheduled_exams se ON se.id = ser.scheduled_exam_id
            WHERE se.id IS NULL
        ")[0]->cnt;

        if ($orphanRooms > 0) {
            $this->error("❌ Found {$orphanRooms} orphaned room allocations!");
            $this->results['failed']++;
        } else {
            $this->info("✅ No orphaned records");
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
            $this->error("❌ {$examsWithoutRooms} exams have no room allocation!");
            $this->results['failed']++;
        } else {
            $this->info("✅ All exams have room allocations");
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
            $this->error("❌ {$examsWithoutProfs} exams have no professor assignment!");
            $this->results['failed']++;
        } else {
            $this->info("✅ All exams have professor assignments");
            $this->results['passed']++;
        }
    }

    // ============================================
    // TEST 2: HARD CONSTRAINTS (PROJECT SPEC)
    // ============================================
    private function testHardConstraints(): void
    {
        $this->newLine();
        $this->info("═══ TEST 2: HARD CONSTRAINTS (Project Requirements) ═══\n");

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
            $this->error("❌ CRITICAL: {$studentViolations} students have >1 exam/day!");
            $this->results['failed']++;
            $this->results['critical']++;
        } else {
            $this->info("✅ Max 1 exam/student/day (PASS)");
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
            $this->error("❌ CRITICAL: {$profViolations} professors have >3 exams/day!");
            $this->results['failed']++;
            $this->results['critical']++;
        } else {
            $this->info("✅ Max 3 exams/professor/day (PASS)");
            $this->results['passed']++;
        }

        // CONSTRAINT 3: Room capacity respected
        $capacityViolations = DB::select("
            SELECT COUNT(*) as cnt
            FROM scheduled_exam_rooms ser
            JOIN rooms r ON r.id = ser.room_id
            JOIN scheduled_exams se ON se.id = ser.scheduled_exam_id
            WHERE se.exam_session_id = ? AND ser.seats_allocated > r.capacity
        ", [$this->examSessionId])[0]->cnt;

        if ($capacityViolations > 0) {
            $this->error("❌ CRITICAL: {$capacityViolations} room capacity violations!");
            $this->results['failed']++;
            $this->results['critical']++;
        } else {
            $this->info("✅ Room capacity respected (PASS)");
            $this->results['passed']++;
        }

        // CONSTRAINT 4: No room double-booking
        $doubleBookings = DB::select("
            SELECT COUNT(*) as cnt
            FROM (
                SELECT 
                    r.id,
                    ts.id,
                    COUNT(DISTINCT se.id) as concurrent_exams
                FROM scheduled_exam_rooms ser
                JOIN scheduled_exams se ON se.id = ser.scheduled_exam_id
                JOIN time_slots ts ON ts.id = se.time_slot_id
                JOIN rooms r ON r.id = ser.room_id
                WHERE se.exam_session_id = ?
                GROUP BY r.id, ts.id
                HAVING COUNT(DISTINCT se.id) > 1
            ) conflicts
        ", [$this->examSessionId])[0]->cnt;

        if ($doubleBookings > 0) {
            $this->error("❌ CRITICAL: {$doubleBookings} room double-bookings!");
            $this->results['failed']++;
            $this->results['critical']++;
        } else {
            $this->info("✅ No room double-bookings (PASS)");
            $this->results['passed']++;
        }

        // CONSTRAINT 5: Professor department match
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
            $this->warn("⚠️  {$deptMismatch} professors assigned outside their department");
            $this->results['warnings']++;
        } else {
            $this->info("✅ All professors match exam department (PASS)");
            $this->results['passed']++;
        }
    }

    // ============================================
    // TEST 3: SOFT CONSTRAINTS (OPTIMIZATION)
    // ============================================
    private function testSoftConstraints(): void
    {
        $this->newLine();
        $this->info("═══ TEST 3: SOFT CONSTRAINTS (Optimization Quality) ═══\n");

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

        $ownPct = round($deptPriority->own_dept / $deptPriority->total * 100, 1);
        $overflowPct = round($deptPriority->overflow / $deptPriority->total * 100, 1);

        $this->line("Department Room Priority:");
        $this->line("  Own department:  {$deptPriority->own_dept} ({$ownPct}%)");
        $this->line("  Shared rooms:    {$deptPriority->shared}");
        $this->line("  Overflow:        {$deptPriority->overflow} ({$overflowPct}%)");

        if ($ownPct > $overflowPct) {
            $this->info("✅ Department priority working correctly");
            $this->results['passed']++;
        } else {
            $this->warn("⚠️  Department priority may not be optimal");
            $this->results['warnings']++;
        }

        // Professor workload balance
        $workloadStats = DB::select("
            SELECT 
                MIN(cnt) as min_load,
                MAX(cnt) as max_load,
                ROUND(AVG(cnt), 2) as avg_load,
                ROUND(STDDEV(cnt), 2) as stddev_load
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

        $this->line("\nProfessor Workload Balance:");
        $this->line("  Min: {$workloadStats->min_load}");
        $this->line("  Max: {$workloadStats->max_load}");
        $this->line("  Avg: {$workloadStats->avg_load}");
        $this->line("  Variance: {$variance}");

        if ($variance <= 5) {
            $this->info("✅ Workload well balanced (variance ≤5)");
            $this->results['passed']++;
        } else {
            $this->warn("⚠️  High workload variance (>{$variance})");
            $this->results['warnings']++;
        }
    }

    // ============================================
    // TEST 4: PERFORMANCE REQUIREMENTS
    // ============================================
    private function testPerformance(): void
    {
        $this->newLine();
        $this->info("═══ TEST 4: PERFORMANCE (<45s requirement) ═══\n");

        $perfLog = DB::table('performance_logs')
            ->where('exam_session_id', $this->examSessionId)
            ->orderByDesc('created_at')
            ->first();

        if (!$perfLog) {
            $this->warn("⚠️  No performance log found");
            $this->results['warnings']++;
            return;
        }

        $this->line("Algorithm: {$perfLog->algorithm}");
        $this->line("Exam count: {$perfLog->exam_count}");
        $this->line("Duration: {$perfLog->duration_seconds}s");
        $this->line("Success: " . ($perfLog->success ? 'Yes' : 'No'));

        if ($perfLog->duration_seconds < 45) {
            $this->info("✅ Performance target achieved (<45s)");
            $this->results['passed']++;
        } else {
            $this->error("❌ Performance target missed (≥45s)");
            $this->results['failed']++;
        }
    }

    // ============================================
    // TEST 5: ROOM USAGE EFFICIENCY
    // ============================================
    private function testRoomUsage(): void
    {
        $this->newLine();
        $this->info("═══ TEST 5: ROOM USAGE EFFICIENCY ═══\n");

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

        $this->line("Total rooms: {$roomStats->total_rooms}");
        $this->line("Used rooms: {$roomStats->used_rooms} ({$utilizationPct}%)");
        $this->line("Avg fill rate: {$roomStats->avg_fill_rate}%");

        if ($utilizationPct >= 80 && $roomStats->avg_fill_rate >= 60) {
            $this->info("✅ Room usage efficient");
            $this->results['passed']++;
        } else {
            $this->warn("⚠️  Room usage could be improved");
            $this->results['warnings']++;
        }
    }

    // ============================================
    // TEST 6: PROFESSOR WORKLOAD COMPLIANCE
    // ============================================
    private function testProfessorWorkload(): void
    {
        $this->newLine();
        $this->info("═══ TEST 6: PROFESSOR WORKLOAD DISTRIBUTION ═══\n");

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

        $this->line("Total professors: {$stats->total_profs}");
        $this->line("Assigned professors: {$stats->assigned_profs} ({$assignmentRate}%)");
        $this->line("Total surveillances: {$stats->total_surveillances}");
        $this->line("Avg per assigned prof: {$stats->avg_surveillances}");

        if ($assignmentRate >= 70) {
            $this->info("✅ Good professor utilization");
            $this->results['passed']++;
        } else {
            $this->warn("⚠️  Low professor utilization (<70%)");
            $this->results['warnings']++;
        }
    }

    // ============================================
    // TEST 7: STUDENT DISTRIBUTION
    // ============================================
    private function testStudentDistribution(): void
    {
        $this->newLine();
        $this->info("═══ TEST 7: STUDENT EXAM DISTRIBUTION ═══\n");

        $dailyDistribution = DB::select("
            SELECT 
                ts.exam_date,
                COUNT(DISTINCT s.id) as students_with_exams,
                COUNT(*) as total_exams_taken
            FROM students s
            JOIN inscriptions i ON i.student_id = s.id
            JOIN scheduled_exams se ON se.module_id = i.module_id AND se.group_id = s.group_id
            JOIN time_slots ts ON ts.id = se.time_slot_id
            WHERE i.exam_session_id = ?
            GROUP BY ts.exam_date
            ORDER BY ts.exam_date
        ", [$this->examSessionId]);

        if (empty($dailyDistribution)) {
            $this->warn("⚠️  No student distribution data");
            $this->results['warnings']++;
            return;
        }

        $studentCounts = array_column($dailyDistribution, 'students_with_exams');
        $stddev = $this->calculateStdDev($studentCounts);
        $avg = array_sum($studentCounts) / count($studentCounts);

        $this->line("Average students/day: " . round($avg, 0));
        $this->line("Std deviation: " . round($stddev, 1));
        $this->line("Days with exams: " . count($dailyDistribution));

        if ($avg > 0 && $stddev / $avg < 0.3) {
            $this->info("✅ Well-balanced student distribution");
            $this->results['passed']++;
        } else {
            $this->warn("⚠️  Uneven student distribution");
            $this->results['warnings']++;
        }
    }

    // ============================================
    // FINAL REPORT
    // ============================================
    private function displayFinalReport(): void
    {
        $this->newLine(2);
        $this->info("╔═══════════════════════════════════════════════════════════╗");
        $this->info("║                    FINAL TEST REPORT                      ║");
        $this->info("╚═══════════════════════════════════════════════════════════╝\n");

        $passed = $this->results['passed'];
        $failed = $this->results['failed'];
        $warnings = $this->results['warnings'];
        $critical = $this->results['critical'];
        $total = $passed + $failed + $warnings;

        $this->line("Tests Passed:   {$passed}");
        $this->line("Tests Failed:   {$failed}" . ($critical > 0 ? " ({$critical} CRITICAL)" : ""));
        $this->line("Warnings:       {$warnings}");
        $this->line("Total Tests:    {$total}");

        $this->newLine();

        if ($critical > 0) {
            $this->error("❌ CRITICAL FAILURES DETECTED - Schedule is INVALID!");
            $this->line("   Fix critical constraint violations before deployment.");
        } elseif ($failed > 0) {
            $this->error("❌ Some tests failed - Schedule needs improvement");
        } elseif ($warnings > 0) {
            $this->warn("⚠️  Schedule is VALID but has optimization opportunities");
        } else {
            $this->info("✅ ALL TESTS PASSED - Schedule is production-ready!");
        }

        $this->newLine();
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
