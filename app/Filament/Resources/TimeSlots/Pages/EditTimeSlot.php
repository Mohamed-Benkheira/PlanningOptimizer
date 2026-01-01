<?php

namespace App\Filament\Resources\TimeSlots\Pages;

use App\Filament\Resources\TimeSlots\TimeSlotResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTimeSlot extends EditRecord
{
    protected static string $resource = TimeSlotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
