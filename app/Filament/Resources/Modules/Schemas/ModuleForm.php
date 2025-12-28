<?php

namespace App\Filament\Resources\Modules\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ModuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('code')
                    ->required(),
                Select::make('specialty_id')
                    ->relationship('specialty', 'name')
                    ->searchable()
                    ->preload(),

                Select::make('level_id')
                    ->relationship('level', 'code')
                    ->preload(),
                TextInput::make('semester')
                    ->required()
                    ->numeric(),
                TextInput::make('credits')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
