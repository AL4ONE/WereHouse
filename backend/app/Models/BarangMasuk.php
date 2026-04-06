<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BarangMasuk extends Model
{
    protected $fillable = ['barang_id', 'supplier_id', 'stock'];
    public function barang()
    {
        return $this->belongsTo(Barang::class);
    }
}
