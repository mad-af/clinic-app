<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'patient_visit_id',
        'amount',
        'status',
    ];

    public function patientVisit()
    {
        return $this->belongsTo(PatientVisit::class);
    }
}
