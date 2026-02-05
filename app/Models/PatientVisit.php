<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientVisit extends Model
{
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'total_amount',
    ];

    protected $casts = [];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function patientVisitProcedures()
    {
        return $this->hasMany(PatientVisitProcedure::class);
    }

    public function procedures()
    {
        return $this->belongsToMany(Procedure::class, 'patient_visit_procedures')
            ->withPivot('price')
            ->withTimestamps();
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    public function recalculateTotal()
    {
        $proceduresTotal = $this->patientVisitProcedures->sum('price');
        $doctorFee = $this->doctor->service_fee ?? 0;

        $this->total_amount = $proceduresTotal + $doctorFee;
        $this->save();

        // Update Invoice if exists
        if ($this->invoice) {
            $this->invoice->amount = $this->total_amount;
            $this->invoice->save();
        }
    }
}
