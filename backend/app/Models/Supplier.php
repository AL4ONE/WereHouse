<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = ['name', 'email', 'phone', 'alamat', 'is_training'];

    protected $casts = [
        'is_training' => 'boolean',
    ];

    public function scopeTrainingMode($query, $isTraining)
    {
        return $query->where('is_training', $isTraining);
    }

    public function barangs()
    {
        return $this->belongsToMany(Barang::class, 'barang_supplier', 'supplier_id', 'barang_id');
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}
