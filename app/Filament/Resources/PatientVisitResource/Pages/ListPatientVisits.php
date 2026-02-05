<?php

namespace App\Filament\Resources\PatientVisitResource\Pages;

use App\Filament\Resources\PatientVisitResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPatientVisits extends ListRecords
{
    protected static string $resource = PatientVisitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
