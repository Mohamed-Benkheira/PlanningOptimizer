<?php

namespace App\Filament\Widgets;

use App\Models\Department;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class StudentsPerDepartmentChart extends ChartWidget
{
    protected ?string $heading = 'Students Distribution by Department';


    protected function getData(): array
    {
        // Direct SQL: Count students per department
        // Students -> Groups -> Specialties -> Departments
        $data = DB::table('departments')
            ->join('specialties', 'departments.id', '=', 'specialties.department_id')
            ->join('groups', 'specialties.id', '=', 'groups.specialty_id')
            ->join('students', 'groups.id', '=', 'students.group_id')
            ->select('departments.code', DB::raw('count(students.id) as total'))
            ->groupBy('departments.code')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Students',
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => [
                        '#f59e0b',
                        '#3b82f6',
                        '#10b981',
                        '#ef4444',
                        '#8b5cf6',
                        '#ec4899',
                        '#6366f1'
                    ],
                ],
            ],
            'labels' => $data->pluck('code')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut'; // or 'pie'
    }
}
