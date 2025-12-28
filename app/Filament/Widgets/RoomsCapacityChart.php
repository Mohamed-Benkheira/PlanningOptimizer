<?php

namespace App\Filament\Widgets;

use App\Models\Room;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class RoomsCapacityChart extends ChartWidget
{
    protected ?string $heading = 'Total Capacity by Room Type';


    protected function getData(): array
    {
        $data = Room::select('type', DB::raw('sum(capacity) as total_capacity'))
            ->groupBy('type')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Total Seats',
                    'data' => $data->pluck('total_capacity'),
                    'backgroundColor' => '#3b82f6',
                ],
            ],
            'labels' => $data->pluck('type'),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
