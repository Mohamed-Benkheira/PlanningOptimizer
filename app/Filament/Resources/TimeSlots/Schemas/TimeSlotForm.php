<?php

namespace App\Filament\Resources\TimeSlots\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;

class TimeSlotForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('exam_session_id')
                    ->relationship('examSession', 'name')
                    ->required(),
                DatePicker::make('exam_date')
                    ->required(),
                TextInput::make('slot_index')
                    ->required()
                    ->numeric(),
                TimePicker::make('starts_at')
                    ->required(),
                TimePicker::make('ends_at'),
            ]);
    }
}
