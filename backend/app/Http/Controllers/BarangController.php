<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\StatusOpName;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BarangController extends Controller
{
    public function index()
    {
        $barangs = Barang::with(['statusOpNames', 'barangMasuks', 'barangKeluars', 'suppliers'])->get();
        return response()->json([
            'status' => "success",
            "data" => $barangs,
        ]);
    }

    public function create(Request $request)
    {
        $val = Validator::make($request->all(), [
            "kode_barang" => "required|unique:barangs,kode_barang",
            "name" => "required",
            "stock_awal" => "required|numeric",
            "stock_saat_ini" => "required|numeric",
            "satuan" => "required",
            "harga" => "required|numeric|min:0",
            "min_stock" => "required|numeric|min:0",
            "supplier_ids" => "nullable|array",
            "supplier_ids.*" => "exists:suppliers,id",
        ]);

        if ($val->fails()) {
            return response()->json([
                'error' => $val->errors(),
            ]);
        }

        $barang = Barang::create($request->only(['kode_barang', 'name', 'stock_awal', 'stock_saat_ini', 'satuan', 'harga', 'min_stock']));

        if ($request->has('supplier_ids')) {
            $barang->suppliers()->sync($request->supplier_ids);
        }

        return response()->json([
            "data" => $barang->load('suppliers')
        ]);
    }

    public function destroy($id)
    {
        $barang = Barang::where("id", $id)->first();
        if (!$barang) {
            return response()->json([
                'error' => "barang not found"
            ]);
        }

        $barang->delete();
        return response()->json([
            'message' => "barang berhasil di hapus"
        ]);
    }

    public function update(Request $request, $id)
    {
        $barang = Barang::where("id", $id)->first();
        if (!$barang) {
            return response()->json([
                'error' => "barang not found"
            ]);
        }

        $val = Validator::make($request->all(), [
            "kode_barang" => "required|unique:barangs,kode_barang,".$id,
            "name" => "required",
            "stock_awal" => "required|numeric",
            "stock_saat_ini" => "required|numeric",
            "satuan" => "required",
            "harga" => "required|numeric|min:0",
            "min_stock" => "required|numeric|min:0",
            "supplier_ids" => "nullable|array",
            "supplier_ids.*" => "exists:suppliers,id",
        ]);

        if ($val->fails()) {
            return response()->json([
                'error' => $val->errors(),
            ]);
        }
        $barang->update($request->only(['kode_barang', 'name', 'stock_awal', 'stock_saat_ini', 'satuan', 'harga', 'min_stock']));

        if ($request->has('supplier_ids')) {
            $barang->suppliers()->sync($request->supplier_ids);
        }

        return response()->json([
            'data' => $barang->load('suppliers')
        ]);
    }

    public function assignSuppliers(Request $request, $id)
    {
        $barang = Barang::where("id", $id)->first();
        if (!$barang) {
            return response()->json([
                'error' => "barang not found"
            ]);
        }

        $val = Validator::make($request->all(), [
            "supplier_ids" => "required|array",
            "supplier_ids.*" => "exists:suppliers,id",
        ]);

        if ($val->fails()) {
            return response()->json([
                'error' => $val->errors(),
            ]);
        }

        $barang->suppliers()->sync($request->supplier_ids);

        return response()->json([
            'status' => 'success',
            'data' => $barang->load('suppliers')
        ]);
    }

    public function addOpName(Request $request, $id)
    {
        $barang = Barang::where("id", $id)->first();

        $val = Validator::make($request->all(), [
            'stock' => "required",
            'keterangan' => "required",
            'tipe' => "required",
        ]);

        if ($val->fails()) {
            return response()->json([
                'status' => "failed",
                'error' => $val->errors(),
            ]);
        }
        ;
        if ($request->tipe == "penambahan") {
            $barang->update([
                "stock_saat_ini" => $barang->stock_saat_ini + $request->stock
            ]);
        } else if ($request->tipe == "pengurangan") {
            $barang->update([
                "stock_saat_ini" => $barang->stock_saat_ini - $request->stock
            ]);
        }

        $stat = StatusOpName::create([
            "stock" => $request->stock,
            "tipe" => $request->tipe,
            "keterangan" => $request->keterangan,
            "barang_id" => $id,
        ]);
        return response()->json([
            'status' => "success",
            "data" => $stat,
            "barang" => $barang
        ]);

    }

    public function opName()
    {
        $opName = StatusOpName::all();
        return response()->json([
            'data' => $opName
        ]);
    }
}
