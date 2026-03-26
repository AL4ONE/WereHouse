<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupplierController extends Controller
{
    public function index(){
        $suppliers = Supplier::with('barangs')->get();
        return response()->json([
            'status' => "success",
            "data" => $suppliers
        ]);
    }

    public function create(Request $request){
        $val = Validator::make($request->all(), [
            "name" => "required",
            "email" => "required",
            "phone" => "required|numeric",
            "barang_ids" => "nullable|array",
            "barang_ids.*" => "exists:barangs,id",
        ]);

        if($val->fails()){
            return response()->json([
                'error' => $val->errors(),
            ]);
        }

        $supplier = Supplier::create($request->only(['name', 'email', 'phone']));

        if ($request->has('barang_ids')) {
            $supplier->barangs()->sync($request->barang_ids);
        }

        return response()->json([
            "data" => $supplier->load('barangs')
        ]);
    }

    public function destroy($id){
        $supplier = Supplier::where("id", $id)->first();
        if(!$supplier){
            return response()->json([
                'error' => "supplier not found"
            ]);
        }

        $supplier->delete();
        return response()->json([
            'message' => "supplier berhasil di hapus"
        ]);
    }

    public function update(Request $request, $id){
            $supplier = Supplier::where("id", $id)->first();
            if(!$supplier){
                return response()->json([
                    'error' => "supplier not found"
                ]);
            }

         $val = Validator::make($request->all(), [
            "name" => "required",
            "email" => "required",
            "phone" => "required|numeric",
            "barang_ids" => "nullable|array",
            "barang_ids.*" => "exists:barangs,id",
        ]);

        if($val->fails()){
            return response()->json([
                'error' => $val->errors(),
            ]);
        }
        $supplier->update($request->only(['name', 'email', 'phone']));

        if ($request->has('barang_ids')) {
            $supplier->barangs()->sync($request->barang_ids);
        }

        return response()->json([
            'data' => $supplier->load('barangs')
        ]);
    }
}
