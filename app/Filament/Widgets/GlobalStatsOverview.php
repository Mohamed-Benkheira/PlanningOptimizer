<?php

namespace App\Filament\Widgets;

use App\Models\Student;
use App\Models\Department;
use App\Models\Professor;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GlobalStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Students', Student::count())
                ->description('Active enrollments')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('success'),

            Stat::make('Departments', Department::count())
                ->description('Faculties managed')
                ->color('primary'),

            Stat::make('Total Professors', Professor::count())
                ->description('Teaching staff')
                ->color('warning'),
        ];
    }
}
