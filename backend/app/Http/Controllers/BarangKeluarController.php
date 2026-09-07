<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\BarangKeluar;
use App\Models\BarangKeluarItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BarangKeluarController extends Controller
{
    public function index(Request $request)
    {
        $isTraining = $request->user()->isTraining();
        $query = BarangKeluar::with(['items.barang', 'barang'])->trainingMode($isTraining);

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->latest()->get()
        ]);
    }

    public function out(Request $request)
    {
        $requestItems = $request->items;
        if (!$requestItems && $request->has('barang_id')) {
            $requestItems = [
                [
                    'barang_id' => $request->barang_id,
                    'stock' => $request->stock
                ]
            ];
            $request->merge(['items' => $requestItems]);
        }

        $val = Validator::make($request->all(), [
            "destination" => "required|string",
            "recipient_address" => "nullable|string",
            "recipient_phone" => "nullable|string",
            "discount" => "nullable|numeric",
            "shipping_cost" => "nullable|numeric",
            "down_payment" => "nullable|numeric",
            "po_number" => "nullable|string",
            "vehicle" => "nullable|string",
            "vehicle_plate" => "nullable|string",
            "pic_name" => "nullable|string",
            "items" => "required|array|min:1",
            "items.*.barang_id" => "required|exists:barangs,id",
            "items.*.stock" => "required|numeric|min:1",
        ]);

        if ($val->fails()) {
            return response()->json([
                'error' => $val->errors(),
            ], 422);
        }

        $isTraining = $request->user()->isTraining();

        try {
            $result = DB::transaction(function () use ($request, $requestItems, $isTraining) {
                $aggregatedItems = [];
                foreach ($requestItems as $item) {
                    $bId = (int)$item['barang_id'];
                    $qty = (int)$item['stock'];
                    if (isset($aggregatedItems[$bId])) {
                        $aggregatedItems[$bId] += $qty;
                    } else {
                        $aggregatedItems[$bId] = $qty;
                    }
                }

                $barangs = Barang::whereIn('id', array_keys($aggregatedItems))
                    ->where('is_training', $isTraining)
                    ->get()
                    ->keyBy('id');

                foreach ($aggregatedItems as $bId => $neededStock) {
                    $barang = $barangs->get($bId);
                    if (!$barang) {
                        throw new \Exception("Product ID {$bId} was not found in current mode.");
                    }
                    if ($neededStock > $barang->stock_saat_ini) {
                        throw new \Exception("Stock for '{$barang->name}' is insufficient (available: {$barang->stock_saat_ini}, requested: {$neededStock})");
                    }
                }

                $count = BarangKeluar::whereDate('created_at', now())->where('is_training', $isTraining)->count();
                $prefix = $isTraining ? 'TRN-INV-' : 'INV-';
                $invoiceNumber = $prefix . date('Ymd') . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

                $poCount = BarangKeluar::whereYear('created_at', date('Y'))->where('is_training', $isTraining)->count();
                $poPrefix = $isTraining ? 'TRN-PO-' : 'PO-';
                $poNumber = $request->filled('po_number') 
                    ? $request->po_number 
                    : ($poPrefix . date('Y') . '-' . str_pad($poCount + 1, 3, '0', STR_PAD_LEFT));

                $totalItemPrice = 0;
                $totalStock = 0;
                $itemsToCreate = [];
                $firstItem = null;

                foreach ($requestItems as $item) {
                    $barang = $barangs->get((int)$item['barang_id']);
                    $qty = (int)$item['stock'];
                    $unitPrice = $barang->harga;
                    $subtotal = $unitPrice * $qty;
                    $totalItemPrice += $subtotal;
                    $totalStock += $qty;

                    if (!$firstItem) {
                        $firstItem = [
                            'barang_id' => $barang->id,
                            'harga_satuan' => $unitPrice,
                        ];
                    }

                    $itemsToCreate[] = [
                        'barang_id' => $barang->id,
                        'stock' => $qty,
                        'harga_satuan' => $unitPrice,
                        'total_harga' => $subtotal,
                    ];
                }

                $data = $request->only([
                    'destination', 'recipient_address', 'recipient_phone',
                    'discount', 'shipping_cost', 'down_payment',
                    'vehicle', 'vehicle_plate', 'pic_name'
                ]);

                $data['is_training'] = $isTraining;
                $data['invoice_number'] = $invoiceNumber;
                $data['po_number'] = $poNumber;
                $data['total_harga'] = $totalItemPrice;
                $data['discount'] = $request->discount ?? 0;
                $data['shipping_cost'] = $request->shipping_cost ?? 0;
                $data['down_payment'] = $request->down_payment ?? 0;

                $data['barang_id'] = $firstItem ? $firstItem['barang_id'] : null;
                $data['stock'] = $totalStock;
                $data['harga_satuan'] = $firstItem ? $firstItem['harga_satuan'] : 0;

                $barangKeluar = BarangKeluar::create($data);

                foreach ($itemsToCreate as $itData) {
                    $barangKeluar->items()->create($itData);
                }

                foreach ($aggregatedItems as $bId => $deductQty) {
                    $barang = $barangs->get($bId);
                    $barang->update([
                        'stock_saat_ini' => $barang->stock_saat_ini - $deductQty
                    ]);
                }

                return $barangKeluar->load(['items.barang', 'barang']);
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Outbound stock recorded successfully!',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function update(Request $request, $id)
    {
        $requestItems = $request->items;
        if (!$requestItems && $request->has('barang_id')) {
            $requestItems = [
                [
                    'barang_id' => $request->barang_id,
                    'stock' => $request->stock
                ]
            ];
            $request->merge(['items' => $requestItems]);
        }

        $val = Validator::make($request->all(), [
            "destination" => "required|string",
            "recipient_address" => "nullable|string",
            "recipient_phone" => "nullable|string",
            "discount" => "nullable|numeric",
            "shipping_cost" => "nullable|numeric",
            "down_payment" => "nullable|numeric",
            "po_number" => "nullable|string",
            "vehicle" => "nullable|string",
            "vehicle_plate" => "nullable|string",
            "pic_name" => "nullable|string",
            "items" => "required|array|min:1",
            "items.*.barang_id" => "required|exists:barangs,id",
            "items.*.stock" => "required|numeric|min:1",
        ]);

        if ($val->fails()) {
            return response()->json([
                'error' => $val->errors(),
            ], 422);
        }

        $isTraining = $request->user()->isTraining();

        try {
            $result = DB::transaction(function () use ($request, $requestItems, $id, $isTraining) {
                $barangKeluar = BarangKeluar::with('items')
                    ->where('id', $id)
                    ->where('is_training', $isTraining)
                    ->firstOrFail();

                if ($barangKeluar->items && $barangKeluar->items->isNotEmpty()) {
                    foreach ($barangKeluar->items as $oldItem) {
                        $b = Barang::find($oldItem->barang_id);
                        if ($b) {
                            $b->update([
                                'stock_saat_ini' => $b->stock_saat_ini + $oldItem->stock
                            ]);
                        }
                    }
                } elseif ($barangKeluar->barang_id && $barangKeluar->stock) {
                    $b = Barang::find($barangKeluar->barang_id);
                    if ($b) {
                        $b->update([
                            'stock_saat_ini' => $b->stock_saat_ini + $barangKeluar->stock
                        ]);
                    }
                }

                $aggregatedItems = [];
                foreach ($requestItems as $item) {
                    $bId = (int)$item['barang_id'];
                    $qty = (int)$item['stock'];
                    if (isset($aggregatedItems[$bId])) {
                        $aggregatedItems[$bId] += $qty;
                    } else {
                        $aggregatedItems[$bId] = $qty;
                    }
                }

                $barangs = Barang::whereIn('id', array_keys($aggregatedItems))
                    ->where('is_training', $isTraining)
                    ->get()
                    ->keyBy('id');

                foreach ($aggregatedItems as $bId => $neededStock) {
                    $barang = $barangs->get($bId);
                    if (!$barang) {
                        throw new \Exception("Product ID {$bId} not found in current mode.");
                    }
                    if ($neededStock > $barang->stock_saat_ini) {
                        throw new \Exception("Stock for '{$barang->name}' is insufficient (available: {$barang->stock_saat_ini}, requested: {$neededStock})");
                    }
                }

                $barangKeluar->items()->delete();

                $totalItemPrice = 0;
                $totalStock = 0;
                $firstItem = null;

                foreach ($requestItems as $item) {
                    $barang = $barangs->get((int)$item['barang_id']);
                    $qty = (int)$item['stock'];
                    $unitPrice = $barang->harga;
                    $subtotal = $unitPrice * $qty;
                    $totalItemPrice += $subtotal;
                    $totalStock += $qty;

                    if (!$firstItem) {
                        $firstItem = [
                            'barang_id' => $barang->id,
                            'harga_satuan' => $unitPrice,
                        ];
                    }

                    $barangKeluar->items()->create([
                        'barang_id' => $barang->id,
                        'stock' => $qty,
                        'harga_satuan' => $unitPrice,
                        'total_harga' => $subtotal,
                    ]);
                }

                foreach ($aggregatedItems as $bId => $deductQty) {
                    $barang = $barangs->get($bId);
                    $barang->update([
                        'stock_saat_ini' => $barang->stock_saat_ini - $deductQty
                    ]);
                }

                $updateData = $request->only([
                    'destination', 'recipient_address', 'recipient_phone',
                    'discount', 'shipping_cost', 'down_payment',
                    'vehicle', 'vehicle_plate', 'pic_name'
                ]);

                if ($request->filled('po_number')) {
                    $updateData['po_number'] = $request->po_number;
                }

                $updateData['total_harga'] = $totalItemPrice;
                $updateData['discount'] = $request->discount ?? 0;
                $updateData['shipping_cost'] = $request->shipping_cost ?? 0;
                $updateData['down_payment'] = $request->down_payment ?? 0;

                $updateData['barang_id'] = $firstItem ? $firstItem['barang_id'] : null;
                $updateData['stock'] = $totalStock;
                $updateData['harga_satuan'] = $firstItem ? $firstItem['harga_satuan'] : 0;

                $barangKeluar->update($updateData);

                return $barangKeluar->fresh(['items.barang', 'barang']);
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Outbound stock updated successfully!',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
