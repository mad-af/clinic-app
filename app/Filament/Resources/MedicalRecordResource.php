<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MedicalRecordResource\Pages;
use App\Models\MedicalRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MedicalRecordResource extends Resource
{
    protected static ?string $model = MedicalRecord::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('patient_id')
                    ->relationship('patient', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\DatePicker::make('date')
                    ->required()
                    ->default(now()),
                Forms\Components\Textarea::make('diagnosis')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),

                Forms\Components\Section::make('Prescribed Medicines')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->schema([
                                Forms\Components\Select::make('medicine_id')
                                    ->relationship('medicine', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        // Just to trigger update on quantity helper text,
                                        // though live() should handle it via dependent get() calls
                                    }),
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
                            ->defaultItems(0)
                            ->addActionLabel('Add Medicine'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
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
            'index' => Pages\ListMedicalRecords::route('/'),
            'create' => Pages\CreateMedicalRecord::route('/create'),
            'edit' => Pages\EditMedicalRecord::route('/{record}/edit'),
        ];
    }
}
