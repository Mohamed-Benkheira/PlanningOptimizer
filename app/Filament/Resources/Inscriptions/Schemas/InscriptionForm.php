<?php

namespace App\Filament\Resources\Inscriptions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InscriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('student_id')
                    ->relationship('student', 'matricule') // Default search field
                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->last_name} {$record->first_name} ({$record->matricule})")
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('module_id')
                    ->relationship('module', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('exam_session_id')
                    ->relationship('examSession', 'name')
                    ->required(),
                TextInput::make('note')
                    ->numeric(),
                TextInput::make('status')
                    ->required()
                    ->default('enrolled'),
            ]);
    }
}
