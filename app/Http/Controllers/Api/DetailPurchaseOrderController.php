<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DetailPurchaseOrder;
use Illuminate\Http\Request;

class DetailPurchaseOrderController extends Controller
{
    public function index()
    {
        $detail = DetailPurchaseOrder::with(['purchaseOrder', 'barang', 'supplier'])->get();
        return response()->json($detail);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'harga_barang_PO' => 'required|numeric',
            'kuantitas_barang_PO' => 'required|numeric',
            'id_PO' => 'required|exists:purchase_orders,id',
            'id_barang' => 'required|exists:barang,id',
            'id_supplier' => 'required|exists:suppliers,id',
        ]);

        $detail = DetailPurchaseOrder::create($validated);

        return response()->json([
            'message' => 'Detail Purchase Order berhasil ditambahkan',
            'data' => $detail
        ], 201);
    }

    public function show($id)
    {
        $detail = DetailPurchaseOrder::with(['purchaseOrder', 'barang', 'supplier'])->find($id);

        if (!$detail) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json($detail);
    }

    public function update(Request $request, $id)
    {
        $detail = DetailPurchaseOrder::find($id);

        if (!$detail) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $validated = $request->validate([
            'harga_barang_PO' => 'numeric',
            'kuantitas_barang_PO' => 'numeric',
            'id_PO' => 'exists:purchase_orders,id',
            'id_barang' => 'exists:barang,id',
            'id_supplier' => 'exists:suppliers,id',
        ]);

        $detail->update($validated);

        return response()->json([
            'message' => 'Detail Purchase Order berhasil diperbarui',
            'data' => $detail
        ]);
    }

    public function destroy($id)
    {
        $detail = DetailPurchaseOrder::find($id);

        if (!$detail) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $detail->delete();

        return response()->json(['message' => 'Detail Purchase Order berhasil dihapus']);
    }
}
