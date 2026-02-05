<?php

namespace App\Filament\Resources\MedicalRecordResource\Pages;

use App\Filament\Resources\MedicalRecordResource;
use App\Models\Medicine;
use App\Models\StockLog;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditMedicalRecord extends EditRecord
{
    protected static string $resource = MedicalRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['items'] = $this->record->items->map(function ($item) {
            return [
                'medicine_id' => $item->medicine_id,
                'quantity' => $item->quantity,
            ];
        })->toArray();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            $record->update($data);

            // Refund stock for existing items
            foreach ($record->items()->get() as $item) {
                $medicine = Medicine::lockForUpdate()->find($item->medicine_id);
                if ($medicine) {
                    $oldStock = $medicine->stock;
                    $medicine->increment('stock', $item->quantity);
                    $newStock = $medicine->stock;

                    // Log stock refund
                    StockLog::create([
                        'medicine_id' => $medicine->id,
                        'action' => 'refund',
                        'quantity' => $item->quantity,
                        'old_stock' => $oldStock,
                        'new_stock' => $newStock,
                        'user_id' => auth()->id(),
                        'reason' => 'Refunded from Medical Record',
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                    ]);
                }
                $item->delete();
            }

            // Deduct stock for new items
            foreach ($items as $item) {
                $medicine = Medicine::lockForUpdate()->find($item['medicine_id']);

                if (! $medicine) {
                    throw new \Exception('Medicine not found');
                }

                if ($medicine->stock < $item['quantity']) {
                    throw new \Exception("Insufficient stock for medicine: {$medicine->name}");
                }

                $oldStock = $medicine->stock;
                $medicine->decrement('stock', $item['quantity']);
                $newStock = $medicine->stock;

                // Log stock deduction
                StockLog::create([
                    'medicine_id' => $medicine->id,
                    'action' => 'deduction',
                    'quantity' => $item['quantity'],
                    'old_stock' => $oldStock,
                    'new_stock' => $newStock,
                    'user_id' => auth()->id(),
                    'reason' => 'Used in Medical Record',
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);

                $record->items()->create([
                    'medicine_id' => $item['medicine_id'],
                    'quantity' => $item['quantity'],
                    'price' => 0,
                ]);
            }

            return $record;
        });
    }
}
