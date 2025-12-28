<?php

namespace App\Filament\Resources\Professors;

use App\Filament\Resources\Professors\Pages\CreateProfessor;
use App\Filament\Resources\Professors\Pages\EditProfessor;
use App\Filament\Resources\Professors\Pages\ListProfessors;
use App\Filament\Resources\Professors\Schemas\ProfessorForm;
use App\Filament\Resources\Professors\Tables\ProfessorsTable;
use App\Models\Professor;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ProfessorResource extends Resource
{
    protected static string|UnitEnum|null $navigationGroup = 'Management';

    protected static ?string $model = Professor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ProfessorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProfessorsTable::configure($table);
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
            'index' => ListProfessors::route('/'),
            'create' => CreateProfessor::route('/create'),
            'edit' => EditProfessor::route('/{record}/edit'),
        ];
    }
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user->isDepartmentHead()) {
            // Correct Scoping: Department -> Professor
            return $query->where('department_id', $user->department_id);
        }

        return $query;
    }

}
