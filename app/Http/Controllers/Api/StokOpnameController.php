<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StokOpname;
use App\Models\StokOpnameDetail;
use App\Models\Barang;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StokOpnameController extends Controller
{
    // ──────────────────────────────────────────────────────────
    // GET /api/stokopname
    // Hanya bisa diakses Kepala Gudang
    //
    // Query params:
    //   ?tgl_dari=2025-01-01    → filter tanggal mulai
    //   ?tgl_sampai=2025-12-31  → filter tanggal akhir
    //   ?status=draft           → filter status (draft/selesai)
    //   ?per_page=10            → jumlah per halaman
    // ──────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = StokOpname::with(['details.barang', 'kepalaGudang']);

        if ($request->filled('tgl_dari')) {
            $query->whereDate('tgl_opname', '>=', $request->tgl_dari);
        }

        if ($request->filled('tgl_sampai')) {
            $query->whereDate('tgl_opname', '<=', $request->tgl_sampai);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $opnames = $query->orderBy('tgl_opname', 'desc')
                         ->paginate($request->per_page ?? 10);

        return response()->json([
            'success' => true,
            'data'    => $opnames->items(), // fix: pakai items() bukan paginator mentah
            'meta'    => [
                'total'        => $opnames->total(),
                'per_page'     => $opnames->perPage(),
                'current_page' => $opnames->currentPage(),
                'last_page'    => $opnames->lastPage(),
                'from'         => $opnames->firstItem(),
                'to'           => $opnames->lastItem(),
            ],
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // POST /api/stokopname
    // Buat stok opname baru dengan status "draft"
    //
    // Alur:
    // 1. Kepala gudang buat opname → status: draft
    // 2. Input hasil hitung fisik tiap barang (stok_asli)
    // 3. Sistem otomatis hitung selisih = stok_asli - stok_sistem
    // 4. Kalau sudah selesai → POST /selesai → stok barang disesuaikan
    //
    // Body:
    // {
    //   "tgl_opname": "2025-12-01",
    //   "id_kepala_gudang": 1,
    //   "details": [
    //     { "id_barang": 1, "stok_asli": 18, "keterangan": "ada yang rusak 2" },
    //     { "id_barang": 2, "stok_asli": 15, "keterangan": null }
    //   ]
    // }
    // ──────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'tgl_opname'           => 'required|date',
            'id_kepala_gudang'     => 'required|exists:kepalagudang,id_kepala_gudang',
            'details'              => 'required|array|min:1',
            'details.*.id_barang'  => 'required|exists:barang,id_barang',
            'details.*.stok_asli'  => 'required|integer|min:0',
            'details.*.keterangan' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Buat header opname dengan status draft
            $opname = StokOpname::create([
                'tgl_opname'       => $request->tgl_opname,
                'id_kepala_gudang' => $request->id_kepala_gudang,
                'status'           => 'draft',
            ]);

            foreach ($request->details as $d) {
                $barang = Barang::findOrFail($d['id_barang']);

                // stok_sistem  → ambil dari database saat ini
                // stok_asli    → hasil hitung fisik di gudang
                // selisih      → stok_asli - stok_sistem
                //   positif (+) → ada barang lebih dari catatan
                //   negatif (-) → ada barang kurang dari catatan
                //   nol (0)     → sesuai
                StokOpnameDetail::create([
                    'id_opname'   => $opname->id_opname,
                    'id_barang'   => $barang->id_barang,
                    'stok_sistem' => $barang->jumlah_stok,
                    'stok_asli'   => $d['stok_asli'],
                    'selisih'     => $d['stok_asli'] - $barang->jumlah_stok,
                    'keterangan'  => $d['keterangan'] ?? null,
                ]);
            }

            DB::commit();

              // ← tambah activity log
            ActivityLog::catat($request->user(), "Membuat Stok Opname tanggal {$opname->tgl_opname}", 'stok_opname');

            return response()->json([
                'success' => true,
                'message' => 'Stok Opname berhasil dibuat.',
                'data'    => $opname->load(['details.barang', 'kepalaGudang']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // ──────────────────────────────────────────────────────────
    // GET /api/stokopname/{id}
    // ──────────────────────────────────────────────────────────
    public function show($id)
    {
        $opname = StokOpname::with(['details.barang', 'kepalaGudang'])
                            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $opname,
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // PUT /api/stokopname/{id}
    // Update stok_asli selama masih status draft
    // Tidak bisa update kalau sudah selesai
    //
    // Body:
    // {
    //   "tgl_opname": "2025-12-02",     ← opsional
    //   "details": [
    //     { "id_barang": 1, "stok_asli": 17, "keterangan": "koreksi" }
    //   ]
    // }
    // ──────────────────────────────────────────────────────────
    public function update(Request $request, $id)
    {
        $opname = StokOpname::findOrFail($id);

        if ($opname->status === 'selesai') {
            return response()->json([
                'success' => false,
                'message' => 'Stok opname yang sudah selesai tidak bisa diubah.',
            ], 422);
        }

        $request->validate([
            'tgl_opname'           => 'sometimes|date',
            'details'              => 'sometimes|array',
            'details.*.id_barang'  => 'required|exists:barang,id_barang',
            'details.*.stok_asli'  => 'required|integer|min:0',
            'details.*.keterangan' => 'nullable|string',
        ]);

        if ($request->filled('tgl_opname')) {
            $opname->update(['tgl_opname' => $request->tgl_opname]);
        }

        if ($request->has('details')) {
            foreach ($request->details as $d) {
                $detail = $opname->details()
                                 ->where('id_barang', $d['id_barang'])
                                 ->first();

                if ($detail) {
                    $detail->update([
                        'stok_asli'  => $d['stok_asli'],
                        'selisih'    => $d['stok_asli'] - $detail->stok_sistem, // hitung ulang
                        'keterangan' => $d['keterangan'] ?? $detail->keterangan,
                    ]);
                }
            }
        }

         // ← tambah activity log
        ActivityLog::catat($request->user(), "Memperbarui Stok Opname #{$opname->id_opname}", 'stok_opname');
 

        return response()->json([
            'success' => true,
            'message' => 'Stok Opname berhasil diupdate.',
            'data'    => $opname->load(['details.barang', 'kepalaGudang']),
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // POST /api/stokopname/{id}/selesai
    // Finalisasi opname → update jumlah_stok barang sesuai stok_asli
    //
    // Yang terjadi saat selesai:
    // - stok barang di tabel barang disesuaikan ke stok_asli
    // - status opname berubah jadi "selesai"
    // - tidak bisa diubah lagi setelah ini
    // ──────────────────────────────────────────────────────────
    public function selesai($id)
    {
        $opname = StokOpname::with('details')->findOrFail($id);

        if ($opname->status === 'selesai') {
            return response()->json([
                'success' => false,
                'message' => 'Stok opname ini sudah selesai.',
            ], 422);
        }

        DB::transaction(function () use ($opname) {
            foreach ($opname->details as $detail) {
                // Update jumlah_stok barang sesuai hasil fisik
                Barang::where('id_barang', $detail->id_barang)
                      ->update(['jumlah_stok' => $detail->stok_asli]);
            }

            $opname->update(['status' => 'selesai']);
        });

         // ← tambah activity log
        ActivityLog::catat(request()->user(), "Menyelesaikan Stok Opname #{$opname->id_opname} — stok barang disesuaikan", 'stok_opname');

        return response()->json([
            'success' => true,
            'message' => 'Stok opname diselesaikan. Stok barang telah disesuaikan dengan hasil fisik.',
            'data'    => $opname->load(['details.barang', 'kepalaGudang']),
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // DELETE /api/stokopname/{id}
    // Hanya bisa hapus yang masih draft
    // ──────────────────────────────────────────────────────────
    public function destroy($id)
    {
        $opname = StokOpname::findOrFail($id);

        if ($opname->status === 'selesai') {
            return response()->json([
                'success' => false,
                'message' => 'Stok opname yang sudah selesai tidak bisa dihapus.',
            ], 422);
        }

        // detail ikut terhapus otomatis via cascade di migration
        $opname->delete();

        // ← tambah activity log
        ActivityLog::catat(request()->user(), "Menghapus Stok Opname #{$opname->id_opname}", 'stok_opname');
 
        
        return response()->json([
            'success' => true,
            'message' => 'Stok opname berhasil dihapus.',
        ]);
    }
    
}