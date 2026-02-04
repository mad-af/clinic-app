<?php

namespace App\Filament\Resources\MedicalRecordResource\Pages;

use App\Filament\Resources\MedicalRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\Medicine;

class CreateMedicalRecord extends CreateRecord
{
    protected static string $resource = MedicalRecordResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            $record = static::getModel()::create($data);

            foreach ($items as $item) {
                $medicine = Medicine::lockForUpdate()->find($item['medicine_id']);

                if ($medicine->stock < $item['quantity']) {
                    throw new \Exception("Insufficient stock for medicine: {$medicine->name}");
                }

                $medicine->decrement('stock', $item['quantity']);

                $record->items()->create([
                    'medicine_id' => $item['medicine_id'],
                    'quantity' => $item['quantity'],
                ]);
            }

            return $record;
        });
    }
}
