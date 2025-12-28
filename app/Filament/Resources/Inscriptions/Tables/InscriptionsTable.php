<?php

namespace App\Filament\Resources\Inscriptions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.matricule')
                    ->label('Matricule')
                    ->searchable(),
                TextColumn::make('student.last_name')
                    ->label('Student')
                    ->formatStateUsing(fn($record) => $record->student->last_name . ' ' . $record->student->first_name)
                    ->searchable(),
                TextColumn::make('module.name')
                    ->label('Module')
                    ->searchable(),
                TextColumn::make('examSession.name')
                    ->label('Session')
                    ->limit(20),
                TextColumn::make('note')
                    ->label('Grade')
                    ->placeholder('-'),
                TextColumn::make('status')
                    ->badge(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
