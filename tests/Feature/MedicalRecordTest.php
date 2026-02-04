<?php

namespace Tests\Feature;

use App\Filament\Resources\MedicalRecordResource;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MedicalRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_medical_record_and_deduct_stock()
    {
        // Arrange
        $user = User::factory()->create();
        $patient = Patient::create(['name' => 'John Doe']);
        $medicine = Medicine::create(['name' => 'Paracetamol', 'stock' => 100]);

        // Act
        Livewire::actingAs($user)
            ->test(MedicalRecordResource\Pages\CreateMedicalRecord::class)
            ->fillForm([
                'patient_id' => $patient->id,
                'date' => now(),
                'diagnosis' => 'Headache',
                'items' => [
                    [
                        'medicine_id' => $medicine->id,
                        'quantity' => 10,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoErrors();

        // Assert
        $this->assertDatabaseHas('medical_records', [
            'patient_id' => $patient->id,
        ]);

        $this->assertDatabaseHas('medical_record_items', [
            'medicine_id' => $medicine->id,
            'quantity' => 10,
        ]);

        $this->assertEquals(90, $medicine->fresh()->stock);
    }

    public function test_cannot_create_medical_record_if_insufficient_stock()
    {
        // Arrange
        $user = User::factory()->create();
        $patient = Patient::create(['name' => 'John Doe']);
        $medicine = Medicine::create(['name' => 'Paracetamol', 'stock' => 5]);

        // Act & Assert
        try {
            Livewire::actingAs($user)
                ->test(MedicalRecordResource\Pages\CreateMedicalRecord::class)
                ->fillForm([
                    'patient_id' => $patient->id,
                    'date' => now(),
                    'diagnosis' => 'Headache',
                    'items' => [
                        [
                            'medicine_id' => $medicine->id,
                            'quantity' => 10,
                        ],
                    ],
                ])
                ->call('create');
        } catch (\Exception $e) {
            // Depending on how Filament handles it, it might bubble up or be caught.
            // If caught by Filament, it sets a notification.
        }

        // Check if stock is untouched
        $this->assertEquals(5, $medicine->fresh()->stock);
        // Check if record is NOT created
        $this->assertDatabaseMissing('medical_records', ['patient_id' => $patient->id]);
    }

    public function test_can_update_medical_record_and_adjust_stock()
    {
        // Arrange
        $user = User::factory()->create();
        $patient = Patient::create(['name' => 'John Doe']);
        $medicine = Medicine::create(['name' => 'Paracetamol', 'stock' => 100]);

        // Create initial record
        $record = \App\Models\MedicalRecord::create([
            'patient_id' => $patient->id,
            'date' => now(),
            'diagnosis' => 'Initial',
        ]);
        $record->items()->create([
            'medicine_id' => $medicine->id,
            'quantity' => 10,
        ]);
        $medicine->decrement('stock', 10); // 90 left

        // Act - Update quantity to 20
        Livewire::actingAs($user)
            ->test(MedicalRecordResource\Pages\EditMedicalRecord::class, ['record' => $record->getKey()])
            ->set('data.items', [
                [
                    'medicine_id' => $medicine->id,
                    'quantity' => 20,
                ],
            ])
            ->call('save')
            ->assertHasNoErrors();

        // Assert
        // Original 10 refunded (90+10=100), then 20 deducted (100-20=80)
        $this->assertEquals(80, $medicine->fresh()->stock);
        $this->assertDatabaseHas('medical_record_items', [
            'medical_record_id' => $record->id,
            'medicine_id' => $medicine->id,
            'quantity' => 20,
        ]);
    }

    public function test_can_delete_medical_record_and_refund_stock()
    {
        // Arrange
        $user = User::factory()->create();
        $patient = Patient::create(['name' => 'John Doe']);
        $medicine = Medicine::create(['name' => 'Paracetamol', 'stock' => 100]);

        $record = \App\Models\MedicalRecord::create([
            'patient_id' => $patient->id,
            'date' => now(),
            'diagnosis' => 'Initial',
        ]);
        $record->items()->create([
            'medicine_id' => $medicine->id,
            'quantity' => 10,
        ]);
        $medicine->decrement('stock', 10); // 90 left

        // Act
        $record->delete();

        // Assert
        $this->assertEquals(100, $medicine->fresh()->stock);
        $this->assertDatabaseMissing('medical_records', ['id' => $record->id]);
    }

    public function test_removing_item_from_medical_record_refunds_stock()
    {
        // Arrange
        $user = User::factory()->create();
        $patient = Patient::create(['name' => 'John Doe']);
        $medicine = Medicine::create(['name' => 'Paracetamol', 'stock' => 100]);

        $record = \App\Models\MedicalRecord::create([
            'patient_id' => $patient->id,
            'date' => now(),
            'diagnosis' => 'Initial',
        ]);
        $record->items()->create([
            'medicine_id' => $medicine->id,
            'quantity' => 10,
        ]);
        $medicine->decrement('stock', 10); // 90 left

        // Act - Submit empty items
        Livewire::actingAs($user)
            ->test(MedicalRecordResource\Pages\EditMedicalRecord::class, ['record' => $record->getKey()])
            ->set('data.items', [])
            ->call('save')
            ->assertHasNoErrors();

        // Assert
        $this->assertEquals(100, $medicine->fresh()->stock);
        $this->assertDatabaseMissing('medical_record_items', ['medical_record_id' => $record->id]);
    }
}
