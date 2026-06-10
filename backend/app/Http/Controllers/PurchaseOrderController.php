<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\BarangMasuk;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PurchaseOrderController extends Controller
{
    /**
     * List all purchase orders with filters
     */
    public function index(Request $request)
    {
        $isTraining = $request->user()->isTraining();
        $query = PurchaseOrder::with(['supplier', 'creator', 'approver', 'items.barang'])
            ->trainingMode($isTraining);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->supplier_id) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('order_date', [$request->start_date, $request->end_date]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->latest()->get(),
        ]);
    }

    /**
     * Show a single purchase order with items
     */
    public function show(Request $request, $id)
    {
        $isTraining = $request->user()->isTraining();
        $po = PurchaseOrder::with(['supplier', 'creator', 'approver', 'items.barang'])
            ->where('is_training', $isTraining)
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $po,
        ]);
    }

    /**
     * Create a new purchase order
     */
    public function store(Request $request)
    {
        $val = Validator::make($request->all(), [
            'supplier_id' => 'required|exists:suppliers,id',
            'order_date' => 'required|date',
            'expected_date' => 'nullable|date|after_or_equal:order_date',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:draft,pending',
            'items' => 'required|array|min:1',
            'items.*.barang_id' => 'required|exists:barangs,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'nullable|numeric|min:0',
        ]);

        if ($val->fails()) {
            return response()->json([
                'error' => $val->errors(),
            ], 422);
        }

        $isTraining = $request->user()->isTraining();

        return DB::transaction(function () use ($request, $isTraining) {
            // Generate PO Number: PO-YYYY-XXX
            $year = date('Y');
            $count = PurchaseOrder::whereYear('created_at', $year)->where('is_training', $isTraining)->count();
            $prefix = $isTraining ? 'TRN-PO-' : 'PO-';
            $poNumber = $prefix . $year . '-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);

            $po = PurchaseOrder::create([
                'po_number' => $poNumber,
                'supplier_id' => $request->supplier_id,
                'created_by' => $request->user()->id,
                'status' => $request->status ?? 'draft',
                'order_date' => $request->order_date,
                'expected_date' => $request->expected_date,
                'notes' => $request->notes,
                'total_amount' => 0,
                'is_training' => $isTraining,
            ]);

            $totalAmount = 0;

            foreach ($request->items as $item) {
                $barang = Barang::findOrFail($item['barang_id']);
                $unitPrice = $item['unit_price'] ?? $barang->harga;
                $subtotal = $unitPrice * $item['quantity'];

                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'barang_id' => $item['barang_id'],
                    'quantity' => $item['quantity'],
                    'received_quantity' => 0,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ]);

                $totalAmount += $subtotal;
            }

            $po->update(['total_amount' => $totalAmount]);

            return response()->json([
                'status' => 'success',
                'data' => $po->load(['supplier', 'creator', 'items.barang']),
            ], 201);
        });
    }

    /**
     * Update a purchase order (only draft status)
     */
    public function update(Request $request, $id)
    {
        $isTraining = $request->user()->isTraining();
        $po = PurchaseOrder::where('is_training', $isTraining)->findOrFail($id);

        if ($po->status !== 'draft') {
            return response()->json([
                'status' => 'failed',
                'message' => 'Hanya PO dengan status draft yang bisa diedit',
            ], 400);
        }

        $val = Validator::make($request->all(), [
            'supplier_id' => 'required|exists:suppliers,id',
            'order_date' => 'required|date',
            'expected_date' => 'nullable|date|after_or_equal:order_date',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:draft,pending',
            'items' => 'required|array|min:1',
            'items.*.barang_id' => 'required|exists:barangs,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'nullable|numeric|min:0',
        ]);

        if ($val->fails()) {
            return response()->json([
                'error' => $val->errors(),
            ], 422);
        }

        return DB::transaction(function () use ($request, $po) {
            $po->update([
                'supplier_id' => $request->supplier_id,
                'order_date' => $request->order_date,
                'expected_date' => $request->expected_date,
                'notes' => $request->notes,
                'status' => $request->status ?? $po->status,
            ]);

            // Delete old items and recreate
            $po->items()->delete();

            $totalAmount = 0;

            foreach ($request->items as $item) {
                $barang = Barang::findOrFail($item['barang_id']);
                $unitPrice = $item['unit_price'] ?? $barang->harga;
                $subtotal = $unitPrice * $item['quantity'];

                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'barang_id' => $item['barang_id'],
                    'quantity' => $item['quantity'],
                    'received_quantity' => 0,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ]);

                $totalAmount += $subtotal;
            }

            $po->update(['total_amount' => $totalAmount]);

            return response()->json([
                'status' => 'success',
                'data' => $po->load(['supplier', 'creator', 'items.barang']),
            ]);
        });
    }

    /**
     * Delete a purchase order (only draft status)
     */
    public function destroy(Request $request, $id)
    {
        $isTraining = $request->user()->isTraining();
        $po = PurchaseOrder::where('is_training', $isTraining)->findOrFail($id);

        if ($po->status !== 'draft') {
            return response()->json([
                'status' => 'failed',
                'message' => 'Hanya PO dengan status draft yang bisa dihapus',
            ], 400);
        }

        $po->delete();

        return response()->json([
            'status' => 'success',
        ]);
    }

    /**
     * Approve a purchase order (pending -> approved)
     */
    public function approve(Request $request, $id)
    {
        $isTraining = $request->user()->isTraining();
        $po = PurchaseOrder::where('is_training', $isTraining)->findOrFail($id);

        if ($po->status !== 'pending') {
            return response()->json([
                'status' => 'failed',
                'message' => 'Hanya PO dengan status pending yang bisa di-approve',
            ], 400);
        }

        $po->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $po->load(['supplier', 'creator', 'approver', 'items.barang']),
        ]);
    }

    /**
     * Receive goods from a PO (approved -> received)
     * This auto-creates BarangMasuk records and updates stock
     */
    public function receive(Request $request, $id)
    {
        $isTraining = $request->user()->isTraining();
        $po = PurchaseOrder::with('items')->where('is_training', $isTraining)->findOrFail($id);

        if ($po->status !== 'approved') {
            return response()->json([
                'status' => 'failed',
                'message' => 'Hanya PO dengan status approved yang bisa di-receive',
            ], 400);
        }

        return DB::transaction(function () use ($po, $isTraining) {
            foreach ($po->items as $item) {
                // Create BarangMasuk record
                BarangMasuk::create([
                    'barang_id' => $item->barang_id,
                    'supplier_id' => $po->supplier_id,
                    'stock' => $item->quantity,
                    'harga_satuan' => $item->unit_price,
                    'total_harga' => $item->subtotal,
                    'is_training' => $isTraining,
                ]);

                // Update stock
                $barang = Barang::findOrFail($item->barang_id);
                $barang->update([
                    'stock_saat_ini' => $barang->stock_saat_ini + $item->quantity,
                ]);

                // Mark item as received
                $item->update([
                    'received_quantity' => $item->quantity,
                ]);
            }

            $po->update(['status' => 'received']);

            return response()->json([
                'status' => 'success',
                'message' => 'Barang dari PO berhasil diterima dan stok sudah diperbarui',
                'data' => $po->load(['supplier', 'creator', 'approver', 'items.barang']),
            ]);
        });
    }

    /**
     * Cancel a purchase order
     */
    public function cancel(Request $request, $id)
    {
        $isTraining = $request->user()->isTraining();
        $po = PurchaseOrder::where('is_training', $isTraining)->findOrFail($id);

        if (in_array($po->status, ['received', 'cancelled'])) {
            return response()->json([
                'status' => 'failed',
                'message' => 'PO yang sudah diterima atau dibatalkan tidak bisa dicancel',
            ], 400);
        }

        $po->update(['status' => 'cancelled']);

        return response()->json([
            'status' => 'success',
            'data' => $po->load(['supplier', 'creator', 'items.barang']),
        ]);
    }
}
