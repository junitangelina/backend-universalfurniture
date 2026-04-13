<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\DetailBarang;
use App\Models\Notifikasi;
use App\Models\Admin;
use App\Models\Owner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BarangController extends Controller
{
    // ──────────────────────────────────────────────────────────
    // GET /api/barang
    // Query params:
    //   ?search=kursi        → cari nama_barang atau kategori
    //   ?kategori=sofa       → filter by kategori
    //   ?supplier=toko jaya  → filter by nama supplier
    //   ?stok_min=1          → hanya tampilkan yang stok menipis
    //   ?per_page=10         → jumlah per halaman (default 10)
    // ──────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = Barang::with(['detailBarang', 'supplier']);

        // Search by nama_barang atau kategori
        if ($request->filled('search')) {
            $keyword = $request->search;
            $query->where(function ($q) use ($keyword) {
                $q->where('nama_barang', 'LIKE', "%{$keyword}%")
                  ->orWhere('kategori', 'LIKE', "%{$keyword}%");
            });
        }

        // Filter by kategori
        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        // Filter by nama supplier
        if ($request->filled('supplier')) {
            $query->whereHas('supplier', function ($q) use ($request) {
                $q->where('nama_supplier', 'LIKE', "%{$request->supplier}%");
            });
        }

        // Filter hanya yang stok menipis
        if ($request->filled('stok_min') && $request->stok_min == 1) {
            $query->whereColumn('jumlah_stok', '<=', 'stok_min');
        }

        $barangs = $query->orderBy('nama_barang')
                         ->paginate($request->per_page ?? 10);

        return response()->json([
            'success' => true,
            'data'    => $barangs->items(), // fix: hanya items, bukan paginator mentah
            'meta'    => [
                'total'        => $barangs->total(),
                'per_page'     => $barangs->perPage(),
                'current_page' => $barangs->currentPage(),
                'last_page'    => $barangs->lastPage(),
                'from'         => $barangs->firstItem(), // "1" dari "1-10 of 100"
                'to'           => $barangs->lastItem(),  // "10" dari "1-10 of 100"
            ],
            'stok_menipis_count' => Barang::whereColumn('jumlah_stok', '<=', 'stok_min')->count(),
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // POST /api/barang
    // Content-Type: multipart/form-data (karena ada upload gambar)
    // Body:
    //   nama_barang, kategori, harga, jumlah_stok, stok_min,
    //   id_supplier, gambar (file), detail[0][merek], dst
    // ──────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'nama_barang'     => 'required|string|max:100',
            'kategori'        => 'required|string|max:50',
            'harga'           => 'required|integer|min:0',
            'jumlah_stok'     => 'required|integer|min:0',
            'stok_min'        => 'required|integer|min:0',
            'id_supplier'     => 'required|exists:supplier,id_supplier',
            'gambar'          => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'detail'          => 'nullable|array',
            'detail.*.merek'  => 'nullable|string',
            'detail.*.tipe'   => 'nullable|string',
            'detail.*.ukuran' => 'nullable|string',
            'detail.*.bahan'  => 'nullable|string', // ← tambah bahan
        ]);

        $gambarPath = null;
        if ($request->hasFile('gambar')) {
            // Simpan ke storage/app/public/barang/
            // Akses via: http://domain.com/storage/barang/namafile.jpg
            $gambarPath = $request->file('gambar')->store('barang', 'public');
        }

        $barang = Barang::create([
            'nama_barang' => $request->nama_barang,
            'kategori'    => $request->kategori,
            'harga'       => $request->harga,
            'jumlah_stok' => $request->jumlah_stok,
            'stok_min'    => $request->stok_min,
            'id_supplier' => $request->id_supplier,
            'gambar'      => $gambarPath,
        ]);

        if ($request->has('detail')) {
            foreach ($request->detail as $d) {
                $barang->detailBarang()->create([
                    'merek'  => $d['merek']  ?? '',
                    'tipe'   => $d['tipe']   ?? '',
                    'ukuran' => $d['ukuran'] ?? '',
                    'bahan'  => $d['bahan']  ?? null, // ← tambah bahan
                ]);
            }
        }

        // fix: tandai sebagai barang baru supaya tidak notif
        // kalau stok awal memang diisi 0 saat pertama create
        $this->checkAndNotifyStokMin($barang, isNewBarang: true);

        return response()->json([
            'success' => true,
            'message' => 'Barang berhasil ditambahkan.',
            'data'    => $barang->load(['detailBarang', 'supplier']),
        ], 201);
    }

    // ──────────────────────────────────────────────────────────
    // GET /api/barang/{id}
    // ──────────────────────────────────────────────────────────
    public function show($id)
    {
        // fix: tambah supplier & konsisten pakai wrapper success + data
        $barang = Barang::with(['detailBarang', 'supplier'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $barang,
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // PUT /api/barang/{id}
    // Content-Type: multipart/form-data (kalau ada ganti gambar)
    // ──────────────────────────────────────────────────────────
    public function update(Request $request, $id)
    {
        $barang = Barang::findOrFail($id);

        $request->validate([
            'nama_barang'     => 'sometimes|string|max:100',
            'kategori'        => 'sometimes|string|max:50',
            'harga'           => 'sometimes|integer|min:0',
            'jumlah_stok'     => 'sometimes|integer|min:0',
            'stok_min'        => 'sometimes|integer|min:0',
            'id_supplier'     => 'sometimes|exists:supplier,id_supplier',
            'gambar'          => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'detail'          => 'nullable|array',
            'detail.*.merek'  => 'nullable|string',
            'detail.*.tipe'   => 'nullable|string',
            'detail.*.ukuran' => 'nullable|string',
            'detail.*.bahan'  => 'nullable|string', // ← tambah bahan
        ]);

        $data = $request->only([
            'nama_barang', 'kategori', 'harga',
            'jumlah_stok', 'stok_min', 'id_supplier',
        ]);

        if ($request->hasFile('gambar')) {
            // Hapus gambar lama dari storage supaya tidak numpuk
            if ($barang->gambar) {
                Storage::disk('public')->delete($barang->gambar);
            }
            $data['gambar'] = $request->file('gambar')->store('barang', 'public');
        }

        $barang->update($data);

        if ($request->has('detail')) {
            // Hapus detail lama, ganti dengan yang baru
            $barang->detailBarang()->delete();
            foreach ($request->detail as $d) {
                $barang->detailBarang()->create([
                    'merek'  => $d['merek']  ?? '',
                    'tipe'   => $d['tipe']   ?? '',
                    'ukuran' => $d['ukuran'] ?? '',
                    'bahan'  => $d['bahan']  ?? null, // ← tambah bahan
                ]);
            }
        }

        $barang->refresh();

        // Cek reorder point hanya kalau stok yang diupdate
        if ($request->has('jumlah_stok')) {
            $this->checkAndNotifyStokMin($barang); // bukan barang baru
        }

        return response()->json([
            'success' => true,
            'message' => 'Barang berhasil diupdate.',
            'data'    => $barang->load(['detailBarang', 'supplier']),
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // DELETE /api/barang/{id}
    // ──────────────────────────────────────────────────────────
    public function destroy($id)
    {
        $barang = Barang::findOrFail($id);

        // fix: cek apakah barang masih dipakai di detail transaksi
        // kalau ada → tidak bisa dihapus karena akan merusak history
        if ($barang->detailBarang()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Barang tidak bisa dihapus karena masih memiliki data detail.',
            ], 422);
        }

        // Hapus gambar dari storage kalau ada
        if ($barang->gambar) {
            Storage::disk('public')->delete($barang->gambar);
        }

        $barang->delete();

        return response()->json([
            'success' => true,
            'message' => 'Barang berhasil dihapus.',
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // GET /api/barang-stok-minimum
    // Khusus list barang yang stoknya sudah menyentuh batas minimum
    // ──────────────────────────────────────────────────────────
    public function stokMinimum()
    {
        $barangs = Barang::with(['supplier'])
            ->whereColumn('jumlah_stok', '<=', 'stok_min')
            ->orderBy('jumlah_stok', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'count'   => $barangs->count(),
            'data'    => $barangs,
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // Private: Reorder Point Logic
    //
    // Dipanggil setiap kali stok barang berubah.
    // Kondisi trigger: jumlah_stok <= stok_min
    //
    // $isNewBarang → true saat dipanggil dari store()
    //   Kalau barang baru dan stok awal 0, skip notif
    //   karena stok 0 bukan hasil pengurangan, tapi memang belum diisi
    // ──────────────────────────────────────────────────────────
    private function checkAndNotifyStokMin(Barang $barang, bool $isNewBarang = false): void
    {
        // fix: skip notif kalau barang baru dengan stok awal 0
        if ($isNewBarang && $barang->jumlah_stok == 0) return;

        // Stok masih aman, tidak perlu notif
        if ($barang->jumlah_stok > $barang->stok_min) return;

        // Anti duplikat: jangan buat notif baru kalau yang lama belum dibaca
        $sudahAda = Notifikasi::where('notifiable_type', Barang::class)
            ->where('notifiable_id', $barang->id_barang)
            ->where('tipe', 'stok_minimum')
            ->where('is_read', false)
            ->exists();

        if ($sudahAda) return;

        $statusLabel = $barang->jumlah_stok <= 0 ? '⛔ HABIS' : '⚠️ STOK MINIMUM';
        $pesan = "Stok barang \"{$barang->nama_barang}\" saat ini tersisa {$barang->jumlah_stok} unit. "
               . "Batas minimum: {$barang->stok_min} unit. Segera lakukan pengadaan!";

        // Kirim ke semua Admin
        Admin::all()->each(function ($admin) use ($barang, $statusLabel, $pesan) {
            Notifikasi::create([
                'penerima_type'   => Admin::class,
                'penerima_id'     => $admin->id_admin,
                'notifiable_type' => Barang::class,
                'notifiable_id'   => $barang->id_barang,
                'judul'           => "{$statusLabel} - {$barang->nama_barang}",
                'pesan'           => $pesan,
                'tipe'            => 'stok_minimum',
                'is_read'         => false,
            ]);
        });

        // Kirim ke semua Owner
        Owner::all()->each(function ($owner) use ($barang, $statusLabel, $pesan) {
            Notifikasi::create([
                'penerima_type'   => Owner::class,
                'penerima_id'     => $owner->id_owner,
                'notifiable_type' => Barang::class,
                'notifiable_id'   => $barang->id_barang,
                'judul'           => "{$statusLabel} - {$barang->nama_barang}",
                'pesan'           => $pesan,
                'tipe'            => 'stok_minimum',
                'is_read'         => false,
            ]);
        });
    }
}