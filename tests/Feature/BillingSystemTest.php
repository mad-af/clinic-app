<?php

namespace Tests\Feature;

use App\Filament\Resources\PatientVisitResource;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Procedure;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BillingSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_create_patient_visit_with_automatic_invoice()
    {
        $doctor = User::factory()->create(['role' => 'doctor', 'service_fee' => 50000]);
        $patient = Patient::factory()->create();
        $procedure = Procedure::create(['name' => 'Consultation', 'price' => 100000]);

        Livewire::test(PatientVisitResource\Pages\CreatePatientVisit::class)
            ->set('data.doctor_id', $doctor->id)
            ->set('data.patient_id', $patient->id)
            ->set('data.procedure_id', $procedure->id)
            ->assertSet('data.total_amount', 150000) // 100000 + 50000
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('patient_visits', [
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'procedure_id' => $procedure->id,
            'total_amount' => 150000,
        ]);

        $this->assertDatabaseHas('invoices', [
            'amount' => 150000,
            'status' => 'Unpaid',
        ]);
    }

    public function test_live_billing_calculation_updates_on_doctor_change()
    {
        $doctor1 = User::factory()->create(['role' => 'doctor', 'service_fee' => 50000]);
        $doctor2 = User::factory()->create(['role' => 'doctor', 'service_fee' => 75000]);
        $procedure = Procedure::create(['name' => 'Consultation', 'price' => 100000]);

        Livewire::test(PatientVisitResource\Pages\CreatePatientVisit::class)
            ->set('data.procedure_id', $procedure->id)
            ->set('data.doctor_id', $doctor1->id)
            ->assertSet('data.total_amount', 150000)
            ->set('data.doctor_id', $doctor2->id)
            ->assertSet('data.total_amount', 175000);
    }

    public function test_live_billing_calculation_updates_on_procedure_change()
    {
        $doctor = User::factory()->create(['role' => 'doctor', 'service_fee' => 50000]);
        $procedure1 = Procedure::create(['name' => 'Consultation', 'price' => 100000]);
        $procedure2 = Procedure::create(['name' => 'Checkup', 'price' => 200000]);

        Livewire::test(PatientVisitResource\Pages\CreatePatientVisit::class)
            ->set('data.doctor_id', $doctor->id)
            ->set('data.procedure_id', $procedure1->id)
            ->assertSet('data.total_amount', 150000)
            ->set('data.procedure_id', $procedure2->id)
            ->assertSet('data.total_amount', 250000);
    }
}
