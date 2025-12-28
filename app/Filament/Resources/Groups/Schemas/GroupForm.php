<?php

namespace App\Filament\Resources\Groups\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class GroupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                Select::make('specialty_id')
                    ->relationship('specialty', 'name') // Shows "Génie Logiciel"
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('level_id')
                    ->relationship('level', 'code') // Shows "L3", "M1"
                    ->preload()
                    ->required(),

                Select::make('academic_year_id')
                    ->relationship('academicYear', 'code') // Shows "2025-2026"
                    ->default(fn() => \App\Models\AcademicYear::where('is_active', true)->first()?->id) // Auto-select active year
                    ->required(),
                TextInput::make('capacity')
                    ->required()
                    ->numeric()
                    ->default(20),
            ]);
    }
}
