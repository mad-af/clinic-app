<?php

namespace App\Filament\Resources\DoctorResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AppointmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'appointments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('patient_id')
                    ->label('Patient')
                    ->relationship('patient', 'name')
                    ->searchable()
                    ->preload(),
                Forms\Components\DateTimePicker::make('appointment_time')
                    ->required()
                    ->seconds(false)
                    ->rules([
                        fn (RelationManager $livewire, $record): \Closure => function (string $attribute, $value, \Closure $fail) use ($livewire, $record) {
                            $doctorId = $livewire->getOwnerRecord()->id;
                            
                            if (! $value) {
                                return;
                            }

                            $date = \Carbon\Carbon::parse($value);
                            $dayOfWeek = $date->dayOfWeek;
                            $time = $date->format('H:i:s');

                            // Check 1: Shift Availability
                            $hasShift = \App\Models\DoctorShift::where('doctor_id', $doctorId)
                                ->where('day_of_week', $dayOfWeek)
                                ->where('start_time', '<=', $time)
                                ->where('end_time', '>=', $time)
                                ->exists();

                            if (! $hasShift) {
                                $fail('Doctor is not scheduled to work at this time (Day: '.$date->format('l').', Time: '.$time.').');
                                return;
                            }

                            // Check 2: Conflict
                            $query = \App\Models\Appointment::where('doctor_id', $doctorId)
                                ->where('appointment_time', $value);

                            if ($record) {
                                $query->where('id', '!=', $record->id);
                            }

                            if ($query->exists()) {
                                $fail('This time slot is already booked for this doctor.');
                            }
                        },
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('appointment_time')
            ->columns([
                Tables\Columns\TextColumn::make('patient.name')
                    ->label('Patient')
                    ->searchable()
                    ->placeholder('No Patient'),
                Tables\Columns\TextColumn::make('appointment_time')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
