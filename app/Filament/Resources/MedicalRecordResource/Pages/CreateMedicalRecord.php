<?php

namespace App\Filament\Resources\MedicalRecordResource\Pages;

use App\Filament\Resources\MedicalRecordResource;
use App\Models\Medicine;
use App\Models\StockLog;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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

                $oldStock = $medicine->stock;
                $medicine->decrement('stock', $item['quantity']);
                $newStock = $medicine->stock;

                // Log stock deduction
                StockLog::create([
                    'medicine_id' => $medicine->id,
                    'old_stock' => $oldStock,
                    'new_stock' => $newStock,
                    'employee_id' => auth()->id(),
                    'ip_address' => request()->ip(),
                ]);

                $record->items()->create([
                    'medicine_id' => $item['medicine_id'],
                    'quantity' => $item['quantity'],
                    'price' => $medicine->price,
                ]);
            }

            if ($record->patientVisit) {
                $record->patientVisit->recalculateTotal();
            }

            return $record;
        });
    }
}
