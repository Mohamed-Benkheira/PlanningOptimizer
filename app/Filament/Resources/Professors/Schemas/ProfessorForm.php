<?php

namespace App\Filament\Resources\Professors\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProfessorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('first_name')
                    ->required(),
                TextInput::make('last_name')
                    ->required(),
                Select::make('department_id')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('groups')
                    ->relationship('groups', titleAttribute: 'name') // Keep this for saving
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->getOptionLabelFromRecordUsing(
                        fn($record) =>
                        // Customize this string to show whatever you want!
                        "{$record->name} - " . ($record->specialty->name ?? 'No Specialty') . " - " . ($record->level->code ?? 'No Specialty')
                    ),


                TextInput::make('grade'),
                TextInput::make('status')
                    ->required()
                    ->default('active'),
            ]);
    }
}
