<?php

namespace App\Filament\Resources\MedicineResource\Pages;

use App\Filament\Resources\MedicineResource;
use App\Models\StockLog;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateMedicine extends CreateRecord
{
    protected static string $resource = MedicineResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $record = parent::handleRecordCreation($data);

        // Log initial stock
        if (isset($data['stock']) && $data['stock'] > 0) {
            StockLog::create([
                'medicine_id' => $record->id,
                'old_stock' => 0,
                'new_stock' => $data['stock'],
                'employee_id' => auth()->id(),
                'ip_address' => request()->ip(),
            ]);
        }

        return $record;
    }
}
