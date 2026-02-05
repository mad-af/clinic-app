<?php

namespace App\Filament\Resources\MedicineResource\Pages;

use App\Filament\Resources\MedicineResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

use App\Models\StockLog;
use Illuminate\Database\Eloquent\Model;

class EditMedicine extends EditRecord
{
    protected static string $resource = MedicineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $oldStock = $record->stock;
        
        $record->update($data);
        
        $newStock = $record->stock;

        // Log stock change if any
        if ($oldStock !== $newStock) {
            StockLog::create([
                'medicine_id' => $record->id,
                'old_stock' => $oldStock,
                'new_stock' => $newStock,
                'employee_id' => auth()->id(),
                'ip_address' => request()->ip(),
            ]);
        }

        return $record;
    }
}
