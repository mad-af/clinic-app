<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AppointmentResource\Pages;
use App\Models\Appointment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('doctor_id')
                    ->label('Doctor')
                    ->relationship('doctor', 'name', fn (Builder $query) => $query->where('role', 'doctor'))
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set) {
                        $set('appointment_time', null);
                    }),
                Forms\Components\Placeholder::make('doctor_shifts')
                    ->label('Doctor Schedule')
                    ->content(function (Forms\Get $get) {
                        $doctorId = $get('doctor_id');
                        if (! $doctorId) {
                            return 'Select a doctor to view schedule';
                        }

                        $shifts = \App\Models\DoctorShift::where('doctor_id', $doctorId)
                            ->orderBy('day_of_week')
                            ->orderBy('start_time')
                            ->get();

                        if ($shifts->isEmpty()) {
                            return 'No schedule available for this doctor';
                        }

                        $days = [
                            0 => 'Sunday',
                            1 => 'Monday',
                            2 => 'Tuesday',
                            3 => 'Wednesday',
                            4 => 'Thursday',
                            5 => 'Friday',
                            6 => 'Saturday',
                        ];

                        $scheduleHtml = '<ul class="list-disc pl-4">';
                        foreach ($shifts as $shift) {
                            $dayName = $days[$shift->day_of_week] ?? 'Unknown';
                            $start = \Carbon\Carbon::parse($shift->start_time)->format('H:i');
                            $end = \Carbon\Carbon::parse($shift->end_time)->format('H:i');
                            $scheduleHtml .= "<li><strong>{$dayName}</strong>: {$start} - {$end}</li>";
                        }
                        $scheduleHtml .= '</ul>';

                        return new \Illuminate\Support\HtmlString($scheduleHtml);
                    }),
                Forms\Components\Select::make('patient_id')
                    ->label('Patient')
                    ->relationship('patient', 'name')
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->required()
                    ->default('pending'),
                Forms\Components\DateTimePicker::make('appointment_time')
                    ->required()
                    ->seconds(false)
                    ->rules([
                        fn (Forms\Get $get, $record): \Closure => function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                            $doctorId = $get('doctor_id');
                            if (! $doctorId || ! $value) {
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('doctor.name')
                    ->label('Doctor')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('patient.name')
                    ->label('Patient')
                    ->sortable()
                    ->searchable()
                    ->placeholder('No Patient'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'confirmed' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('appointment_time')
                    ->dateTime('M d, Y H:i')
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
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListAppointments::route('/'),
            'create' => Pages\CreateAppointment::route('/create'),
            'view' => Pages\ViewAppointment::route('/{record}'),
        ];
    }
}
