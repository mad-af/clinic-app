<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medicine extends Model
{
    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function medicalRecordItems()
    {
        return $this->hasMany(MedicalRecordItem::class);
    }
}
