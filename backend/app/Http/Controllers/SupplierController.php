<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupplierController extends Controller
{
    public function index(Request $request){
        $isTraining = $request->user()->isTraining();
        $suppliers = Supplier::with('barangs')->trainingMode($isTraining)->get();
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
            "alamat" => "required|string",
            "barang_ids" => "nullable|array",
            "barang_ids.*" => "exists:barangs,id",
        ]);

        if($val->fails()){
            return response()->json([
                'error' => $val->errors(),  
            ]);
        }

        $data = $request->only(['name', 'email', 'phone', 'alamat']);
        $data['is_training'] = $request->user()->isTraining();

        $supplier = Supplier::create($data);

        if ($request->has('barang_ids')) {
            $supplier->barangs()->sync($request->barang_ids);
        }

        return response()->json([
            "data" => $supplier->load('barangs')
        ]);
    }

    public function destroy(Request $request, $id){
        $isTraining = $request->user()->isTraining();
        $supplier = Supplier::where("id", $id)->where('is_training', $isTraining)->first();
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
            $isTraining = $request->user()->isTraining();
            $supplier = Supplier::where("id", $id)->where('is_training', $isTraining)->first();
            if(!$supplier){
                return response()->json([
                    'error' => "supplier not found"
                ]);
            }

         $val = Validator::make($request->all(), [
            "name" => "required",
            "email" => "required",
            "phone" => "required|numeric",
            "alamat" => "required|string",
            "barang_ids" => "nullable|array",
            "barang_ids.*" => "exists:barangs,id",
        ]);

        if($val->fails()){
            return response()->json([
                'error' => $val->errors(),
            ]);
        }
        $supplier->update($request->only(['name', 'email', 'phone', 'alamat']));

        if ($request->has('barang_ids')) {
            $supplier->barangs()->sync($request->barang_ids);
        }

        return response()->json([
            'data' => $supplier->load('barangs')
        ]);
    }
}
