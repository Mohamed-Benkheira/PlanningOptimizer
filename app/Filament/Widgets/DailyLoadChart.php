<?php

namespace App\Filament\Widgets;

use App\Models\ExamSession;
use App\Services\ExamStatisticsService;
use Filament\Widgets\ChartWidget;

class DailyLoadChart extends ChartWidget
{
    // REMOVED 'static' keyword:
    protected ?string $heading = 'Student Exam Distribution (Daily Load)';

    protected static ?int $sort = 3;

    public static function canView(): bool
    {
        return auth()->user()->isDean() || auth()->user()->isSuperAdmin();
    }

    protected function getData(): array
    {
        $session = ExamSession::latest('id')->first();
        if (!$session)
            return ['datasets' => [], 'labels' => []];

        $data = (new ExamStatisticsService())->getDailyStudentLoad($session->id);

        $labels = array_map(fn($item) => $item->exam_date, $data);
        $counts = array_map(fn($item) => $item->student_count, $data);

        return [
            'datasets' => [
                [
                    'label' => 'Students with Exams',
                    'data' => $counts,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
