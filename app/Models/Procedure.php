<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Procedure extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
    ];

    public function patientVisits()
    {
        return $this->belongsToMany(PatientVisit::class, 'patient_visit_procedures')
            ->withPivot('price')
            ->withTimestamps();
    }
}
