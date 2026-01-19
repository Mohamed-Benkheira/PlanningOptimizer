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
                TextColumn::make('semester.code')
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('starts_on')
                    ->date()
                    ->sortable(),
                TextColumn::make('ends_on')
                    ->date()
                    ->sortable(),
                TextColumn::make('id')->label('Session ID')->sortable(),

                TextColumn::make('approval_status')
                    ->label('Status')
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
                    ->label('Approved By')
                    ->placeholder('N/A'),

                TextColumn::make('approved_at')
                    ->label('Approved At')
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
                    ),

                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Schedule')
                    ->modalDescription('Are you sure you want to approve this exam session?')
                    ->action(function (ExamSession $record) {
                        $record->update([
                            'approval_status' => 'approved',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                            'rejection_reason' => null,
                        ]);

                        Notification::make()
                            ->title('Schedule Approved')
                            ->success()
                            ->send();
                    })
                    ->visible(
                        fn(ExamSession $record) =>
                        auth()->user() && auth()->user()->isDean() && $record->isPending()
                    ),

                Action::make('reject')
                    ->label('Reject')
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
                            ->title('Schedule Rejected')
                            ->danger()
                            ->send();
                    })
                    ->visible(
                        fn(ExamSession $record) =>
                        auth()->user() && auth()->user()->isDean() && $record->isPending()
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
