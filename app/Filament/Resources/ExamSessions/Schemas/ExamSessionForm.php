<?php

namespace App\Filament\Resources\ExamSessions\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ExamSessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('semester_id')
                    ->relationship('semester', 'code') // Shows S1, S2
                    ->required(),

                Select::make('academic_year_id')
                    ->relationship('academicYear', 'code')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                DatePicker::make('starts_on')
                    ->required(),
                DatePicker::make('ends_on')
                    ->required(),
            ]);
    }
}
