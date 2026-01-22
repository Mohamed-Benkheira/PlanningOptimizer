<?php

namespace App\Filament\Resources\ScheduledExamRooms;

use App\Filament\Resources\ScheduledExamRooms\Pages\CreateScheduledExamRoom;
use App\Filament\Resources\ScheduledExamRooms\Pages\EditScheduledExamRoom;
use App\Filament\Resources\ScheduledExamRooms\Pages\ListScheduledExamRooms;
use App\Filament\Resources\ScheduledExamRooms\Schemas\ScheduledExamRoomForm;
use App\Filament\Resources\ScheduledExamRooms\Tables\ScheduledExamRoomsTable;
use App\Models\ScheduledExamRoom;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;
class ScheduledExamRoomResource extends Resource
{
    protected static ?string $model = ScheduledExamRoom::class;
    protected static UnitEnum|string|null $navigationGroup = 'Exam Scheduling'; // Added 'static' here

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ScheduledExamRoomForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ScheduledExamRoomsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListScheduledExamRooms::route('/'),
            'create' => CreateScheduledExamRoom::route('/create'),
            'edit' => EditScheduledExamRoom::route('/{record}/edit'),
        ];
    }
}
