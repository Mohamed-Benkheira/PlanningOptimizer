<?php

namespace App\Filament\Resources\ScheduledExamRooms\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ScheduledExamRoomForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('scheduled_exam_id')
                    ->relationship('scheduledExam', 'id')
                    ->required(),
                Select::make('room_id')
                    ->relationship('room', 'name')
                    ->required(),
                TextInput::make('seats_allocated')
                    ->required()
                    ->numeric(),
            ]);
    }
}
