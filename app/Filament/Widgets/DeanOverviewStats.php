<?php

namespace App\Filament\Widgets;

use App\Models\ExamSession;
use App\Services\ExamStatisticsService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DeanOverviewStats extends BaseWidget
{
    protected static ?int $sort = 1; // Show first

    public static function canView(): bool
    {
        return auth()->user()->isDean() || auth()->user()->isSuperAdmin();
    }

    protected function getStats(): array
    {
        $session = ExamSession::latest('id')->first();

        if (!$session) {
            return [
                Stat::make('No Data', 'No Sessions Found')->color('gray'),
            ];
        }

        $service = new ExamStatisticsService();

        $roomStats = $service->getRoomUsageStats($session->id);
        $profStats = $service->getProfessorWorkloadStats($session->id);
        $conflicts = $service->getConflictStats($session->id);

        return [
            Stat::make('Room Efficiency', ($roomStats->avg_fill_rate ?? 0) . '%')
                ->description(($roomStats->used_rooms ?? 0) . '/' . ($roomStats->total_rooms ?? 0) . ' Rooms Used')
                ->descriptionIcon('heroicon-o-building-office-2')
                ->color(($roomStats->avg_fill_rate ?? 0) > 60 ? 'success' : 'warning'),

            Stat::make('Avg Prof Workload', $profStats->avg_surveillances ?? 0)
                ->description('Range: ' . ($profStats->min_load ?? 0) . ' - ' . ($profStats->max_load ?? 0) . ' exams')
                ->descriptionIcon('heroicon-o-users')
                ->color('info'),

            Stat::make('Student Conflicts', $conflicts['student_conflicts'] ?? 0)
                ->description('Students with >1 exam/day')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color(($conflicts['student_conflicts'] ?? 0) === 0 ? 'success' : 'danger'),
        ];
    }
}
