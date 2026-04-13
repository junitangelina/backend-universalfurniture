<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    // ──────────────────────────────────────────────────────────
    // GET /api/supplier
    // Query params:
    //   ?search=toko jaya   → cari nama supplier
    //   ?per_page=10        → jumlah per halaman (default: semua)
    //
    // Catatan: kalau tidak ada ?per_page → return semua (untuk dropdown)
    //          kalau ada ?per_page       → return pagination (untuk tabel)
    // ──────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = Supplier::withCount('barang'); // tampilkan jumlah barang tiap supplier

        // Search by nama supplier
        if ($request->filled('search')) {
            $query->where('nama_supplier', 'LIKE', "%{$request->search}%");
        }

        // Kalau ada per_page → pakai pagination (untuk halaman tabel supplier)
        // Kalau tidak ada    → return semua (untuk kebutuhan dropdown di form barang)
        if ($request->filled('per_page')) {
            $suppliers = $query->orderBy('nama_supplier')->paginate($request->per_page);

            return response()->json([
                'success' => true,
                'data'    => $suppliers->items(),
                'meta'    => [
                    'total'        => $suppliers->total(),
                    'per_page'     => $suppliers->perPage(),
                    'current_page' => $suppliers->currentPage(),
                    'last_page'    => $suppliers->lastPage(),
                    'from'         => $suppliers->firstItem(),
                    'to'           => $suppliers->lastItem(),
                ],
            ]);
        }

        // Return semua untuk dropdown (tanpa pagination)
        return response()->json([
            'success' => true,
            'data'    => $query->orderBy('nama_supplier')->get(),
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // POST /api/supplier
    // ──────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'nama_supplier'   => 'required|string|max:50',
            'notelp_supplier' => 'required|string|max:20',
            'alamat_supplier' => 'nullable|string',
        ]);

        $supplier = Supplier::create($request->only([
            'nama_supplier',
            'notelp_supplier',
            'alamat_supplier',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Supplier berhasil ditambahkan.',
            'data'    => $supplier,
        ], 201);
    }

    // ──────────────────────────────────────────────────────────
    // GET /api/supplier/{id}
    // ──────────────────────────────────────────────────────────
    public function show($id)
    {
        // Load barang juga supaya bisa lihat barang apa saja dari supplier ini
        $supplier = Supplier::with('barang')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $supplier,
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // PUT /api/supplier/{id}
    // ──────────────────────────────────────────────────────────
    public function update(Request $request, $id)
    {
        $supplier = Supplier::findOrFail($id);

        $request->validate([
            'nama_supplier'   => 'sometimes|string|max:50',
            'notelp_supplier' => 'sometimes|string|max:20',
            'alamat_supplier' => 'nullable|string',
        ]);

        $supplier->update($request->only([
            'nama_supplier',
            'notelp_supplier',
            'alamat_supplier',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Supplier berhasil diupdate.',
            'data'    => $supplier,
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // DELETE /api/supplier/{id}
    // ──────────────────────────────────────────────────────────
    public function destroy($id)
    {
        $supplier = Supplier::findOrFail($id);

        // Cek apakah supplier masih punya barang
        // Kalau ada → tidak bisa dihapus
        if ($supplier->barang()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Supplier tidak bisa dihapus karena masih memiliki barang.',
            ], 422);
        }

        $supplier->delete();

        return response()->json([
            'success' => true,
            'message' => 'Supplier berhasil dihapus.',
        ]);
    }
}