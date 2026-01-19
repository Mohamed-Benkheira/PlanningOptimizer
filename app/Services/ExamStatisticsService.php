<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ExamStatisticsService
{
    public function getRoomUsageStats(int $examSessionId, ?int $departmentId = null)
    {
        $sql = "
            SELECT 
                COUNT(DISTINCT r.id) as total_rooms,
                COUNT(DISTINCT CASE WHEN ser.id IS NOT NULL THEN r.id END) as used_rooms,
                ROUND(AVG(CAST(ser.seats_allocated AS NUMERIC) / NULLIF(r.capacity, 0) * 100), 1) as avg_fill_rate
            FROM rooms r
            LEFT JOIN scheduled_exam_rooms ser ON ser.room_id = r.id
            LEFT JOIN scheduled_exams se ON se.id = ser.scheduled_exam_id AND se.exam_session_id = ?
            WHERE r.is_active = true
        ";

        if ($departmentId) {
            $sql .= " AND (r.department_id = ? OR r.department_id IS NULL)";
            return DB::selectOne($sql, [$examSessionId, $departmentId]);
        }

        return DB::selectOne($sql, [$examSessionId]);
    }

    public function getProfessorWorkloadStats(int $examSessionId, ?int $departmentId = null)
    {
        $sql = "
            SELECT 
                COUNT(DISTINCT p.id) as total_profs,
                COUNT(DISTINCT sep.professor_id) as assigned_profs,
                COALESCE(SUM(cnt), 0) as total_surveillances,
                ROUND(COALESCE(AVG(cnt), 0), 1) as avg_surveillances,
                MIN(cnt) as min_load,
                MAX(cnt) as max_load
            FROM professors p
            LEFT JOIN (
                SELECT professor_id, COUNT(*) as cnt
                FROM scheduled_exam_professors sep
                JOIN scheduled_exams se ON se.id = sep.scheduled_exam_id
                WHERE se.exam_session_id = ?
                GROUP BY professor_id
            ) sep ON sep.professor_id = p.id
            WHERE p.status = 'active'
        ";

        if ($departmentId) {
            $sql .= " AND p.department_id = ?";
            return DB::selectOne($sql, [$examSessionId, $departmentId]);
        }

        return DB::selectOne($sql, [$examSessionId]);
    }

    public function getConflictStats(int $examSessionId, ?int $departmentId = null)
    {
        $sql = "
            SELECT COUNT(*) as cnt
            FROM (
                SELECT s.id, ts.exam_date
                FROM students s
                JOIN inscriptions i ON i.student_id = s.id
                JOIN scheduled_exams se ON se.module_id = i.module_id AND se.group_id = s.group_id
                JOIN time_slots ts ON ts.id = se.time_slot_id
        ";

        if ($departmentId) {
            $sql .= " JOIN modules m ON m.id = se.module_id
                WHERE i.exam_session_id = ? AND m.department_id = ?";
            $studentConflicts = DB::selectOne($sql . "
                GROUP BY s.id, ts.exam_date
                HAVING COUNT(*) > 1
            ) violations", [$examSessionId, $departmentId])->cnt;
        } else {
            $sql .= " WHERE i.exam_session_id = ?";
            $studentConflicts = DB::selectOne($sql . "
                GROUP BY s.id, ts.exam_date
                HAVING COUNT(*) > 1
            ) violations", [$examSessionId])->cnt;
        }

        return ['student_conflicts' => $studentConflicts];
    }

    public function getHealthCheckReport(int $examSessionId, ?int $departmentId = null): array
    {
        $tests = [];

        // 1. Data Integrity (Orphans)
        $orphans = DB::selectOne("
            SELECT (
                (SELECT COUNT(*) FROM scheduled_exam_rooms ser LEFT JOIN scheduled_exams se ON se.id = ser.scheduled_exam_id WHERE se.id IS NULL) +
                (SELECT COUNT(*) FROM scheduled_exam_professors sep LEFT JOIN scheduled_exams se ON se.id = sep.scheduled_exam_id WHERE se.id IS NULL)
            ) as cnt
        ")->cnt;
        $tests[] = [
            'name' => 'Data Integrity',
            'status' => $orphans === 0 ? 'success' : 'danger',
            'message' => $orphans === 0 ? 'No orphans found' : "$orphans orphaned records",
        ];

        // 2. Hard Constraint: Student Conflicts
        if ($departmentId) {
            $studConf = DB::selectOne("
                SELECT COUNT(*) as cnt FROM (
                    SELECT s.id, ts.exam_date FROM students s
                    JOIN inscriptions i ON i.student_id = s.id
                    JOIN scheduled_exams se ON se.module_id = i.module_id AND se.group_id = s.group_id
                    JOIN time_slots ts ON ts.id = se.time_slot_id
                    JOIN modules m ON m.id = se.module_id
                    WHERE i.exam_session_id = ? AND m.department_id = ?
                    GROUP BY s.id, ts.exam_date HAVING COUNT(*) > 1
                ) q
            ", [$examSessionId, $departmentId])->cnt;
        } else {
            $studConf = DB::selectOne("
                SELECT COUNT(*) as cnt FROM (
                    SELECT s.id, ts.exam_date FROM students s
                    JOIN inscriptions i ON i.student_id = s.id
                    JOIN scheduled_exams se ON se.module_id = i.module_id AND se.group_id = s.group_id
                    JOIN time_slots ts ON ts.id = se.time_slot_id
                    WHERE i.exam_session_id = ?
                    GROUP BY s.id, ts.exam_date HAVING COUNT(*) > 1
                ) q
            ", [$examSessionId])->cnt;
        }
        $tests[] = [
            'name' => 'Student Daily Limit (1/day)',
            'status' => $studConf === 0 ? 'success' : 'danger',
            'message' => $studConf === 0 ? 'All Clear' : "$studConf violations",
        ];

        // 3. Hard Constraint: Professor Limit
        if ($departmentId) {
            $profConf = DB::selectOne("
                SELECT COUNT(*) as cnt FROM (
                    SELECT p.id, ts.exam_date FROM professors p
                    JOIN scheduled_exam_professors sep ON sep.professor_id = p.id
                    JOIN scheduled_exams se ON se.id = sep.scheduled_exam_id
                    JOIN time_slots ts ON ts.id = se.time_slot_id
                    WHERE se.exam_session_id = ? AND p.department_id = ?
                    GROUP BY p.id, ts.exam_date HAVING COUNT(*) > 3
                ) q
            ", [$examSessionId, $departmentId])->cnt;
        } else {
            $profConf = DB::selectOne("
                SELECT COUNT(*) as cnt FROM (
                    SELECT p.id, ts.exam_date FROM professors p
                    JOIN scheduled_exam_professors sep ON sep.professor_id = p.id
                    JOIN scheduled_exams se ON se.id = sep.scheduled_exam_id
                    JOIN time_slots ts ON ts.id = se.time_slot_id
                    WHERE se.exam_session_id = ?
                    GROUP BY p.id, ts.exam_date HAVING COUNT(*) > 3
                ) q
            ", [$examSessionId])->cnt;
        }
        $tests[] = [
            'name' => 'Professor Daily Limit (3/day)',
            'status' => $profConf === 0 ? 'success' : 'danger',
            'message' => $profConf === 0 ? 'All Clear' : "$profConf violations",
        ];

        // 4. Room Capacity
        $sql = "
            SELECT COUNT(*) as cnt
            FROM scheduled_exam_rooms ser
            JOIN rooms r ON r.id = ser.room_id
            WHERE ser.seats_allocated > r.capacity
        ";

        if ($departmentId) {
            $sql .= " AND (r.department_id = ? OR r.department_id IS NULL)";
            $capOverflow = DB::selectOne($sql, [$departmentId])->cnt;
        } else {
            $capOverflow = DB::selectOne($sql)->cnt;
        }

        $tests[] = [
            'name' => 'Room Capacity Checks',
            'status' => $capOverflow === 0 ? 'success' : 'danger',
            'message' => $capOverflow === 0 ? '100% Capacity respected' : "$capOverflow overflows detected",
        ];

        return $tests;
    }

    public function getDailyStudentLoad(int $examSessionId, ?int $departmentId = null)
    {
        $sql = "
            SELECT ts.exam_date, COUNT(DISTINCT s.id) as student_count
            FROM time_slots ts
            JOIN scheduled_exams se ON se.time_slot_id = ts.id
            JOIN inscriptions i ON i.module_id = se.module_id
            JOIN students s ON s.id = i.student_id
        ";

        if ($departmentId) {
            $sql .= " JOIN modules m ON m.id = se.module_id
                WHERE se.exam_session_id = ? AND m.department_id = ?";
            return DB::select($sql . "
                GROUP BY ts.exam_date
                ORDER BY ts.exam_date
            ", [$examSessionId, $departmentId]);
        }

        $sql .= " WHERE se.exam_session_id = ?";
        return DB::select($sql . "
            GROUP BY ts.exam_date
            ORDER BY ts.exam_date
        ", [$examSessionId]);
    }

    /**
     * Get global statistics overview
     */
    public function getGlobalStats(int $examSessionId, ?int $departmentId = null)
    {
        $sql = "
            SELECT 
                COUNT(DISTINCT se.id) as total_exams,
                COUNT(DISTINCT i.student_id) as total_students,
                COUNT(DISTINCT sep.professor_id) as total_professors,
                COUNT(DISTINCT ser.room_id) as total_rooms_used,
                COUNT(DISTINCT ts.exam_date) as total_exam_days,
                MIN(ts.exam_date) as first_exam_date,
                MAX(ts.exam_date) as last_exam_date
            FROM scheduled_exams se
            LEFT JOIN inscriptions i ON i.module_id = se.module_id AND i.exam_session_id = se.exam_session_id
            LEFT JOIN scheduled_exam_professors sep ON sep.scheduled_exam_id = se.id
            LEFT JOIN scheduled_exam_rooms ser ON ser.scheduled_exam_id = se.id
            LEFT JOIN time_slots ts ON ts.id = se.time_slot_id
        ";

        if ($departmentId) {
            $sql .= " JOIN modules m ON m.id = se.module_id
                WHERE se.exam_session_id = ? AND m.department_id = ?";
            return DB::selectOne($sql, [$examSessionId, $departmentId]);
        }

        $sql .= " WHERE se.exam_session_id = ?";
        return DB::selectOne($sql, [$examSessionId]);
    }

    /**
     * Get department breakdown with detailed metrics
     */
    public function getDepartmentBreakdown(int $examSessionId, ?int $departmentId = null)
    {
        $sql = "
            SELECT 
                d.name as department_name,
                d.code as department_code,
                COUNT(DISTINCT se.id) as exam_count,
                COUNT(DISTINCT i.student_id) as student_count,
                COUNT(DISTINCT sep.professor_id) as professor_count,
                ROUND(AVG(ser.seats_allocated::numeric / NULLIF(r.capacity, 0) * 100), 1) as avg_room_utilization
            FROM departments d
            LEFT JOIN modules m ON m.department_id = d.id
            LEFT JOIN scheduled_exams se ON se.module_id = m.id AND se.exam_session_id = ?
            LEFT JOIN inscriptions i ON i.module_id = m.id AND i.exam_session_id = ?
            LEFT JOIN scheduled_exam_professors sep ON sep.scheduled_exam_id = se.id
            LEFT JOIN scheduled_exam_rooms ser ON ser.scheduled_exam_id = se.id
            LEFT JOIN rooms r ON r.id = ser.room_id
        ";

        if ($departmentId) {
            $sql .= " WHERE d.id = ?
                GROUP BY d.id, d.name, d.code
                ORDER BY d.name";
            return DB::select($sql, [$examSessionId, $examSessionId, $departmentId]);
        }

        $sql .= " GROUP BY d.id, d.name, d.code
            ORDER BY d.name";
        return DB::select($sql, [$examSessionId, $examSessionId]);
    }

    /**
     * Get peak exam days
     */
    public function getPeakExamDays(int $examSessionId, ?int $departmentId = null)
    {
        $sql = "
            SELECT 
                ts.exam_date,
                COUNT(DISTINCT se.id) as exam_count,
                COUNT(DISTINCT i.student_id) as student_count,
                COUNT(DISTINCT sep.professor_id) as professor_count
            FROM time_slots ts
            JOIN scheduled_exams se ON se.time_slot_id = ts.id
            LEFT JOIN inscriptions i ON i.module_id = se.module_id AND i.exam_session_id = ts.exam_session_id
            LEFT JOIN scheduled_exam_professors sep ON sep.scheduled_exam_id = se.id
        ";

        if ($departmentId) {
            $sql .= " JOIN modules m ON m.id = se.module_id
                WHERE ts.exam_session_id = ? AND m.department_id = ?";
            return DB::select($sql . "
                GROUP BY ts.exam_date
                ORDER BY exam_count DESC
                LIMIT 5
            ", [$examSessionId, $departmentId]);
        }

        $sql .= " WHERE ts.exam_session_id = ?";
        return DB::select($sql . "
            GROUP BY ts.exam_date
            ORDER BY exam_count DESC
            LIMIT 5
        ", [$examSessionId]);
    }

    /**
     * Get detailed list of students with multiple exams per day
     */
    public function getStudentConflicts(int $examSessionId, ?int $departmentId = null)
    {
        $sql = "
            SELECT 
                s.id as student_id,
                s.matricule,
                s.first_name,
                s.last_name,
                ts.exam_date,
                COUNT(*) as exam_count,
                STRING_AGG(m.name, ', ') as module_names
            FROM students s
            JOIN inscriptions i ON i.student_id = s.id
            JOIN scheduled_exams se ON se.module_id = i.module_id AND se.group_id = s.group_id
            JOIN time_slots ts ON ts.id = se.time_slot_id
            JOIN modules m ON m.id = se.module_id
        ";

        if ($departmentId) {
            $sql .= " WHERE i.exam_session_id = ? AND m.department_id = ?";
            return DB::select($sql . "
                GROUP BY s.id, s.matricule, s.first_name, s.last_name, ts.exam_date
                HAVING COUNT(*) > 1
                ORDER BY ts.exam_date, s.last_name
            ", [$examSessionId, $departmentId]);
        }

        $sql .= " WHERE i.exam_session_id = ?";
        return DB::select($sql . "
            GROUP BY s.id, s.matricule, s.first_name, s.last_name, ts.exam_date
            HAVING COUNT(*) > 1
            ORDER BY ts.exam_date, s.last_name
        ", [$examSessionId]);
    }

    /**
     * Get detailed list of professors with >3 exams per day
     */
    public function getProfessorConflicts(int $examSessionId, ?int $departmentId = null)
    {
        $sql = "
            SELECT 
                p.id as professor_id,
                p.first_name,
                p.last_name,
                ts.exam_date,
                COUNT(*) as exam_count,
                STRING_AGG(m.name, ', ') as module_names
            FROM professors p
            JOIN scheduled_exam_professors sep ON sep.professor_id = p.id
            JOIN scheduled_exams se ON se.id = sep.scheduled_exam_id
            JOIN time_slots ts ON ts.id = se.time_slot_id
            JOIN modules m ON m.id = se.module_id
        ";

        if ($departmentId) {
            $sql .= " WHERE se.exam_session_id = ? AND p.department_id = ?";
            return DB::select($sql . "
                GROUP BY p.id, p.first_name, p.last_name, ts.exam_date
                HAVING COUNT(*) > 3
                ORDER BY ts.exam_date, p.last_name
            ", [$examSessionId, $departmentId]);
        }

        $sql .= " WHERE se.exam_session_id = ?";
        return DB::select($sql . "
            GROUP BY p.id, p.first_name, p.last_name, ts.exam_date
            HAVING COUNT(*) > 3
            ORDER BY ts.exam_date, p.last_name
        ", [$examSessionId]);
    }

    /**
     * Get detailed list of room capacity violations
     */
    public function getRoomCapacityViolations(int $examSessionId, ?int $departmentId = null)
    {
        $sql = "
            SELECT 
                r.id as room_id,
                r.code as room_code,
                r.name as room_name,
                r.capacity,
                ts.exam_date,
                ts.starts_at,
                SUM(ser.seats_allocated) as total_allocated,
                STRING_AGG(m.name, ', ') as exam_names
            FROM scheduled_exam_rooms ser
            JOIN scheduled_exams se ON se.id = ser.scheduled_exam_id
            JOIN time_slots ts ON ts.id = se.time_slot_id
            JOIN rooms r ON r.id = ser.room_id
            JOIN modules m ON m.id = se.module_id
        ";

        if ($departmentId) {
            $sql .= " WHERE se.exam_session_id = ? AND (r.department_id = ? OR r.department_id IS NULL)";
            return DB::select($sql . "
                GROUP BY r.id, r.code, r.name, r.capacity, ts.id, ts.exam_date, ts.starts_at
                HAVING SUM(ser.seats_allocated) > r.capacity
                ORDER BY ts.exam_date, ts.starts_at, r.code
            ", [$examSessionId, $departmentId]);
        }

        $sql .= " WHERE se.exam_session_id = ?";
        return DB::select($sql . "
            GROUP BY r.id, r.code, r.name, r.capacity, ts.id, ts.exam_date, ts.starts_at
            HAVING SUM(ser.seats_allocated) > r.capacity
            ORDER BY ts.exam_date, ts.starts_at, r.code
        ", [$examSessionId]);
    }
}
