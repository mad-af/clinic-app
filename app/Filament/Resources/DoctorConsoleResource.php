<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DoctorConsoleResource\Pages;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DoctorConsoleResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static ?string $navigationLabel = 'Doctor Console';

    protected static ?string $pluralModelLabel = 'Doctor Console';

    protected static ?string $modelLabel = 'Patient Queue';

    protected static ?string $slug = 'doctor-console';

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('patient.name')
                    ->label('Patient')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('appointment_time')
                    ->label('Appointment Time')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'confirmed' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('start_examination')
                    ->label('Start Examination')
                    ->icon('heroicon-m-pencil-square')
                    ->color('primary')
                    ->modalHeading('Examination Form')
                    ->form([
                        Forms\Components\Textarea::make('diagnosis')
                            ->required()
                            ->rows(3),
                        Forms\Components\Textarea::make('notes')
                            ->label('Medical Notes')
                            ->rows(3),
                        Forms\Components\Section::make('Prescribed Medicines')
                            ->schema([
                                Forms\Components\Repeater::make('items')
                                    ->schema([
                                        Forms\Components\Select::make('medicine_id')
                                            ->label('Medicine')
                                            ->options(\App\Models\Medicine::pluck('name', 'id'))
                                            ->required()
                                            ->searchable()
                                            ->live()
                                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                            ->rule('distinct'),
                                        Forms\Components\TextInput::make('quantity')
                                            ->numeric()
                                            ->required()
                                            ->default(1)
                                            ->minValue(1)
                                            ->helperText(function (Forms\Get $get) {
                                                $medicineId = $get('medicine_id');
                                                if (! $medicineId) {
                                                    return 'Select a medicine to see available stock';
                                                }
                                                $stock = \App\Models\Medicine::find($medicineId)?->stock ?? 0;

                                                return 'Available stock: '.$stock;
                                            })
                                            ->rules([
                                                fn (Forms\Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                                    $medicineId = $get('medicine_id');
                                                    if (! $medicineId) {
                                                        return;
                                                    }
                                                    $stock = \App\Models\Medicine::find($medicineId)?->stock ?? 0;
                                                    if ($value > $stock) {
                                                        $fail("Quantity exceeds available stock ($stock).");
                                                    }
                                                },
                                            ]),
                                    ])
                                    ->columns(2)
                                    ->defaultItems(0),
                            ]),
                    ])
                    ->action(function (Appointment $record, array $data) {
                        \Illuminate\Support\Facades\DB::transaction(function () use ($record, $data) {
                            // Create Patient Visit
                            $visit = \App\Models\PatientVisit::create([
                                'patient_id' => $record->patient_id,
                                'doctor_id' => $record->doctor_id,
                                'total_amount' => 0,
                            ]);

                            // Create Medical Record
                            $medicalRecord = MedicalRecord::create([
                                'patient_visit_id' => $visit->id,
                                'patient_id' => $record->patient_id,
                                'date' => now(),
                                'diagnosis' => $data['diagnosis'],
                                'notes' => $data['notes'],
                            ]);

                            // Process Medicines
                            if (isset($data['items']) && is_array($data['items'])) {
                                foreach ($data['items'] as $itemData) {
                                    $medicine = \App\Models\Medicine::lockForUpdate()->find($itemData['medicine_id']);

                                    if ($medicine && $medicine->stock >= $itemData['quantity']) {
                                        // Deduct Stock
                                        $oldStock = $medicine->stock;
                                        $medicine->decrement('stock', $itemData['quantity']);
                                        $newStock = $medicine->stock;

                                        // Create Stock Log
                                        \App\Models\StockLog::create([
                                            'medicine_id' => $medicine->id,
                                            'user_id' => \Illuminate\Support\Facades\Auth::id(),
                                            'action' => 'deduction',
                                            'quantity' => $itemData['quantity'],
                                            'old_stock' => $oldStock,
                                            'new_stock' => $newStock,
                                            'reason' => 'Used in Medical Record #'.$medicalRecord->id,
                                            'ip_address' => request()->ip(),
                                            'user_agent' => request()->userAgent(),
                                        ]);

                                        // Create Medical Record Item
                                        $medicalRecord->items()->create([
                                            'medicine_id' => $medicine->id,
                                            'quantity' => $itemData['quantity'],
                                            'price' => $medicine->price,
                                        ]);
                                    }
                                }
                            }

                            // Recalculate Total
                            $visit->recalculateTotal();

                            // Update Appointment Status
                            $record->update(['status' => 'completed']);
                        });
                    })
                    ->successNotificationTitle('Examination finished successfully'),
            ])
            ->bulkActions([
                // No bulk actions needed for queue
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('status', ['pending', 'confirmed']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDoctorConsoles::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
