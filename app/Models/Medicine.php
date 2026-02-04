<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medicine extends Model
{
    protected $guarded = [];

    public function medicalRecordItems()
    {
        return $this->hasMany(MedicalRecordItem::class);
    }
}
