<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $guarded = [];

    public function barangs()
    {
        return $this->belongsToMany(Barang::class, 'barang_supplier', 'supplier_id', 'barang_id');
    }
}
