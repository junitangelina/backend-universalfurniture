<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use App\Models\DetailTransaksi;
use App\Models\Barang;
use App\Models\PurchaseOrder;
use App\Models\DetailPurchaseOrder;
use App\Models\Notifikasi;
use App\Models\Admin;
use App\Models\Owner;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TransaksiController extends Controller
{
    // ──────────────────────────────────────────────────────────
    // GET /api/transaksi
    // ──────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = Transaksi::with(['details.barang', 'purchaseOrder']);

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('jenis')) {
            $query->where('jenis_transaksi', $request->jenis);
        }

        if ($request->filled('metode')) {
            $query->where('metode_bayar', $request->metode);
        }

        if ($request->filled('status_bayar')) {
            $query->whereHas('details', function ($q) use ($request) {
                $q->where('status_bayar', $request->status_bayar);
            });
        }

        if ($request->filled('tgl_dari')) {
            $query->whereDate('tgl_transaksi', '>=', $request->tgl_dari);
        }

        if ($request->filled('tgl_sampai')) {
            $query->whereDate('tgl_transaksi', '<=', $request->tgl_sampai);
        }

        if ($request->boolean('export')) {
            $data = $query->orderBy('tgl_transaksi', 'desc')->get();
            return response()->json([
                'success' => true,
                'data'    => $data,
                'total'   => $data->count(),
            ]);
        }

        $transaksis = $query->orderBy('tgl_transaksi', 'desc')
                            ->paginate($request->per_page ?? 10);

        return response()->json([
            'success' => true,
            'data'    => $transaksis->items(),
            'meta'    => [
                'total'        => $transaksis->total(),
                'per_page'     => $transaksis->perPage(),
                'current_page' => $transaksis->currentPage(),
                'last_page'    => $transaksis->lastPage(),
                'from'         => $transaksis->firstItem(),
                'to'           => $transaksis->lastItem(),
            ],
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // POST /api/transaksi
    //
    // SKENARIO 1 - Transaksi MASUK (customer beli, 1 barang):
    // {
    //   "nama_transaksi": "Penjualan Kursi",
    //   "tgl_transaksi": "2025-12-01",
    //   "jenis_transaksi": "masuk",
    //   "metode_bayar": "cash",
    //   "id_barang": 1,
    //   "kuantitas": 2,
    //   "jatuh_tempo": null
    // }
    //
    // SKENARIO 2 - Transaksi KELUAR (dari PO yang sudah selesai):
    // {
    //   "nama_transaksi": "Pembelian dari Supplier",
    //   "tgl_transaksi": "2025-12-01",
    //   "jenis_transaksi": "keluar",
    //   "metode_bayar": "transfer_bank",
    //   "id_PO": 3
    //   ← backend otomatis ambil semua barang dari PO
    //   ← status PO diubah jadi 'sudah_diproses' setelah transaksi berhasil
    // }
    // ──────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'nama_transaksi'   => 'required|string|max:100',
            'tgl_transaksi'    => 'required|date',
            'jenis_transaksi'  => 'required|in:masuk,keluar',
            'metode_bayar'     => 'required|in:cash,transfer_bank',
            'bukti_pembayaran' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',

            // Untuk transaksi masuk (1 barang)
            'id_barang'   => 'nullable|exists:barang,id_barang',
            'kuantitas'   => 'nullable|integer|min:1',
            'jatuh_tempo' => 'nullable|date|after:tgl_transaksi',

            // Untuk transaksi keluar (dari PO)
            'id_PO' => 'nullable|exists:purchase_order,id_PO',
        ]);

        // Validasi sesuai jenis transaksi
        if ($request->jenis_transaksi === 'masuk') {
            if (!$request->filled('id_barang') || !$request->filled('kuantitas')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi masuk wajib mengisi id_barang dan kuantitas.',
                ], 422);
            }
        } else {
            if (!$request->filled('id_PO')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi keluar wajib menyertakan id_PO.',
                ], 422);
            }

            // ✅ Validasi: PO harus berstatus 'selesai' untuk bisa dibuat transaksi
            $po = PurchaseOrder::find($request->id_PO);
            if (!$po || $po->status_PO !== 'selesai') {
                return response()->json([
                    'success' => false,
                    'message' => 'PO yang dipilih belum selesai atau tidak ditemukan. Hanya PO berstatus "selesai" yang bisa dibuat transaksi.',
                ], 422);
            }
        }

        return DB::transaction(function () use ($request) {

            // Upload bukti pembayaran
            $buktiPath = null;
            if ($request->hasFile('bukti_pembayaran')) {
                $buktiPath = $request->file('bukti_pembayaran')
                                     ->store('bukti_transaksi', 'public');
            }

            // Simpan header transaksi
            $transaksi = Transaksi::create([
                'nama_transaksi'  => $request->nama_transaksi,
                'tgl_transaksi'   => $request->tgl_transaksi,
                'jenis_transaksi' => $request->jenis_transaksi,
                'metode_bayar'    => $request->metode_bayar,
                'id_PO'           => $request->id_PO ?? null,
            ]);

            // ── SKENARIO 1: Transaksi MASUK (customer beli) ───
            if ($request->jenis_transaksi === 'masuk') {
                $barang = Barang::lockForUpdate()->findOrFail($request->id_barang);

                if ($barang->jumlah_stok < $request->kuantitas) {
                    throw new \Exception(
                        "Stok \"{$barang->nama_barang}\" tidak cukup. " .
                        "Tersedia: {$barang->jumlah_stok}, dibutuhkan: {$request->kuantitas}."
                    );
                }

                $barang->decrement('jumlah_stok', $request->kuantitas);

                $statusBayar = $request->filled('jatuh_tempo') ? 'pending' : 'lunas';

                DetailTransaksi::create([
                    'id_transaksi'     => $transaksi->id_transaksi,
                    'id_barang'        => $request->id_barang,
                    'bukti_pembayaran' => $buktiPath,
                    'kuantitas'        => $request->kuantitas,
                    'harga_satuan'     => $barang->harga,
                    'subtotal'         => $request->kuantitas * $barang->harga,
                    'jatuh_tempo'      => $request->jatuh_tempo ?? null,
                    'status_bayar'     => $statusBayar,
                    'tgl_bayar'        => $statusBayar === 'lunas' ? today() : null,
                ]);

                // Cek reorder point setelah stok berkurang
                $barang->refresh();
                if ($barang->jumlah_stok <= $barang->stok_min) {
                    $this->notifikasiStokMin($barang);
                }

            // ── SKENARIO 2: Transaksi KELUAR (dari PO selesai) ─
            } else {
                $detailsPO = DetailPurchaseOrder::where('id_PO', $request->id_PO)->get();

                if ($detailsPO->isEmpty()) {
                    throw new \Exception("Purchase Order tidak memiliki detail barang.");
                }

                foreach ($detailsPO as $detailPO) {
                    $barang = Barang::lockForUpdate()->findOrFail($detailPO->id_barang);

                    // Stok bertambah karena barang datang dari supplier
                    $barang->increment('jumlah_stok', $detailPO->kuantitasbarangPO);

                    DetailTransaksi::create([
                        'id_transaksi'     => $transaksi->id_transaksi,
                        'id_barang'        => $detailPO->id_barang,
                        'bukti_pembayaran' => $buktiPath,
                        'kuantitas'        => $detailPO->kuantitasbarangPO,
                        'harga_satuan'     => $detailPO->hargabarangPO,
                        'subtotal'         => $detailPO->kuantitasbarangPO * $detailPO->hargabarangPO,
                        'jatuh_tempo'      => null,
                        'status_bayar'     => 'lunas',
                        'tgl_bayar'        => today(),
                    ]);
                }

                // ✅ Update status PO jadi 'sudah_diproses' supaya tidak bisa dipilih lagi
                PurchaseOrder::where('id_PO', $request->id_PO)
                    ->update(['status_PO' => 'sudah_diproses']);
            }

            ActivityLog::catat(
                $request->user(),
                "Membuat transaksi '{$transaksi->nama_transaksi}' ({$transaksi->jenis_transaksi})",
                'transaksi'
            );

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil disimpan.',
                'data'    => $transaksi->load(['details.barang', 'purchaseOrder']),
            ], 201);
        });
    }

    // ──────────────────────────────────────────────────────────
    // GET /api/transaksi/{id}
    // ──────────────────────────────────────────────────────────
    public function show($id)
    {
        $transaksi = Transaksi::with(['details.barang', 'purchaseOrder'])
                              ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $transaksi,
            'total'   => $transaksi->total,
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // PUT /api/transaksi/{id}
    // ──────────────────────────────────────────────────────────
    public function update(Request $request, $id)
    {
        $transaksi = Transaksi::findOrFail($id);

        $request->validate([
            'nama_transaksi' => 'sometimes|string|max:100',
            'tgl_transaksi'  => 'sometimes|date',
            'metode_bayar'   => 'sometimes|in:cash,transfer_bank',
            'jatuh_tempo'    => 'nullable|date',
        ]);

        $transaksi->update($request->only([
            'nama_transaksi',
            'tgl_transaksi',
            'metode_bayar',
        ]));

        if ($request->has('jatuh_tempo') && $transaksi->details()->exists()) {
            $transaksi->details()->update([
                'jatuh_tempo'  => $request->jatuh_tempo,
                'status_bayar' => $request->jatuh_tempo ? 'pending' : 'lunas',
            ]);
        }

        ActivityLog::catat($request->user(), "Memperbarui transaksi '{$transaksi->nama_transaksi}'", 'transaksi');

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil diupdate.',
            'data'    => $transaksi->load(['details.barang']),
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // PATCH /api/transaksi/{id}/bayar
    // ──────────────────────────────────────────────────────────
    public function bayar(Request $request, $id)
    {
        $transaksi = Transaksi::with('details')->findOrFail($id);

        if ($transaksi->details->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Detail transaksi tidak ditemukan.',
            ], 404);
        }

        $belumLunas = $transaksi->details->where('status_bayar', '!=', 'lunas');
        if ($belumLunas->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi ini sudah lunas.',
            ], 422);
        }

        $request->validate([
            'bukti_pembayaran' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $buktiPath = null;
        if ($request->hasFile('bukti_pembayaran')) {
            $detailPertama = $transaksi->details->first();
            if ($detailPertama->bukti_pembayaran) {
                Storage::disk('public')->delete($detailPertama->bukti_pembayaran);
            }
            $buktiPath = $request->file('bukti_pembayaran')->store('bukti_transaksi', 'public');
        }

        foreach ($belumLunas as $detail) {
            $detail->update([
                'status_bayar'     => 'lunas',
                'tgl_bayar'        => today(),
                'bukti_pembayaran' => $buktiPath ?? $detail->bukti_pembayaran,
            ]);
        }

        ActivityLog::catat($request->user(), "Mencatat pembayaran transaksi '{$transaksi->nama_transaksi}'", 'transaksi');

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran berhasil dicatat. Transaksi lunas.',
            'data'    => $transaksi->load(['details.barang']),
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // DELETE /api/transaksi/{id}
    // ──────────────────────────────────────────────────────────
    public function destroy($id)
    {
        $transaksi = Transaksi::with('details')->findOrFail($id);

        DB::transaction(function () use ($transaksi) {

            foreach ($transaksi->details as $detail) {
                $barang = Barang::findOrFail($detail->id_barang);

                if ($transaksi->jenis_transaksi === 'masuk') {
                    $barang->increment('jumlah_stok', $detail->kuantitas);
                } else {
                    $barang->decrement('jumlah_stok', $detail->kuantitas);
                }

                if ($detail->bukti_pembayaran) {
                    Storage::disk('public')->delete($detail->bukti_pembayaran);
                }
            }

            // ✅ Kalau transaksi keluar dihapus, kembalikan status PO ke 'selesai'
            // supaya PO bisa dipilih lagi untuk membuat transaksi baru
            if ($transaksi->jenis_transaksi === 'keluar' && $transaksi->id_PO) {
                PurchaseOrder::where('id_PO', $transaksi->id_PO)
                    ->update(['status_PO' => 'selesai']);
            }

            $transaksi->delete();
        });

        ActivityLog::catat(request()->user(), "Menghapus transaksi '{$transaksi->nama_transaksi}'", 'transaksi');

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil dihapus dan stok barang dikembalikan.',
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // GET /api/purchaseorder/{id}/for-transaksi
    // Preview data PO untuk auto-fill form transaksi keluar
    // Frontend panggil ini saat user pilih PO di dropdown
    // ──────────────────────────────────────────────────────────
    public function previewDariPO($id)
    {
        $po = PurchaseOrder::with(['details.barang', 'details.supplier'])->findOrFail($id);

        // ✅ FIX: hanya PO berstatus 'selesai' yang bisa dipakai untuk transaksi
        // (bukan menolak yang selesai, tapi menolak yang BUKAN selesai)
        if ($po->status_PO !== 'selesai') {
            return response()->json([
                'success' => false,
                'message' => 'PO ini belum selesai atau sudah pernah dibuat transaksinya. Hanya PO berstatus "selesai" yang dapat digunakan.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id_PO'        => $po->id_PO,
                'referensi_PO' => $po->referensi_PO,
                'tgl_PO'       => $po->tgl_PO,
                'items' => $po->details->map(fn($d) => [
                    'id_barang'    => $d->id_barang,
                    'nama_barang'  => $d->barang->nama_barang,
                    'kuantitas'    => $d->kuantitasbarangPO,
                    'harga_satuan' => $d->hargabarangPO,
                    'subtotal'     => $d->kuantitasbarangPO * $d->hargabarangPO,
                ]),
                'total' => $po->details->sum(fn($d) => $d->kuantitasbarangPO * $d->hargabarangPO),
            ],
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // Private: Notifikasi reorder point
    // ──────────────────────────────────────────────────────────
    private function notifikasiStokMin(Barang $barang): void
    {
        $sudahAda = Notifikasi::where('notifiable_type', Barang::class)
            ->where('notifiable_id', $barang->id_barang)
            ->where('tipe', 'stok_minimum')
            ->where('is_read', false)
            ->exists();

        if ($sudahAda) return;

        $label = $barang->jumlah_stok <= 0 ? '⛔ STOK HABIS' : '⚠️ STOK MINIMUM';
        $pesan = "Stok \"{$barang->nama_barang}\" tinggal {$barang->jumlah_stok} unit "
               . "(batas minimum: {$barang->stok_min} unit). Segera lakukan pengadaan!";

        Admin::all()->each(fn($a) => Notifikasi::create([
            'penerima_type'   => Admin::class,
            'penerima_id'     => $a->id_admin,
            'notifiable_type' => Barang::class,
            'notifiable_id'   => $barang->id_barang,
            'judul'           => "{$label} - {$barang->nama_barang}",
            'pesan'           => $pesan,
            'tipe'            => 'stok_minimum',
            'is_read'         => false,
        ]));

        Owner::all()->each(fn($o) => Notifikasi::create([
            'penerima_type'   => Owner::class,
            'penerima_id'     => $o->id_owner,
            'notifiable_type' => Barang::class,
            'notifiable_id'   => $barang->id_barang,
            'judul'           => "{$label} - {$barang->nama_barang}",
            'pesan'           => $pesan,
            'tipe'            => 'stok_minimum',
            'is_read'         => false,
        ]));
    }
}