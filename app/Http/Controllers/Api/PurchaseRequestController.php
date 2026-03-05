<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PurchaseRequest;
use App\Models\Admin;
use App\Models\Owner;
use Illuminate\Http\Request;

class PurchaseRequestController extends Controller
{
    // GET /purchase-request
    public function index()
    {
        $requests = PurchaseRequest::with(['admin', 'owner'])->get();
        return response()->json($requests);
    }

    // POST /purchase-request
   public function store(Request $request)
{
    $validated = $request->validate([
        'tgl_PR'             => 'required|date',
        'status_PR'          => 'required|string',
        'id_admin'           => 'nullable|exists:admin,id_admin',
        'id_owner'           => 'nullable|exists:owner,id_owner',
        'id_barang'          => 'required|exists:barang,id_barang',
        'id_supplier'        => 'required|exists:supplier,id_supplier',
        'hargabarangPR'      => 'required|numeric',
        'kuantitasbarangPR'  => 'required|integer',
    ]);

    if (empty($validated['id_admin']) && empty($validated['id_owner'])) {
        return response()->json(['message' => 'id_admin atau id_owner wajib diisi'], 422);
    }

    // Simpan PR dulu
    $purchaseRequest = PurchaseRequest::create([
        'tgl_PR'    => $validated['tgl_PR'],
        'status_PR' => $validated['status_PR'],
        'id_admin'  => $validated['id_admin'] ?? null,
        'id_owner'  => $validated['id_owner'] ?? null,
    ]);

    // Langsung simpan detail-nya
    $purchaseRequest->details()->create([
        'id_barang'         => $validated['id_barang'],
        'id_supplier'       => $validated['id_supplier'],
        'hargabarangPR'     => $validated['hargabarangPR'],
        'kuantitasbarangPR' => $validated['kuantitasbarangPR'],
    ]);

    return response()->json([
        'message' => 'Purchase Request berhasil dibuat',
        'data'    => $purchaseRequest->load('details'),
    ], 201);
}

    // GET /purchase-request/{id}
    public function show($id)
    {
        $pr = PurchaseRequest::with(['admin', 'owner', 'details.barang', 'details.supplier'])->find($id);

        if (!$pr) {
            return response()->json(['message' => 'Purchase Request tidak ditemukan'], 404);
        }

        return response()->json($pr);
    }

    // PUT /purchase-request/{id}
    public function update(Request $request, $id)
    {
        $purchaseRequest = PurchaseRequest::find($id);

        if (!$purchaseRequest) {
            return response()->json(['message' => 'Purchase Request tidak ditemukan'], 404);
        }

        $validated = $request->validate([
            'tgl_PR' => 'sometimes|date',
            'status_PR' => 'sometimes|string',
        ]);

        $purchaseRequest->update($validated);

        return response()->json([
            'message' => 'Purchase Request berhasil diperbarui',
            'data' => $purchaseRequest,
        ]);
    }

    // DELETE /purchase-request/{id}
    public function destroy($id)
    {
        $purchaseRequest = PurchaseRequest::find($id);

        if (!$purchaseRequest) {
            return response()->json(['message' => 'Purchase Request tidak ditemukan'], 404);
        }

        $purchaseRequest->delete();

        return response()->json(['message' => 'Purchase Request berhasil dihapus']);
    }
}
