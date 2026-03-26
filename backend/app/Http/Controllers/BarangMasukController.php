<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\BarangMasuk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BarangMasukController extends Controller
{
    public function in(Request $request){
        $val = Validator::make($request->all(), [
            "barang_id" => "required|exists:barangs,id",
            "supplier_id" => "required|exists:suppliers,id",
            "stock" => "required"
        ]);

         if($val->fails()){
            return response()->json([
                'error' => $val->errors(),
            ]);
        }

        $barang = Barang::where("id", $request->barang_id)->first();
        $barangMasuk = BarangMasuk::create($request->all());
        $barang->update([
            "stock_saat_ini" => $barang->stock_saat_ini + $barangMasuk->stock
        ]);

        return response()->json([
            'barang' => $barang,
            "data masuk" => $barangMasuk,
        ]);
    }
}
