<?php

namespace App\Filament\Resources\StockAdjustmentResource\Pages;

use App\Filament\Resources\StockAdjustmentResource;
use App\Models\Medicine;
use App\Models\StockLog;
use Filament\Actions;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateStockAdjustment extends CreateRecord
{
    protected static string $resource = StockAdjustmentResource::class;

    protected function getFormActions(): array
    {
        return [
            $this->getVerifyAndSaveAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function getVerifyAndSaveAction(): Actions\Action
    {
        return Actions\Action::make('verifyAndSave')
            ->label('Verify & Save')
            ->color('primary')
            ->requiresConfirmation(function () {
                $amount = (int) $this->data['adjustment_amount'];

                return abs($amount) > 100;
            })
            ->modalHeading('High-Risk Adjustment Verification')
            ->modalDescription('This adjustment exceeds 100 units. Please confirm your password to proceed.')
            ->modalSubmitActionLabel('Verify & Save')
            ->form(function () {
                $amount = (int) $this->data['adjustment_amount'];
                if (abs($amount) > 100) {
                    return [
                        TextInput::make('password')
                            ->password()
                            ->label('Confirm Password')
                            ->required()
                            ->rule(function () {
                                return function (string $attribute, $value, \Closure $fail) {
                                    if (! Hash::check($value, auth()->user()->password)) {
                                        $fail('Incorrect password.');
                                    }
                                };
                            }),
                    ];
                }

                return [];
            })
            ->action(function (array $data) {
                $this->processAdjustment();
            });
    }

    protected function processAdjustment()
    {
        // Manual validation if needed, but Filament form handles basic validation
        // We need to validate the form first
        try {
            $this->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Stop if form is invalid
            $this->form->validate(); // This throws exception and shows errors on form

            return;
        }

        $data = $this->data;

        DB::transaction(function () use ($data) {
            $medicine = Medicine::lockForUpdate()->find($data['medicine_id']);
            $oldStock = $medicine->stock;
            $adjustmentAmount = (int) $data['adjustment_amount'];
            $newStock = $oldStock + $adjustmentAmount;

            // Update Medicine Stock
            $medicine->stock = $newStock;
            $medicine->save();

            // Create Stock Log
            StockLog::create([
                'medicine_id' => $medicine->id,
                'action' => 'adjustment',
                'quantity' => abs($adjustmentAmount),
                'old_stock' => $oldStock,
                'new_stock' => $newStock,
                'user_id' => auth()->id(),
                'reason' => $data['notes'] ?? 'Manual adjustment',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Create Stock Adjustment Record (The main resource record)
            $this->record = static::getModel()::create($data);

            // Filament lifecycle hooks
            // $this->afterCreate();
        });

        Notification::make()
            ->title('Stock adjusted successfully')
            ->success()
            ->send();

        $this->redirect($this->getResource()::getUrl('index'));
    }
}
