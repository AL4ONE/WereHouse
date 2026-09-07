<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BarangKeluarItem extends Model
{
    protected $fillable = [
        'barang_keluar_id',
        'barang_id',
        'stock',
        'harga_satuan',
        'total_harga',
    ];

    protected $casts = [
        'stock' => 'integer',
        'harga_satuan' => 'decimal:2',
        'total_harga' => 'decimal:2',
    ];

    public function barangKeluar()
    {
        return $this->belongsTo(BarangKeluar::class, 'barang_keluar_id');
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }
}
