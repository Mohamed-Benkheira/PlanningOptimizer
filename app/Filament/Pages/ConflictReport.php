<?php

namespace App\Filament\Pages;

use App\Models\ExamSession;
use App\Services\ExamStatisticsService;
use Filament\Pages\Page;
use BackedEnum;
use UnitEnum;
class ConflictReport extends Page
{

    protected string $view = 'filament.pages.conflict-report';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected static ?string $navigationLabel = 'Conflict Report';
    protected static ?string $title = 'Conflict & Violation Report';
    protected static UnitEnum|string|null $navigationGroup = 'Analytics'; // Added 'static' here
    protected static ?int $navigationSort = 2;

    public $studentConflicts = [];
    public $professorConflicts = [];
    public $roomViolations = [];
    public $sessionId = null;
    public $debugInfo = '';

    public static function canAccess(): bool
    {
        return auth()->user()->isDean();
    }

    public function mount(): void
    {
        $session = ExamSession::latest('id')->first();

        if ($session) {
            $this->sessionId = $session->id;
            $this->debugInfo = "Session ID: {$session->id}, Name: {$session->name}";

            $service = new ExamStatisticsService();

            $this->studentConflicts = $service->getStudentConflicts($session->id);
            $this->professorConflicts = $service->getProfessorConflicts($session->id);
            $this->roomViolations = $service->getRoomCapacityViolations($session->id);

            $this->debugInfo .= " | Students: " . count($this->studentConflicts);
            $this->debugInfo .= " | Professors: " . count($this->professorConflicts);
            $this->debugInfo .= " | Rooms: " . count($this->roomViolations);
        } else {
            $this->debugInfo = "No exam session found in database";
        }
    }
}
