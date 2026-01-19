<?php

namespace App\Filament\Pages;

use App\Models\ExamSession;
use App\Services\ExamStatisticsService;
use Filament\Pages\Page;
use BackedEnum;
use UnitEnum;
class GlobalAnalytics extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar';
    protected string $view = 'filament.pages.global-analytics';
    protected static ?string $navigationLabel = 'Global Analytics';
    protected static ?string $title = 'Global Analytics Dashboard';
    protected static UnitEnum|string|null $navigationGroup = 'Analytics';
    protected static ?int $navigationSort = 1;

    public $globalStats = null;
    public $departmentBreakdown = [];
    public $peakDays = [];
    public $sessionId = null;


    public static function canAccess(): bool
    {
        return auth()->user()->isDean();
    }

    public function mount(): void
    {
        $session = ExamSession::latest('id')->first();

        if ($session) {
            $this->sessionId = $session->id;
            $service = new ExamStatisticsService();

            $this->globalStats = $service->getGlobalStats($session->id);
            $this->departmentBreakdown = $service->getDepartmentBreakdown($session->id);
            $this->peakDays = $service->getPeakExamDays($session->id);
        }
    }
}
