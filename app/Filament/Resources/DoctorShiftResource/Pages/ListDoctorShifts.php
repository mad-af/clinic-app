<?php

namespace App\Filament\Resources\DoctorShiftResource\Pages;

use App\Filament\Resources\DoctorShiftResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDoctorShifts extends ListRecords
{
    protected static string $resource = DoctorShiftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
