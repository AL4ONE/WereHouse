<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BarangMasuk extends Model
{
    protected $fillable = ['barang_id', 'supplier_id', 'stock', 'harga_satuan', 'total_harga', 'is_training'];
    
    protected $casts = [
        'is_training' => 'boolean',
    ];

    public function scopeTrainingMode($query, $isTraining)
    {
        return $query->where('is_training', $isTraining);
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class);
    }
    
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
