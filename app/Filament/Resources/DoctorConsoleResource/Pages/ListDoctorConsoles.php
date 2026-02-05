<?php

namespace App\Filament\Resources\DoctorConsoleResource\Pages;

use App\Filament\Resources\DoctorConsoleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDoctorConsoles extends ListRecords
{
    protected static string $resource = DoctorConsoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
