<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DetailPurchaseRequest;
use Illuminate\Http\Request;

class DetailPurchaseRequestController extends Controller
{
    // GET /detail-purchase-request
    public function index()
    {
        $details = DetailPurchaseRequest::with(['barang', 'supplier', 'purchaseRequest'])->get();
        return response()->json($details);
    }

    // POST /detail-purchase-request
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_PR' => 'required|exists:purchase_requests,id',
            'id_barang' => 'required|exists:barangs,id',
            'id_supplier' => 'required|exists:suppliers,id',
            'hargabarangPR' => 'required|numeric',
            'kuantitasbarangPR' => 'required|integer',
        ]);

        $detail = DetailPurchaseRequest::create($validated);

        return response()->json([
            'message' => 'Detail Purchase Request berhasil dibuat',
            'data' => $detail,
        ], 201);
    }

    // GET /detail-purchase-request/{id}
    public function show($id)
    {
        $detail = DetailPurchaseRequest::with(['barang', 'supplier', 'purchaseRequest'])->find($id);

        if (!$detail) {
            return response()->json(['message' => 'Detail tidak ditemukan'], 404);
        }

        return response()->json($detail);
    }

    // PUT /detail-purchase-request/{id}
    public function update(Request $request, $id)
    {
        $detail = DetailPurchaseRequest::find($id);

        if (!$detail) {
            return response()->json(['message' => 'Detail tidak ditemukan'], 404);
        }

        $validated = $request->validate([
            'hargabarangPR' => 'sometimes|numeric',
            'kuantitasbarangPR' => 'sometimes|integer',
        ]);

        $detail->update($validated);

        return response()->json([
            'message' => 'Detail berhasil diperbarui',
            'data' => $detail,
        ]);
    }

    // DELETE /detail-purchase-request/{id}
    public function destroy($id)
    {
        $detail = DetailPurchaseRequest::find($id);

        if (!$detail) {
            return response()->json(['message' => 'Detail tidak ditemukan'], 404);
        }

        $detail->delete();

        return response()->json(['message' => 'Detail berhasil dihapus']);
    }
}
