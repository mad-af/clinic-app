<?php

namespace Tests\Feature;

use App\Filament\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\DoctorShift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AppointmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_appointment_within_shift()
    {
        $doctor = User::factory()->doctor()->create();
        
        // Monday (1) shift 09:00 - 17:00
        DoctorShift::create([
            'doctor_id' => $doctor->id,
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
        ]);

        // Next Monday at 10:00
        $appointmentDate = now()->next(1)->setTime(10, 0, 0);

        Livewire::test(AppointmentResource\Pages\CreateAppointment::class)
            ->set('data.doctor_id', $doctor->id)
            ->set('data.appointment_time', $appointmentDate->toDateTimeString())
            ->call('create')
            ->assertHasNoErrors();
            
        $this->assertDatabaseHas('appointments', [
            'doctor_id' => $doctor->id,
            'appointment_time' => $appointmentDate->toDateTimeString(),
        ]);
    }

    public function test_cannot_create_appointment_outside_shift()
    {
        $doctor = User::factory()->doctor()->create();
        
        // Monday (1) shift 09:00 - 17:00
        DoctorShift::create([
            'doctor_id' => $doctor->id,
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
        ]);

        // Next Monday at 08:00 (Before shift)
        $appointmentDate = now()->next(1)->setTime(8, 0, 0);

        Livewire::test(AppointmentResource\Pages\CreateAppointment::class)
            ->set('data.doctor_id', $doctor->id)
            ->set('data.appointment_time', $appointmentDate->toDateTimeString())
            ->call('create')
            ->assertHasErrors(['data.appointment_time']);
    }

    public function test_cannot_create_conflicting_appointment()
    {
        $doctor = User::factory()->doctor()->create();
        
        // Monday (1) shift 09:00 - 17:00
        DoctorShift::create([
            'doctor_id' => $doctor->id,
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
        ]);

        // Next Monday at 10:00
        $appointmentDate = now()->next(1)->setTime(10, 0, 0);

        Appointment::create([
            'doctor_id' => $doctor->id,
            'appointment_time' => $appointmentDate,
        ]);

        // Try to create same appointment
        Livewire::test(AppointmentResource\Pages\CreateAppointment::class)
            ->set('data.doctor_id', $doctor->id)
            ->set('data.appointment_time', $appointmentDate->toDateTimeString())
            ->call('create')
            ->assertHasErrors(['data.appointment_time']);
    }
}
