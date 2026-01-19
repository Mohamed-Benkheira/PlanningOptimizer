<?php

namespace App\Filament\Student\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use App\Models\ExamSession;
use BackedEnum;
class StudentSchedule extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-calendar-days';
    protected string $view = 'filament.student.pages.student-schedule';
    protected static ?string $navigationLabel = 'My Exam Schedule';
    protected static ?string $title = 'My Exam Schedule';
    protected static ?int $navigationSort = 1;

    public $schedule = [];
    public $session = null;
    public $student = null;
    public $stats = null;

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->isStudent();
    }

    public function mount(): void
    {
        $user = auth()->user();

        if (!$user->student_id) {
            return;
        }

        // Get latest exam session
        $this->session = ExamSession::with(['academicYear', 'semester'])
            ->latest('id')
            ->first();

        if (!$this->session) {
            return;
        }

        // Get student info
        $this->student = DB::selectOne("
            SELECT s.*, g.name as group_name, sp.name as specialty_name, l.code as level_code
            FROM students s
            JOIN groups g ON g.id = s.group_id
            JOIN specialties sp ON sp.id = g.specialty_id
            JOIN levels l ON l.id = g.level_id
            WHERE s.id = ?
        ", [$user->student_id]);

        // Get student's exam schedule
        $this->schedule = DB::select("
            SELECT 
                ts.exam_date,
                ts.starts_at,
                ts.ends_at,
                m.code as module_code,
                m.name as module_name,
                m.credits,
                STRING_AGG(DISTINCT r.code, ', ') as rooms,
                STRING_AGG(DISTINCT p.last_name || ' ' || p.first_name, ', ') as professors,
                se.student_count
            FROM inscriptions i
            JOIN scheduled_exams se ON se.module_id = i.module_id AND se.group_id = ?
            JOIN time_slots ts ON ts.id = se.time_slot_id
            JOIN modules m ON m.id = se.module_id
            LEFT JOIN scheduled_exam_rooms ser ON ser.scheduled_exam_id = se.id
            LEFT JOIN rooms r ON r.id = ser.room_id
            LEFT JOIN scheduled_exam_professors sep ON sep.scheduled_exam_id = se.id
            LEFT JOIN professors p ON p.id = sep.professor_id
            WHERE i.student_id = ? AND i.exam_session_id = ?
            GROUP BY ts.exam_date, ts.starts_at, ts.ends_at, m.code, m.name, m.credits, se.student_count
            ORDER BY ts.exam_date, ts.starts_at
        ", [$this->student->group_id, $user->student_id, $this->session->id]);

        // Calculate stats
        $this->stats = [
            'total_exams' => count($this->schedule),
            'total_credits' => array_sum(array_column($this->schedule, 'credits')),
            'days_count' => count(array_unique(array_column($this->schedule, 'exam_date'))),
            'first_exam' => $this->schedule[0]->exam_date ?? null,
            'last_exam' => end($this->schedule)->exam_date ?? null,
        ];
    }
}
