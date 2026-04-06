<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusOpName extends Model
{
    protected $fillable = ['stock', 'tipe', 'keterangan', 'barang_id'];
}
