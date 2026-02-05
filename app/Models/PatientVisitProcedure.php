<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientVisitProcedure extends Model
{
    protected $fillable = [
        'patient_visit_id',
        'procedure_id',
        'price',
    ];

    public function patientVisit()
    {
        return $this->belongsTo(PatientVisit::class);
    }

    public function procedure()
    {
        return $this->belongsTo(Procedure::class);
    }
}
