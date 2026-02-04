<?php

namespace Tests\Feature;

use App\Filament\Resources\DoctorResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DoctorResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_doctors()
    {
        $doctor = User::factory()->create(['role' => 'doctor']);
        $patient = User::factory()->create(['role' => 'patient']);

        Livewire::test(DoctorResource\Pages\ListDoctors::class)
            ->assertCanSeeTableRecords([$doctor])
            ->assertCanNotSeeTableRecords([$patient]);
    }

    public function test_can_create_doctor()
    {
        $newData = User::factory()->make();

        Livewire::test(DoctorResource\Pages\CreateDoctor::class)
            ->fillForm([
                'name' => $newData->name,
                'email' => $newData->email,
                'password' => 'password',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'email' => $newData->email,
            'role' => 'doctor',
        ]);
    }

    public function test_can_edit_doctor()
    {
        $doctor = User::factory()->create(['role' => 'doctor']);
        $newName = 'Dr. Updated';

        Livewire::test(DoctorResource\Pages\EditDoctor::class, ['record' => $doctor->getRouteKey()])
            ->fillForm([
                'name' => $newName,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'id' => $doctor->id,
            'name' => $newName,
        ]);
    }
}
