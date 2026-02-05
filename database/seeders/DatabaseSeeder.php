<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Patient;
use App\Models\Medicine;
use App\Models\Procedure;
use App\Models\DoctorShift;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Admin User
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@clinic.com',
            'role' => 'admin',
        ]);

        // 2. Create Doctors
        $doctors = User::factory(3)->doctor()->create();

        // 3. Create Doctor Shifts (Monday-Friday, 09:00-17:00)
        foreach ($doctors as $doctor) {
            // Create shifts for Monday (1) to Friday (5)
            for ($day = 1; $day <= 5; $day++) {
                DoctorShift::create([
                    'doctor_id' => $doctor->id,
                    'day_of_week' => $day,
                    'start_time' => '09:00:00',
                    'end_time' => '17:00:00',
                ]);
            }
        }

        // 4. Create Patients
        Patient::factory(20)->create();

        // 5. Create Medicines
        Medicine::factory(30)->create();

        // 6. Create Procedures
        Procedure::factory(10)->create();
    }
}
