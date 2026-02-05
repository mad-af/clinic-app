<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Models\Invoice;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('mark_paid')
                ->label('Mark as Paid')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Confirm Payment')
                ->modalDescription('Are you sure you want to mark this invoice as paid?')
                ->modalSubmitActionLabel('Yes, Paid')
                ->action(function (Invoice $record) {
                    $record->update(['status' => 'Paid']);
                })
                ->visible(fn (Invoice $record) => $record->status === 'Unpaid'),
        ];
    }
}
