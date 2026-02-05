<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DoctorShiftResource\Pages;
use App\Models\DoctorShift;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DoctorShiftResource extends Resource
{
    protected static ?string $model = DoctorShift::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Master Data';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('doctor_id')
                    ->relationship('doctor', 'name', fn (Builder $query) => $query->where('role', 'doctor'))
                    ->required(),
                Forms\Components\Select::make('day_of_week')
                    ->options([
                        0 => 'Sunday',
                        1 => 'Monday',
                        2 => 'Tuesday',
                        3 => 'Wednesday',
                        4 => 'Thursday',
                        5 => 'Friday',
                        6 => 'Saturday',
                    ])
                    ->required()
                    ->rule(function (Forms\Get $get, $record) {
                        return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                            $doctorId = $get('doctor_id');
                            if (! $doctorId) {
                                return;
                            }

                            $query = \App\Models\DoctorShift::where('doctor_id', $doctorId)
                                ->where('day_of_week', $value);

                            if ($record) {
                                $query->where('id', '!=', $record->id);
                            }

                            if ($query->exists()) {
                                $fail('This doctor already has a shift on this day.');
                            }
                        };
                    }),
                Forms\Components\TimePicker::make('start_time')
                    ->required()
                    ->seconds(false),
                Forms\Components\TimePicker::make('end_time')
                    ->required()
                    ->seconds(false)
                    ->after('start_time'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('doctor.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('day_of_week')
                    ->formatStateUsing(fn (int $state): string => match ($state) {
                        0 => 'Sunday',
                        1 => 'Monday',
                        2 => 'Tuesday',
                        3 => 'Wednesday',
                        4 => 'Thursday',
                        5 => 'Friday',
                        6 => 'Saturday',
                        default => 'Unknown',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_time'),
                Tables\Columns\TextColumn::make('end_time'),
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
            'index' => Pages\ListDoctorShifts::route('/'),
            'create' => Pages\CreateDoctorShift::route('/create'),
            'edit' => Pages\EditDoctorShift::route('/{record}/edit'),
        ];
    }
}
