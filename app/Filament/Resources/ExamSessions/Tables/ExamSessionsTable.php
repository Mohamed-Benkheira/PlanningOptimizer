<?php

namespace App\Filament\Resources\ExamSessions\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use App\Models\ExamSession;
use Filament\Forms;
use Illuminate\Database\Eloquent\Collection;

class ExamSessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('academicYear.code')
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('starts_on')
                    ->date()
                    ->sortable(),
                TextColumn::make('ends_on')
                    ->date()
                    ->sortable(),
                TextColumn::make('id')
                    ->label('Session ID')
                    ->sortable(),

                // Department Head Approval Status
                TextColumn::make('dept_approval_status')
                    ->label('Dept. Status')
                    ->badge()
                    ->color(fn(?string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn(?string $state): string => match ($state) {
                        'pending' => 'heroicon-o-clock',
                        'approved' => 'heroicon-o-check-circle',
                        'rejected' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle',
                    }),

                TextColumn::make('deptApprover.name')
                    ->label('Dept. Approved By')
                    ->placeholder('N/A'),

                // Dean Approval Status
                TextColumn::make('approval_status')
                    ->label('Dean Status')
                    ->badge()
                    ->color(fn(?string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn(?string $state): string => match ($state) {
                        'pending' => 'heroicon-o-clock',
                        'approved' => 'heroicon-o-check-circle',
                        'rejected' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle',
                    }),

                TextColumn::make('approver.name')
                    ->label('Dean Approved By')
                    ->placeholder('N/A'),

                TextColumn::make('approved_at')
                    ->label('Dean Approved At')
                    ->dateTime()
                    ->placeholder('N/A'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->url(
                        fn(ExamSession $record): string =>
                        route('filament.admin.resources.exam-sessions.edit', ['record' => $record->id])
                    )
                    ->visible(fn(ExamSession $record) => !$record->isFullyApproved()),

                // DEPARTMENT HEAD ACTIONS
                Action::make('dept_approve')
                    ->label('Dept. Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Schedule (Department)')
                    ->modalDescription('Are you sure you want to approve this exam session? It will be sent to the Dean for final approval.')
                    ->action(function (ExamSession $record) {
                        $record->update([
                            'dept_approval_status' => 'approved',
                            'dept_approved_by' => auth()->id(),
                            'dept_approved_at' => now(),
                            'dept_rejection_reason' => null,
                        ]);

                        Notification::make()
                            ->title('Schedule Approved by Department')
                            ->body('The schedule has been sent to the Dean for final approval.')
                            ->success()
                            ->send();
                    })
                    ->visible(
                        fn(ExamSession $record) =>
                        auth()->user() &&
                        auth()->user()->isDepartmentHead() &&
                        $record->isDeptPending()
                    ),

                Action::make('dept_reject')
                    ->label('Dept. Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('dept_rejection_reason')
                            ->label('Reason for Rejection')
                            ->required()
                            ->rows(3)
                            ->placeholder('Explain why this schedule is being rejected...'),
                    ])
                    ->action(function (ExamSession $record, array $data) {
                        $record->update([
                            'dept_approval_status' => 'rejected',
                            'dept_approved_by' => auth()->id(),
                            'dept_approved_at' => now(),
                            'dept_rejection_reason' => $data['dept_rejection_reason'],
                        ]);

                        Notification::make()
                            ->title('Schedule Rejected by Department')
                            ->body($data['dept_rejection_reason'])
                            ->danger()
                            ->send();
                    })
                    ->visible(
                        fn(ExamSession $record) =>
                        auth()->user() &&
                        auth()->user()->isDepartmentHead() &&
                        $record->isDeptPending()
                    ),

                // DEAN ACTIONS
                Action::make('approve')
                    ->label('Dean Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Final Approval (Dean)')
                    ->modalDescription('Are you sure you want to give final approval? The schedule will be visible to students and professors.')
                    ->action(function (ExamSession $record) {
                        $record->update([
                            'approval_status' => 'approved',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                            'rejection_reason' => null,
                        ]);

                        Notification::make()
                            ->title('Schedule Fully Approved')
                            ->body('The schedule is now visible to students and professors.')
                            ->success()
                            ->send();
                    })
                    ->visible(
                        fn(ExamSession $record) =>
                        auth()->user() &&
                        auth()->user()->isDean() &&
                        $record->isReadyForDeanApproval()
                    ),

                Action::make('reject')
                    ->label('Dean Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Reason for Rejection')
                            ->required()
                            ->rows(3)
                            ->placeholder('Explain why this schedule is being rejected...'),
                    ])
                    ->action(function (ExamSession $record, array $data) {
                        $record->update([
                            'approval_status' => 'rejected',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                            'rejection_reason' => $data['rejection_reason'],
                        ]);

                        Notification::make()
                            ->title('Schedule Rejected by Dean')
                            ->body($data['rejection_reason'])
                            ->danger()
                            ->send();
                    })
                    ->visible(
                        fn(ExamSession $record) =>
                        auth()->user() &&
                        auth()->user()->isDean() &&
                        $record->isReadyForDeanApproval()
                    ),

                // VIEW REJECTION REASON
                Action::make('view_rejection')
                    ->label('View Rejection')
                    ->icon('heroicon-o-information-circle')
                    ->color('warning')
                    ->modalHeading('Rejection Details')
                    ->modalContent(fn(ExamSession $record) => view('filament.modals.rejection-details', [
                        'dept_reason' => $record->dept_rejection_reason,
                        'dean_reason' => $record->rejection_reason,
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->visible(
                        fn(ExamSession $record) =>
                        $record->isDeptRejected() || $record->isRejected()
                    ),
            ])
            ->filters([
                //
            ])
            ->toolbarActions([
                BulkAction::make('delete')
                    ->label('Delete Selected')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->action(fn(Collection $records) => $records->each->delete())
                    ->deselectRecordsAfterCompletion(),
            ]);
    }
}
