<x-filament-panels::page>
    <div class="space-y-6">
        
        <!-- Selection Form -->
        <x-filament::section>
            <x-slot name="heading">
                Select Exam Session
            </x-slot>
            
            <form wire:submit.prevent>
                {{ $this->form }}
            </form>
        </x-filament::section>

        <!-- Action Buttons -->
        @if($this->data['exam_session_id'] ?? null)
        <x-filament::section>
            <x-slot name="heading">
                Generate Schedule
            </x-slot>

            <div class="space-y-4">
                <div class="flex items-start gap-4">
                    <!-- Step 1 -->
                    <div class="flex-1 bg-gray-50 dark:bg-gray-800 rounded-lg p-6 border-2 border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 font-bold">
                                1
                            </div>
                            <div>
                                <h3 class="font-semibold text-lg">Generate Time Slots</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Create 4 slots per day (excluding Fridays)</p>
                            </div>
                        </div>
                        
                        <x-filament::button 
                            wire:click="generateSlots"
                            color="primary"
                            size="lg"
                            class="w-full"
                        >
                            Generate Time Slots
                        </x-filament::button>
                    </div>

                    <!-- Step 2 -->
                    <div class="flex-1 bg-gray-50 dark:bg-gray-800 rounded-lg p-6 border-2 border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-300 font-bold">
                                2
                            </div>
                            <div>
                                <h3 class="font-semibold text-lg">Generate & Test Schedule</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Run optimization + automatic validation</p>
                            </div>
                        </div>
                        
                        <x-filament::button 
                            wire:click="generateSchedule"
                            color="success"
                            size="lg"
                            class="w-full"
                            :disabled="$isProcessing"
                        >
                            @if($isProcessing)
                                <span class="animate-spin mr-2">⏳</span>
                                Processing...
                            @else
                                Generate & Validate
                            @endif
                        </x-filament::button>
                    </div>
                </div>

                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <div class="flex gap-3">
                        <div class="text-blue-600 dark:text-blue-400">ℹ️</div>
                        <div class="text-sm text-blue-800 dark:text-blue-200">
                            <strong>Automatic Testing:</strong> After generating the schedule, a complete test suite will run automatically to validate all constraints and detect any issues.
                        </div>
                    </div>
                </div>
            </div>
        </x-filament::section>
        @endif

        <!-- Schedule Generation Output -->
        @if($output)
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center justify-between w-full">
                    <span>Schedule Generation Output</span>
                    <x-filament::button 
                        wire:click="$set('output', null)"
                        color="gray"
                        size="sm"
                    >
                        Clear
                    </x-filament::button>
                </div>
            </x-slot>

            <div class="bg-gray-900 text-green-400 rounded-lg p-4 font-mono text-sm overflow-x-auto max-h-96 overflow-y-auto">
                <pre>{{ $output }}</pre>
            </div>
        </x-filament::section>
        @endif

        <!-- Test Results Output (NEW) -->
        @if($showTests && $testOutput)
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center justify-between w-full">
                    <span>
                        🧪 Validation Test Results
                        @if(str_contains($testOutput, 'ALL TESTS PASSED'))
                            <span class="ml-2 text-green-600 dark:text-green-400">✅</span>
                        @elseif(str_contains($testOutput, 'CRITICAL'))
                            <span class="ml-2 text-red-600 dark:text-red-400">❌</span>
                        @else
                            <span class="ml-2 text-yellow-600 dark:text-yellow-400">⚠️</span>
                        @endif
                    </span>
                    <x-filament::button 
                        wire:click="$set('testOutput', null)"
                        color="gray"
                        size="sm"
                    >
                        Hide Tests
                    </x-filament::button>
                </div>
            </x-slot>

            <div class="bg-gray-900 text-cyan-400 rounded-lg p-4 font-mono text-sm overflow-x-auto max-h-[600px] overflow-y-auto">
                <pre>{{ $testOutput }}</pre>
            </div>
        </x-filament::section>
        @endif

    </div>
</x-filament-panels::page>
