<?php

namespace App\Filament\Resources\ScheduledExamRooms\Pages;

use App\Filament\Resources\ScheduledExamRooms\ScheduledExamRoomResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditScheduledExamRoom extends EditRecord
{
    protected static string $resource = ScheduledExamRoomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
