<?php

namespace App\Filament\Widgets;

use App\Models\ExamSession;
use App\Services\ExamStatisticsService;
use Filament\Widgets\Widget;

class SystemHealthCheck extends Widget
{
    // REMOVED 'static' keyword here:
    protected string $view = 'filament.widgets.system-health-check';

    protected int|string|array $columnSpan = 'full';


    // 'sort' MUST be static
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()->isDean() || auth()->user()->isSuperAdmin();
    }

    protected function getViewData(): array
    {
        $session = ExamSession::latest('id')->first();

        return [
            'results' => $session ? (new ExamStatisticsService())->getHealthCheckReport($session->id) : [],
        ];
    }
}
