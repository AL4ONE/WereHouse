<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\BarangKeluar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BarangKeluarController extends Controller
{
    public function out(Request $request){
        $val = Validator::make($request->all(), [
            "barang_id" => "required|exists:barangs,id",
            "destination" => "required",
            "stock" => "required"
        ]);

         if($val->fails()){
            return response()->json([
                'error' => $val->errors(),
            ]);
        }

        $barang = Barang::where("id", $request->barang_id)->first();

        if($request->stock > $barang->stock_saat_ini){
            return response()->json([
                'status' => "failed",
                "message" => "stock barang tidak mencukupi"
            ]);
        }
        $barangKeluar = BarangKeluar::create($request->all());
        $barang->update([
            "stock_saat_ini" => $barang->stock_saat_ini - $barangKeluar->stock
        ]);

        return response()->json([
            'barang' => $barang,
            "data masuk" => $barangKeluar,
        ]);
    }
}
