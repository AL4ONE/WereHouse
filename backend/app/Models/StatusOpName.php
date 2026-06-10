<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusOpName extends Model
{
    protected $fillable = ['stock', 'tipe', 'keterangan', 'barang_id', 'is_training'];

    protected $casts = [
        'is_training' => 'boolean',
    ];

    public function scopeTrainingMode($query, $isTraining)
    {
        return $query->where('is_training', $isTraining);
    }
}
