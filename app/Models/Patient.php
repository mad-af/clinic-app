<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $guarded = [];

    public function medicalRecords()
    {
        return $this->hasMany(MedicalRecord::class);
    }
}
