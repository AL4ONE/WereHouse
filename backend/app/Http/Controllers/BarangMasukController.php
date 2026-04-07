<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\BarangMasuk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BarangMasukController extends Controller
{
    public function index(Request $request){
        $query = BarangMasuk::with(['barang', 'supplier']);

        if($request->start_date && $request->end_date) {
            $query->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->latest()->get()
        ]);
    }

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
        $barangMasuk = BarangMasuk::create($request->only(['barang_id', 'supplier_id', 'stock']));
        $barang->update([
            "stock_saat_ini" => $barang->stock_saat_ini + $barangMasuk->stock
        ]);

        return response()->json([
            'barang' => $barang,
            "data masuk" => $barangMasuk,
        ]);
    }

    public function update(Request $request, $id){
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

        $barangMasuk = BarangMasuk::findOrFail($id);
        
        // Revert old stock
        $oldBarang = Barang::findOrFail($barangMasuk->barang_id);
        $oldBarang->update([
            "stock_saat_ini" => $oldBarang->stock_saat_ini - $barangMasuk->stock
        ]);
        
        $barangMasuk->update($request->only(['barang_id', 'supplier_id', 'stock']));
        
        // Apply new stock
        $newBarang = Barang::findOrFail($request->barang_id);
        $newBarang->update([
            "stock_saat_ini" => $newBarang->stock_saat_ini + $request->stock
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $barangMasuk,
        ]);
    }

    public function destroy($id){
        $barangMasuk = BarangMasuk::findOrFail($id);
        
        // Revert stock
        $barang = Barang::findOrFail($barangMasuk->barang_id);
        $barang->update([
            "stock_saat_ini" => $barang->stock_saat_ini - $barangMasuk->stock
        ]);
        
        $barangMasuk->delete();
        
        return response()->json([
            'status' => 'success'
        ]);
    }
}
