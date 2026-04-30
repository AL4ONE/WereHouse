<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BarangKeluar extends Model
{
    protected $fillable = ['barang_id', 'destination', 'recipient_address', 'recipient_phone', 'stock', 'harga_satuan', 'total_harga', 'invoice_number', 'discount', 'shipping_cost', 'down_payment', 'po_number', 'vehicle', 'vehicle_plate', 'pic_name'];
    public function barang()
    {
        return $this->belongsTo(Barang::class);
    }
}
