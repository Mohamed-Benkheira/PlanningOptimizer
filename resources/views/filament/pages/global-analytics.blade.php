<x-filament-panels::page>
    @if($sessionId && $globalStats)
        <div class="space-y-6">
            <!-- Global KPI Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <x-filament::section>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-primary-600">{{ $globalStats->total_exams }}</div>
                        <div class="text-xs text-gray-500 mt-1">Total Exams</div>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-primary-600">{{ $globalStats->total_students }}</div>
                        <div class="text-xs text-gray-500 mt-1">Students</div>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-primary-600">{{ $globalStats->total_professors }}</div>
                        <div class="text-xs text-gray-500 mt-1">Professors</div>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-primary-600">{{ $globalStats->total_exam_days }}</div>
                        <div class="text-xs text-gray-500 mt-1">Exam Days</div>
                    </div>
                </x-filament::section>
            </div>

            <!-- Department Breakdown Table -->
            <x-filament::section>
                <x-slot name="heading">
                    Department Performance Breakdown
                </x-slot>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-left">Department</th>
                                <th class="px-4 py-3 text-center">Exams</th>
                                <th class="px-4 py-3 text-center">Students</th>
                                <th class="px-4 py-3 text-center">Professors</th>
                                <th class="px-4 py-3 text-center">Room Utilization</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($departmentBreakdown as $dept)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-4 py-3 font-medium">{{ $dept->department_name }}</td>
                                <td class="px-4 py-3 text-center">{{ $dept->exam_count }}</td>
                                <td class="px-4 py-3 text-center">{{ $dept->student_count }}</td>
                                <td class="px-4 py-3 text-center">{{ $dept->professor_count }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2 py-1 rounded text-xs font-medium
                                        @if($dept->avg_room_utilization > 80)
                                            bg-green-100 text-green-700
                                        @elseif($dept->avg_room_utilization > 60)
                                            bg-yellow-100 text-yellow-700
                                        @else
                                            bg-red-100 text-red-700
                                        @endif
                                    ">
                                        {{ $dept->avg_room_utilization ?? 0 }}%
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>

            <!-- Peak Exam Days -->
            <x-filament::section>
                <x-slot name="heading">
                    Top 5 Busiest Exam Days
                </x-slot>

                <div class="space-y-3">
                    @foreach($peakDays as $day)
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="text-lg font-bold text-primary-600">{{ $day->exam_date }}</div>
                            <div class="text-xs text-gray-500">
                                {{ $day->exam_count }} exams • {{ $day->student_count }} students • {{ $day->professor_count }} professors
                            </div>
                        </div>
                        <div class="text-2xl font-bold text-gray-300">{{ $loop->iteration }}</div>
                    </div>
                    @endforeach
                </div>
            </x-filament::section>
        </div>
    @else
        <x-filament::section>
            <p class="text-gray-500">No exam session data available.</p>
        </x-filament::section>
    @endif
</x-filament-panels::page>
