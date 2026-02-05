<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PatientVisitResource\Pages;
use App\Models\PatientVisit;
use App\Models\Procedure;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PatientVisitResource extends Resource
{
    protected static ?string $model = PatientVisit::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('patient_id')
                    ->relationship('patient', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('doctor_id')
                    ->label('Doctor')
                    ->relationship('doctor', 'name', fn (Builder $query) => $query->where('role', 'doctor'))
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                        self::updateTotal($set, $get);
                    }),
                Forms\Components\Repeater::make('patientVisitProcedures')
                    ->relationship('patientVisitProcedures')
                    ->schema([
                        Forms\Components\Select::make('procedure_id')
                            ->relationship('procedure', 'name')
                            ->disableOptionWhen(function ($value, $state, Forms\Get $get) {
                                return collect($get('../../patientVisitProcedures'))
                                    ->pluck('procedure_id')
                                    ->filter()
                                    ->contains($value) && $value !== $state;
                            })
                            ->distinct()
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                $procedure = Procedure::find($state);
                                if ($procedure) {
                                    $set('price', $procedure->price);
                                }
                                // We need to trigger total update.
                                // Since this is inside a repeater, accessing the parent state to update 'total_amount' is tricky via direct call.
                                // But live() on repeater items re-evaluates the form.
                                // We can use 'live(onBlur: true)' or similar.
                                // However, simpler approach:
                                // Use a placeholder or just rely on the 'total_amount' being calculated.
                                // 'afterStateUpdated' inside repeater can call a method on the main form? No.
                                // We can use 'live()' on the repeater itself.
                            })
                            ->columnSpan(2),
                        Forms\Components\TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->readOnly() // Or editable? Usually snapshot is editable or fixed. Let's keep it editable but defaulted.
                            ->live()
                            ->columnSpan(1),
                    ])
                    ->columns(3)
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                        self::updateTotal($set, $get);
                    }),
                Forms\Components\TextInput::make('total_amount')
                    ->required()
                    ->readonly()
                    ->numeric()
                    ->prefix('Rp'),
            ]);
    }

    protected static function updateTotal(Forms\Set $set, Forms\Get $get)
    {
        $doctorId = $get('doctor_id');
        $procedures = $get('patientVisitProcedures') ?? [];

        $proceduresTotal = 0;
        foreach ($procedures as $item) {
            $proceduresTotal += (int) ($item['price'] ?? 0);
        }

        $doctorFee = 0;
        if ($doctorId) {
            $doctor = User::find($doctorId);
            if ($doctor) {
                $doctorFee = $doctor->service_fee;
            }
        }

        $set('total_amount', $proceduresTotal + $doctorFee);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('patient.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('doctor.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListPatientVisits::route('/'),
            'create' => Pages\CreatePatientVisit::route('/create'),
            'edit' => Pages\EditPatientVisit::route('/{record}/edit'),
        ];
    }
}
