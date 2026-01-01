<?php

namespace App\Filament\Resources\TimeSlots\Pages;

use App\Filament\Resources\TimeSlots\TimeSlotResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTimeSlots extends ListRecords
{
    protected static string $resource = TimeSlotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
