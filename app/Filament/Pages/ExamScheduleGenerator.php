<?php

namespace App\Filament\Pages;

use App\Models\ExamSession;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;
use UnitEnum;
use BackedEnum;

class ExamScheduleGenerator extends Page implements HasForms
{
    use InteractsWithForms;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected string $view = 'filament.pages.exam-schedule-generator';
    protected static ?string $navigationLabel = 'Generate Schedule';
    protected static UnitEnum|string|null $navigationGroup = 'Exams & Pedagogical';

    public ?array $data = [];
    public ?string $output = null;
    public ?string $testOutput = null; // 👈 NEW: Store test results separately
    public bool $isProcessing = false;
    public bool $showTests = false; // 👈 NEW: Control test visibility

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Select::make('exam_session_id')
                    ->label('Exam Session')
                    ->options(ExamSession::query()->pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->reactive()
                    ->helperText('Select the exam session to generate schedule for'),
            ])
            ->statePath('data');
    }

    public function generateSlots(): void
    {
        $this->validate();

        $examSessionId = $this->data['exam_session_id'];

        try {
            $output = new BufferedOutput();

            Artisan::call('exams:generate-slots', [
                'exam_session_id' => $examSessionId
            ], $output);

            $this->output = $output->fetch();

            Notification::make()
                ->success()
                ->title('Time Slots Generated')
                ->body('Time slots have been created successfully.')
                ->send();

            $this->dispatch('slots-generated');
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body($e->getMessage())
                ->send();
        }
    }

    public function generateSchedule(): void
    {
        $this->validate();

        $examSessionId = $this->data['exam_session_id'];
        $this->isProcessing = true;
        $this->testOutput = null; // Reset test output
        $this->showTests = false;

        try {
            // STEP 1: Generate Schedule
            $scheduleOutput = new BufferedOutput();

            $exitCode = Artisan::call('exams:generate-schedule', [
                'exam_session_id' => $examSessionId
            ], $scheduleOutput);

            $this->output = $scheduleOutput->fetch();

            // STEP 2: Run Tests Automatically (only if schedule succeeded)
            if ($exitCode === 0) {
                $this->runTests($examSessionId);

                Notification::make()
                    ->success()
                    ->title('Schedule Generated & Tested')
                    ->body('Exam schedule created and validated successfully!')
                    ->send();
            } else {
                Notification::make()
                    ->warning()
                    ->title('Scheduling Failed')
                    ->body('Could not place all exams. Check output below.')
                    ->send();
            }

            $this->isProcessing = false;
            $this->dispatch('schedule-generated');

        } catch (\Exception $e) {
            $this->isProcessing = false;

            Notification::make()
                ->danger()
                ->title('Error')
                ->body($e->getMessage())
                ->send();
        }
    }

    // 👇 NEW METHOD: Run tests automatically
    private function runTests(int $examSessionId): void
    {
        try {
            $testOutput = new BufferedOutput();

            $testExitCode = Artisan::call('exams:test', [
                'exam_session_id' => $examSessionId
            ], $testOutput);

            $this->testOutput = $testOutput->fetch();
            $this->showTests = true;

            // Parse test results for notification
            if ($testExitCode === 0) {
                if (str_contains($this->testOutput, 'CRITICAL FAILURES')) {
                    Notification::make()
                        ->danger()
                        ->title('Tests Failed')
                        ->body('Critical constraint violations detected!')
                        ->persistent()
                        ->send();
                } else {
                    Notification::make()
                        ->success()
                        ->title('All Tests Passed')
                        ->body('Schedule is production-ready!')
                        ->send();
                }
            } else {
                Notification::make()
                    ->warning()
                    ->title('Tests Completed with Warnings')
                    ->body('Schedule has optimization opportunities.')
                    ->send();
            }

        } catch (\Exception $e) {
            $this->testOutput = "Error running tests: " . $e->getMessage();
            $this->showTests = true;
        }
    }
}
