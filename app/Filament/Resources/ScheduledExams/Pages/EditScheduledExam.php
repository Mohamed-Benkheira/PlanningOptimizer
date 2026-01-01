<?php

namespace App\Filament\Resources\ScheduledExams\Pages;

use App\Filament\Resources\ScheduledExams\ScheduledExamResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditScheduledExam extends EditRecord
{
    protected static string $resource = ScheduledExamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
