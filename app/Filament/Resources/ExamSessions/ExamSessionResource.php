<?php

namespace App\Filament\Resources\ExamSessions;

use App\Filament\Resources\ExamSessions\Pages\CreateExamSession;
use App\Filament\Resources\ExamSessions\Pages\EditExamSession;
use App\Filament\Resources\ExamSessions\Pages\ListExamSessions;
use App\Filament\Resources\ExamSessions\Schemas\ExamSessionForm;
use App\Filament\Resources\ExamSessions\Tables\ExamSessionsTable;
use App\Models\ExamSession;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;


class ExamSessionResource extends Resource
{
    protected static string|UnitEnum|null $navigationGroup = 'Exams & Pedagogical';

    protected static ?string $model = ExamSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ExamSessionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExamSessionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExamSessions::route('/'),
            'create' => CreateExamSession::route('/create'),
            'edit' => EditExamSession::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user->isDepartmentHead()) {
            // Correct Scoping: Department -> Specialty -> Module -> ExamSession
            return $query->whereHas('module.specialty', function ($q) use ($user) {
                $q->where('department_id', $user->department_id);
            });
        }

        return $query;
    }

}
