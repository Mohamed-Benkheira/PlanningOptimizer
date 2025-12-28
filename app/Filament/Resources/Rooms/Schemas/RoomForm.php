<?php

namespace App\Filament\Resources\Rooms\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class RoomForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('code'),
                TextInput::make('capacity')
                    ->required()
                    ->numeric(),
                TextInput::make('type'),
                TextInput::make('building'),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
