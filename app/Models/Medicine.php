<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medicine extends Model
{
    protected $guarded = [];

    protected $casts = [];

    public function medicalRecordItems()
    {
        return $this->hasMany(MedicalRecordItem::class);
    }
}
