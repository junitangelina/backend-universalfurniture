<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\DetailBarang;
use Illuminate\Http\Request;

class BarangController extends Controller
{
    // GET /api/barang
    public function index()
    {
        $barangs = Barang::with('detailBarang')->get();
        return response()->json($barangs);
    }

    // POST /api/barang
    public function store(Request $request)
    {
        // Validasi sederhana
        $request->validate([
            'nama_barang' => 'required|string',
            'kategori' => 'required|string',
            'jumlah_stok' => 'required|integer',
            'stok_min' => 'required|integer',
            'id_supplier' => 'required|integer',
            'gambar' => 'nullable|string',
            'detail' => 'nullable|array'
        ]);

        $barang = Barang::create($request->only([
            'nama_barang', 'kategori', 'jumlah_stok', 'stok_min', 'id_supplier', 'gambar'
        ]));

        if($request->has('detail')) {
    foreach($request->detail as $d) {
        $barang->detailBarang()->create([
            'merek' => $d['merek'] ?? '',
            'tipe' => $d['tipe'] ?? '',
            'ukuran' => $d['ukuran'] ?? ''
        ]);
    }
}


        return response()->json($barang->load('detailBarang'), 201);
    }

    // GET /api/barang/{id}
    public function show($id)
    {
        $barang = Barang::with('detailBarang')->findOrFail($id);
        return response()->json($barang);
    }

    // PUT / PATCH /api/barang/{id}
    public function update(Request $request, $id)
    {
        $barang = Barang::findOrFail($id);
        $barang->update($request->only([
            'nama_barang', 'kategori', 'jumlah_stok', 'stok_min', 'id_supplier', 'gambar'
        ]));

        // Update detail barang (opsional: hapus dan buat baru)
        if($request->has('detail')) {
            // hapus dulu detail lama
            $barang->detailBarang()->delete();

           foreach($request->detail as $d) {
    $barang->detailBarang()->create([
        'merek' => $d['merek'] ?? '',
        'tipe' => $d['tipe'] ?? '',
        'ukuran' => $d['ukuran'] ?? ''
    ]);
}
        }

        return response()->json($barang->load('detailBarang'));
    }

    // DELETE /api/barang/{id}
    public function destroy($id)
    {
        $barang = Barang::findOrFail($id);
        $barang->delete(); // otomatis hapus detail kalau pakai cascade di migration
        return response()->json(null, 204);
    }
}
