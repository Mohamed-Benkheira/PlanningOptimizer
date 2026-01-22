<?php

namespace App\Filament\Pages;

use App\Models\ScheduledExam;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use BackedEnum;
use UnitEnum;

class GroupExamSchedule extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected string $view = 'filament.pages.group-exam-schedule';
    protected static UnitEnum|string|null $navigationGroup = 'Exam Scheduling'; // Added 'static' here

    protected static ?string $navigationLabel = 'Group Exam Schedule';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ScheduledExam::query()
                    ->with([
                        'module:id,name',
                        'timeSlot:id,exam_date,starts_at',
                        'rooms.room:id,name',
                    ])
            )
            ->columns([
                TextColumn::make('timeSlot.exam_date')
                    ->label('Day')
                    ->date()
                    ->sortable(),

                TextColumn::make('timeSlot.starts_at')
                    ->label('Time')
                    ->sortable(),

                TextColumn::make('module.name')
                    ->label('Module')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('student_count')
                    ->label('Students')
                    ->sortable(),

                TextColumn::make('rooms_summary')
                    ->label('Rooms')
                    ->state(function (ScheduledExam $record): string {
                        return $record->rooms
                            ->map(fn($r) => $r->room->name . '(' . $r->seats_allocated . ')')
                            ->join(', ');
                    }),
            ])
            ->filters([
                SelectFilter::make('group_id')
                    ->label('Group')
                    ->relationship('group', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->defaultSort('timeSlot.exam_date')
            ->defaultSort('timeSlot.starts_at');
    }
}
