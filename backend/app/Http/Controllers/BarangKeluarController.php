<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\BarangKeluar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BarangKeluarController extends Controller
{
    public function index(Request $request){
        $query = BarangKeluar::with('barang');

        if($request->start_date && $request->end_date) {
            $query->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->latest()->get()
        ]);
    }

    public function out(Request $request){
        $val = Validator::make($request->all(), [
            "barang_id" => "required|exists:barangs,id",
            "destination" => "required",
            "recipient_address" => "nullable|string",
            "recipient_phone" => "nullable|string",
            "stock" => "required",
            "discount" => "numeric",
            "shipping_cost" => "numeric",
            "down_payment" => "numeric",
            "po_number" => "nullable|string",
            "vehicle" => "nullable|string",
            "vehicle_plate" => "nullable|string",
            "pic_name" => "nullable|string",
        ]);

         if($val->fails()){
            return response()->json([
                'error' => $val->errors(),
            ], 422);
        }

        $barang = Barang::where("id", $request->barang_id)->first();

        if($request->stock > $barang->stock_saat_ini){
            return response()->json([
                'status' => "failed",
                "message" => "stock barang tidak mencukupi"
            ], 400);
        }
        $data = $request->only(['barang_id', 'destination', 'recipient_address', 'recipient_phone', 'stock', 'discount', 'shipping_cost', 'down_payment', 'po_number', 'vehicle', 'vehicle_plate', 'pic_name']);


        $data['harga_satuan'] = $barang->harga;
        $data['total_harga'] = $barang->harga * $request->stock;

        // Generate Invoice Number: INV-YYYYMMDD-XXXX
        $count = BarangKeluar::whereDate('created_at', now())->count();
        $data['invoice_number'] = 'INV-' . date('Ymd') . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

        // Generate PO Number: PO-YYYY-XXX
        $poCount = BarangKeluar::whereYear('created_at', date('Y'))->count();
        $data['po_number'] = 'PO-' . date('Y') . '-' . str_pad($poCount + 1, 3, '0', STR_PAD_LEFT);
        
        $barangKeluar = BarangKeluar::create($data);
        $barang->update([
            "stock_saat_ini" => $barang->stock_saat_ini - $barangKeluar->stock
        ]);

        return response()->json([
            'status' => 'success',
            'barang' => $barang,
            "data" => $barangKeluar,
        ]);
    }
}
