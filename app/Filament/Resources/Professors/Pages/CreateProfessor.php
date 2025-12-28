<?php

namespace App\Filament\Resources\Professors\Pages;

use App\Filament\Resources\Professors\ProfessorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProfessor extends CreateRecord
{
    protected static string $resource = ProfessorResource::class;
}
