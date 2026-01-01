<?php

namespace App\Filament\Resources\ScheduledExamRooms\Pages;

use App\Filament\Resources\ScheduledExamRooms\ScheduledExamRoomResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListScheduledExamRooms extends ListRecords
{
    protected static string $resource = ScheduledExamRoomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
