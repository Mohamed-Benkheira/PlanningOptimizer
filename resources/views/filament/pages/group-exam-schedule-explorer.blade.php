<x-filament-panels::page>
    <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgb(17, 24, 39); z-index: 9999; overflow-y: auto; padding: 20px;">
        <div style="max-width: 1200px; margin: 0 auto; background: rgb(31, 41, 55); border: 1px solid rgb(55, 65, 81); border-radius: 12px; padding: 0; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);">
            
            <!-- Header -->
            <div style="padding: 32px 32px 24px 32px; border-bottom: 1px solid rgb(55, 65, 81);">
                <h1 style="font-size: 1.875rem; font-weight: 600; margin: 0; color: rgb(249, 250, 251); letter-spacing: -0.025em;">Exam Schedule</h1>
                <p style="margin: 6px 0 0 0; color: rgb(156, 163, 175); font-size: 0.875rem;">View and filter your upcoming exams</p>
            </div>

            <!-- Content -->
            <div style="padding: 32px;">
                
                <!-- Filters -->
                <div style="background: rgb(55, 65, 81); padding: 24px; border-radius: 8px; margin-bottom: 24px; border: 1px solid rgb(75, 85, 99);">
                    <h3 style="font-size: 0.875rem; font-weight: 600; margin-bottom: 20px; color: rgb(249, 250, 251); text-transform: uppercase; letter-spacing: 0.05em;">Filters</h3>
                    {{ $this->form }}
                </div>

                <!-- Results -->
                @if($this->group_id)
                <div style="background: rgb(31, 41, 55); border-radius: 8px; border: 1px solid rgb(55, 65, 81); overflow: hidden;">
                    <div style="padding: 16px 24px; border-bottom: 1px solid rgb(55, 65, 81);">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <h3 style="font-size: 0.875rem; font-weight: 600; margin: 0; color: rgb(249, 250, 251); text-transform: uppercase; letter-spacing: 0.05em;">Schedule</h3>
                            <span style="background: rgb(55, 65, 81); padding: 4px 12px; border-radius: 6px; font-size: 0.75rem; color: rgb(156, 163, 175); font-weight: 500;">
                                {{ \App\Models\ScheduledExam::where('exam_session_id', $this->exam_session_id)->where('group_id', $this->group_id)->count() }} exams
                            </span>
                        </div>
                    </div>
                    <div style="padding: 24px;">
                        {{ $this->table }}
                    </div>
                </div>
                @else
                <!-- Empty State -->
                <div style="text-align: center; padding: 64px 20px; background: rgb(55, 65, 81); border-radius: 8px; border: 1px dashed rgb(75, 85, 99);">
                    <div style="width: 48px; height: 48px; margin: 0 auto 16px; border-radius: 50%; background: rgb(75, 85, 99); display: flex; align-items: center; justify-content: center;">
                        <div style="width: 24px; height: 24px; border: 2px solid rgb(156, 163, 175); border-radius: 4px;"></div>
                    </div>
                    <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 8px; color: rgb(249, 250, 251);">No schedule selected</h3>
                    <p style="color: rgb(156, 163, 175); margin: 0; font-size: 0.875rem;">Select all filters above to view your exam schedule</p>
                </div>
                @endif

            </div>

        </div>
    </div>

    <style>
        /* Filament dark mode colors */
        .fi-fo-field-wrp label,
        .fi-fo-field-wrp-label {
            color: rgb(249, 250, 251) !important;
            font-size: 0.875rem !important;
            font-weight: 500 !important;
        }
        
        .fi-input,
        .fi-select select {
            background: rgb(31, 41, 55) !important;
            border: 1px solid rgb(55, 65, 81) !important;
            color: rgb(249, 250, 251) !important;
            border-radius: 6px !important;
        }
        
        .fi-input:focus,
        .fi-select select:focus {
            border-color: rgb(99, 102, 241) !important;
            box-shadow: 0 0 0 1px rgb(99, 102, 241) !important;
        }
        
        .fi-ta-table {
            background: transparent !important;
            border: none !important;
        }
        
        .fi-ta-header-cell {
            background: rgb(55, 65, 81) !important;
            color: rgb(156, 163, 175) !important;
            font-size: 0.75rem !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
            border-bottom: 1px solid rgb(75, 85, 99) !important;
        }
        
        .fi-ta-row {
            border-bottom: 1px solid rgb(55, 65, 81) !important;
        }
        
        .fi-ta-row:hover {
            background: rgb(55, 65, 81) !important;
        }
        
        .fi-ta-cell {
            color: rgb(249, 250, 251) !important;
            font-size: 0.875rem !important;
        }
    </style>
</x-filament-panels::page>
