<?php

namespace App\Filament\Resources\Departments\RelationManagers;

use App\Filament\Resources\Professors\ProfessorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class ProfessorsRelationManager extends RelationManager
{
    protected static string $relationship = 'professors';

    protected static ?string $relatedResource = ProfessorResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
