<?php

namespace App\Filament\Resources\ScheduledExams;

use App\Filament\Resources\ScheduledExams\Pages\CreateScheduledExam;
use App\Filament\Resources\ScheduledExams\Pages\EditScheduledExam;
use App\Filament\Resources\ScheduledExams\Pages\ListScheduledExams;
use App\Filament\Resources\ScheduledExams\Schemas\ScheduledExamForm;
use App\Filament\Resources\ScheduledExams\Tables\ScheduledExamsTable;
use App\Models\ScheduledExam;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ScheduledExamResource extends Resource
{
    protected static ?string $model = ScheduledExam::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ScheduledExamForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ScheduledExamsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    public static function getEloquentQuery(): Builder
    {
        // Get the standard query
        $query = parent::getEloquentQuery();

        /** @var \App\Models\User $user */
        $user = auth()->user();

        // PDF Requirement: "Chef de département... Validation par département"
        // If user is a Department Head, filter exams by their department ID
        if ($user->isDepartmentHead()) {
            return $query->whereHas('module', function (Builder $q) use ($user) {
                $q->where('department_id', $user->department_id);
            });
        }

        // Dean and Exam Admin see everything ("Vue stratégique globale")
        return $query;
    }



    public static function getPages(): array
    {
        return [
            'index' => ListScheduledExams::route('/'),
            'create' => CreateScheduledExam::route('/create'),
            'edit' => EditScheduledExam::route('/{record}/edit'),
        ];
    }
}
