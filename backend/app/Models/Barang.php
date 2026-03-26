<?php

namespace App\Models;

use App\Models\StatusOpName;
use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    protected $guarded = [];
    public function barangMasuks()
    {
        return $this->hasMany(BarangMasuk::class);
    }
    public function barangKeluars()
    {
        return $this->hasMany(BarangKeluar::class);
    }
    public function statusOpNames()
    {
        return $this->hasMany(StatusOpName::class);
    }
    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class, 'barang_supplier', 'barang_id', 'supplier_id');
    }
}
