<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalRecordItem extends Model
{
    protected $guarded = [];

    public function medicalRecord()
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    public function medicine()
    {
        return $this->belongsTo(Medicine::class);
    }
}
