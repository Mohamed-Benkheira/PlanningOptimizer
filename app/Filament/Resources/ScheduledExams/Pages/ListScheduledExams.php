<?php

namespace App\Filament\Resources\ScheduledExams\Pages;

use App\Filament\Resources\ScheduledExams\ScheduledExamResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListScheduledExams extends ListRecords
{
    protected static string $resource = ScheduledExamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
