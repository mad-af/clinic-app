<?php

namespace App\Filament\Resources\PatientVisitResource\Pages;

use App\Filament\Resources\PatientVisitResource;
use Filament\Actions;
use App\Models\Invoice;
use Filament\Resources\Pages\CreateRecord;

class CreatePatientVisit extends CreateRecord
{
    protected static string $resource = PatientVisitResource::class;

    protected function afterCreate(): void
    {
        $visit = $this->record;

        Invoice::create([
            'patient_visit_id' => $visit->id,
            'amount' => $visit->total_amount,
            'status' => 'Unpaid',
        ]);
    }
}
