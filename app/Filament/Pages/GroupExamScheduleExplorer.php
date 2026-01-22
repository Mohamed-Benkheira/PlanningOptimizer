<?php

namespace App\Filament\Pages;

use App\Models\Department;
use App\Models\ExamSession;
use App\Models\Group;
use App\Models\Level;
use App\Models\ScheduledExam;
use App\Models\Specialty;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GroupExamScheduleExplorer extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'Group Schedule (Explorer)';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';
    protected string $view = 'filament.pages.group-exam-schedule-explorer';

    // THIS IS KEY: Use simple layout instead of Filament's default


    public ?int $exam_session_id = null;
    public ?int $department_id = null;
    public ?int $level_id = null;
    public ?int $specialty_id = null;
    public ?int $group_id = null;

    public function mount(): void
    {
        $this->exam_session_id = ExamSession::query()->latest('id')->value('id');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('exam_session_id')
                    ->label('Exam session')
                    ->options(fn() => ExamSession::query()
                        ->orderByDesc('id')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function () {
                        $this->department_id = null;
                        $this->level_id = null;
                        $this->specialty_id = null;
                        $this->group_id = null;
                    })
                    ->required(),

                Select::make('department_id')
                    ->label('Department')
                    ->options(fn() => Department::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function () {
                        $this->level_id = null;
                        $this->specialty_id = null;
                        $this->group_id = null;
                    })
                    ->required(),

                Select::make('level_id')
                    ->label('Level')
                    ->options(fn() => Level::query()
                        ->orderBy('year_number')
                        ->pluck('code', 'id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function () {
                        $this->specialty_id = null;
                        $this->group_id = null;
                    })
                    ->required(),

                Select::make('specialty_id')
                    ->label('Specialty')
                    ->options(function () {
                        if (!$this->level_id || !$this->department_id) {
                            return [];
                        }

                        return Specialty::query()
                            ->where('department_id', $this->department_id)
                            ->whereHas('groups', fn(Builder $q) => $q->where('level_id', $this->level_id))
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all();
                    })
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function () {
                        $this->group_id = null;
                    })
                    ->required(),

                Select::make('group_id')
                    ->label('Group')
                    ->options(function () {
                        if (!$this->level_id || !$this->specialty_id) {
                            return [];
                        }

                        return Group::query()
                            ->where('level_id', $this->level_id)
                            ->where('specialty_id', $this->specialty_id)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all();
                    })
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required(),
            ])
            ->columns(5);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                $q = ScheduledExam::query()
                    ->with([
                        'module:id,name',
                        'timeSlot:id,exam_session_id,exam_date,starts_at',
                        'rooms.room:id,name',
                    ]);

                if (!$this->exam_session_id || !$this->group_id) {
                    return $q->whereRaw('1=0');
                }

                return $q
                    ->where('exam_session_id', $this->exam_session_id)
                    ->where('group_id', $this->group_id);
            })
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
                    ->state(
                        fn(ScheduledExam $record): string =>
                        $record->rooms
                            ->map(fn($r) => $r->room->name . '(' . $r->seats_allocated . ')')
                            ->join(', ')
                    ),
            ])
            ->actions([
                \Filament\Actions\Action::make('view')
                    ->label('View')
                    ->modalHeading('Exam details')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(fn(ScheduledExam $record) => view(
                        'filament.pages.partials.exam-details',
                        [
                            'record' => $record,
                            'roomsHtml' => $record->rooms
                                ->map(fn($r) => $r->room->name . ' — ' . $r->seats_allocated . ' seats')
                                ->join('<br>'),
                        ]
                    )),
            ])
            ->defaultSort('timeSlot.exam_date')
            ->defaultSort('timeSlot.starts_at');
    }
}
