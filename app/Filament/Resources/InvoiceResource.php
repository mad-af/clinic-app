<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Invoice Details')
                    ->schema([
                        Forms\Components\Placeholder::make('patient')
                            ->label('Patient')
                            ->content(fn (Invoice $record) => $record->patientVisit->patient->name ?? '-'),

                        Forms\Components\Placeholder::make('doctor')
                            ->label('Doctor')
                            ->content(fn (Invoice $record) => ($record->patientVisit->doctor->name ?? '-').' (Fee: '.number_format($record->patientVisit->doctor->service_fee ?? 0, 0, ',', '.').')'),

                        Forms\Components\Placeholder::make('items_list')
                            ->label('Invoice Items')
                            ->content(function (Invoice $record) {
                                if (! $record->patientVisit) {
                                    return '-';
                                }

                                $html = '<div class="space-y-2">';

                                // Procedures
                                $procedures = $record->patientVisit->patientVisitProcedures;
                                if ($procedures->count() > 0) {
                                    $html .= '<strong>Procedures:</strong><ul class="list-disc pl-4">';
                                    foreach ($procedures as $proc) {
                                        $html .= '<li>'.($proc->procedure->name ?? 'Unknown').' - Rp '.number_format($proc->price, 0, ',', '.').'</li>';
                                    }
                                    $html .= '</ul>';
                                }

                                // Medicines
                                $medicalRecord = $record->patientVisit->medicalRecord;
                                if ($medicalRecord && $medicalRecord->items->count() > 0) {
                                    $html .= '<strong>Medicines:</strong><ul class="list-disc pl-4">';
                                    foreach ($medicalRecord->items as $item) {
                                        $medicineName = $item->medicine->name ?? 'Unknown';
                                        $price = $item->price * $item->quantity;
                                        $html .= '<li>'.$medicineName.' ('.$item->quantity.'x) - Rp '.number_format($price, 0, ',', '.').'</li>';
                                    }
                                    $html .= '</ul>';
                                }

                                $html .= '</div>';

                                return new \Illuminate\Support\HtmlString($html);
                            }),

                        Forms\Components\TextInput::make('amount')
                            ->label('Total Amount')
                            ->prefix('Rp')
                            ->numeric()
                            ->readOnly(),

                        Forms\Components\Select::make('status')
                            ->options([
                                'Unpaid' => 'Unpaid',
                                'Paid' => 'Paid',
                            ])
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Invoice ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('patientVisit.patient.name')
                    ->label('Patient')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Paid' => 'success',
                        'Unpaid' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('mark_paid')
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
            ])
            ->bulkActions([
                // Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
