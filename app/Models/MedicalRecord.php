<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalRecord extends Model
{
    protected $guarded = [];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function items()
    {
        return $this->hasMany(MedicalRecordItem::class);
    }

    protected static function booted()
    {
        static::deleting(function (MedicalRecord $record) {
            foreach ($record->items as $item) {
                if ($item->medicine) {
                    $item->medicine->increment('stock', $item->quantity);
                }
            }
        });
    }
}
