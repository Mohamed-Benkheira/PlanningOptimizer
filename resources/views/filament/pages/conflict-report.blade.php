<x-filament-panels::page>
    @if($sessionId)
        <div class="space-y-6">
            
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border-2 {{ count($studentConflicts) > 0 ? 'border-red-300 dark:border-red-800' : 'border-green-300 dark:border-green-800' }}">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-3xl font-bold {{ count($studentConflicts) > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                {{ count($studentConflicts) }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Student Conflicts</div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border-2 {{ count($professorConflicts) > 0 ? 'border-red-300 dark:border-red-800' : 'border-green-300 dark:border-green-800' }}">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-3xl font-bold {{ count($professorConflicts) > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                {{ count($professorConflicts) }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Professor Violations</div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border-2 {{ count($roomViolations) > 0 ? 'border-red-300 dark:border-red-800' : 'border-green-300 dark:border-green-800' }}">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-3xl font-bold {{ count($roomViolations) > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                {{ count($roomViolations) }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Room Overflows</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Student Conflicts -->
            @if(count($studentConflicts) > 0)
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <span>Student Conflicts (Multiple Exams Same Day)</span>
                    </div>
                </x-slot>

                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Matricule</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Date</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Count</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Modules</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($studentConflicts as $conflict)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-4 py-3 font-mono text-xs">{{ $conflict->matricule }}</td>
                            <td class="px-4 py-3 font-medium">{{ $conflict->first_name }} {{ $conflict->last_name }}</td>
                            <td class="px-4 py-3">{{ $conflict->exam_date }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-1 bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 rounded-full text-xs font-bold">
                                    {{ $conflict->exam_count }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400">{{ $conflict->module_names }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-filament::section>
            @endif

            <!-- Professor Conflicts -->
            @if(count($professorConflicts) > 0)
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <span>Professor Violations (>3 Exams Same Day)</span>
                    </div>
                </x-slot>

                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Date</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Count</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Modules</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($professorConflicts as $conflict)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-4 py-3 font-medium">{{ $conflict->first_name }} {{ $conflict->last_name }}</td>
                            <td class="px-4 py-3">{{ $conflict->exam_date }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-1 bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 rounded-full text-xs font-bold">
                                    {{ $conflict->exam_count }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400">{{ $conflict->module_names }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-filament::section>
            @endif

            <!-- Room Violations -->
            @if(count($roomViolations) > 0)
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <span>Room Capacity Violations</span>
                    </div>
                </x-slot>

                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Room</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Date & Time</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Capacity</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Allocated</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Overflow</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Exams</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($roomViolations as $violation)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-4 py-3 font-medium">{{ $violation->room_code }}</td>
                            <td class="px-4 py-3">{{ $violation->exam_date }} {{ $violation->starts_at }}</td>
                            <td class="px-4 py-3 text-center">{{ $violation->capacity }}</td>
                            <td class="px-4 py-3 text-center font-bold text-red-600 dark:text-red-400">{{ $violation->total_allocated }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-1 bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 rounded-full text-xs font-bold">
                                    +{{ $violation->total_allocated - $violation->capacity }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400">{{ $violation->exam_names }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-filament::section>
            @endif

            <!-- No Conflicts -->
            @if(count($studentConflicts) === 0 && count($professorConflicts) === 0 && count($roomViolations) === 0)
            <x-filament::section>
                <div class="text-center py-16">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                        All Clear!
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        No conflicts detected. The schedule is ready for approval.
                    </p>
                </div>
            </x-filament::section>
            @endif

        </div>
    @else
        <x-filament::section>
            <div class="text-center py-12">
                <p class="text-gray-500">No exam session available.</p>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
