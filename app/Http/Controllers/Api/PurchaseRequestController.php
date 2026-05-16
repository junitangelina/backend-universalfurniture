<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PurchaseRequest;
use App\Models\DetailPurchaseRequest;
use App\Models\Notifikasi;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Admin;
use App\Models\Owner;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseRequestController extends Controller
{
    // ──────────────────────────────────────────────────────────
    // GET /api/purchaserequest
    // Query params:
    //   ?search=PR-2025     → cari referensi_PR
    //   ?status=tertunda    → filter status
    //   ?per_page=10
    // ──────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = PurchaseRequest::with(['admin', 'owner', 'details.barang', 'details.supplier', 'purchaseOrder']);

        if ($request->filled('search')) {
            $query->where('referensi_PR', 'LIKE', "%{$request->search}%");
        }

        if ($request->filled('status')) {
            $query->where('status_PR', $request->status);
        }

        $prs = $query->orderBy('tgl_PR', 'desc')->paginate($request->per_page ?? 10);

        return response()->json([
            'success' => true,
            'data'    => $prs->items(),
            'meta'    => [
                'total'        => $prs->total(),
                'per_page'     => $prs->perPage(),
                'current_page' => $prs->currentPage(),
                'last_page'    => $prs->lastPage(),
                'from'         => $prs->firstItem(),
                'to'           => $prs->lastItem(),
            ],
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // POST /api/purchaserequest
    // Admin atau Owner bisa buat PR
    // 1 PR untuk 1 supplier yang sama (karena nanti diarahkan ke WA supplier)
    //
    // Body:
    // {
    //   "tgl_PR": "2025-12-01",
    //   "details": [
    //     {
    //       "id_barang": 1,
    //       "id_supplier": 1,         ← harus sama semua (1 supplier)
    //       "hargabarangPR": 150000,  ← input manual, harga beli ke supplier
    //       "kuantitasbarangPR": 10
    //     },
    //     {
    //       "id_barang": 2,
    //       "id_supplier": 1,         ← supplier yang sama
    //       "hargabarangPR": 200000,
    //       "kuantitasbarangPR": 5
    //     }
    //   ]
    // }
    // ──────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'tgl_PR'                      => 'required|date',
            'details'                     => 'required|array|min:1',
            'details.*.id_barang'         => 'required|exists:barang,id_barang',
            'details.*.id_supplier'       => 'required|exists:supplier,id_supplier',
            'details.*.hargabarangPR'     => 'required|numeric|min:0',
            'details.*.kuantitasbarangPR' => 'required|integer|min:1',
        ]);

        // Validasi: semua detail harus pakai supplier yang sama
        $supplierIds = collect($request->details)->pluck('id_supplier')->unique();
        if ($supplierIds->count() > 1) {
            return response()->json([
                'success' => false,
                'message' => '1 Purchase Request hanya boleh untuk 1 supplier yang sama.',
            ], 422);
        }

        $user = $request->user();

        DB::beginTransaction();
        try {
            $pr = PurchaseRequest::create([
                'tgl_PR'    => $request->tgl_PR,
                'status_PR' => 'tertunda',
                'id_admin'  => $user instanceof Admin ? $user->getKey() : null,
                'id_owner'  => $user instanceof Owner ? $user->getKey() : null,
            ]);

            foreach ($request->details as $d) {
                DetailPurchaseRequest::create([
                    'id_PR'               => $pr->id_PR,
                    'id_barang'           => $d['id_barang'],
                    'id_supplier'         => $d['id_supplier'],
                    'hargabarangPR'       => $d['hargabarangPR'],
                    'kuantitasbarangPR'   => $d['kuantitasbarangPR'],
                ]);
            }

            DB::commit();

            // Notif ke Owner: ada PR baru menunggu persetujuan
            // (kalau yang buat admin, notif ke owner)
            if ($user instanceof Admin) {
                Owner::all()->each(fn($o) => Notifikasi::create([
                    'penerima_type'   => Owner::class,
                    'penerima_id'     => $o->id_owner,
                    'notifiable_type' => PurchaseRequest::class,
                    'notifiable_id'   => $pr->id_PR,
                    'judul'           => '📋 Purchase Request Baru',
                    'pesan'           => "PR {$pr->referensi_PR} dari Admin menunggu persetujuan Anda.",
                    'tipe'            => 'purchase_request',
                    'is_read'         => false,
                ]));
            }

            ActivityLog::catat($user, "Membuat Purchase Request {$pr->referensi_PR}", 'purchase_request');

            return response()->json([
                'success' => true,
                'message' => 'Purchase Request berhasil dibuat.',
                'data'    => $pr->load(['details.barang', 'details.supplier', 'admin', 'owner']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ──────────────────────────────────────────────────────────
    // GET /api/purchaserequest/{id}
    // ──────────────────────────────────────────────────────────
    public function show($id)
    {
        $pr = PurchaseRequest::with([
            'admin', 'owner',
            'details.barang',
            'details.supplier',
            'purchaseOrder.details.barang',
        ])->findOrFail($id);

        return response()->json(['success' => true, 'data' => $pr]);
    }

    // ──────────────────────────────────────────────────────────
    // PUT /api/purchaserequest/{id}
    // Hanya bisa edit kalau status masih tertunda
    // ──────────────────────────────────────────────────────────
    public function update(Request $request, $id)
    {
        $pr = PurchaseRequest::findOrFail($id);

        if ($pr->status_PR !== 'tertunda') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya PR dengan status tertunda yang bisa diubah.',
            ], 422);
        }

        $request->validate([
            'tgl_PR'  => 'sometimes|date',
            'details' => 'sometimes|array|min:1',
            'details.*.id_barang'         => 'required|exists:barang,id_barang',
            'details.*.id_supplier'       => 'required|exists:supplier,id_supplier',
            'details.*.hargabarangPR'     => 'required|numeric|min:0',
            'details.*.kuantitasbarangPR' => 'required|integer|min:1',
        ]);

        if ($request->filled('tgl_PR')) {
            $pr->update(['tgl_PR' => $request->tgl_PR]);
        }

        if ($request->has('details')) {
            // Validasi 1 supplier
            $supplierIds = collect($request->details)->pluck('id_supplier')->unique();
            if ($supplierIds->count() > 1) {
                return response()->json([
                    'success' => false,
                    'message' => '1 Purchase Request hanya boleh untuk 1 supplier yang sama.',
                ], 422);
            }

            // Hapus detail lama, ganti baru
            $pr->details()->delete();
            foreach ($request->details as $d) {
                DetailPurchaseRequest::create([
                    'id_PR'             => $pr->id_PR,
                    'id_barang'         => $d['id_barang'],
                    'id_supplier'       => $d['id_supplier'],
                    'hargabarangPR'     => $d['hargabarangPR'],
                    'kuantitasbarangPR' => $d['kuantitasbarangPR'],
                ]);
            }
        }

        ActivityLog::catat($request->user(), "Memperbarui Purchase Request {$pr->referensi_PR}", 'purchase_request');

        return response()->json([
            'success' => true,
            'message' => 'PR berhasil diupdate.',
            'data'    => $pr->load(['details.barang', 'details.supplier']),
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // DELETE /api/purchaserequest/{id}
    // Hanya bisa hapus kalau masih tertunda
    // ──────────────────────────────────────────────────────────
    public function destroy(Request $request, $id)
    {
        $pr = PurchaseRequest::findOrFail($id);

        if ($pr->status_PR !== 'tertunda') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya PR tertunda yang bisa dihapus.',
            ], 422);
        }

        $referensi = $pr->referensi_PR;
        $pr->delete();

        ActivityLog::catat($request->user(), "Menghapus Purchase Request {$referensi}", 'purchase_request');

        return response()->json(['success' => true, 'message' => 'PR berhasil dihapus.']);
    }

    // ──────────────────────────────────────────────────────────
    // POST /api/purchaserequest/{id}/approve
    // HANYA OWNER yang bisa approve
    // ──────────────────────────────────────────────────────────
    public function approve(Request $request, $id)
    {
        // Cek role — hanya owner yang boleh
        if (!($request->user() instanceof Owner)) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Owner yang bisa menyetujui Purchase Request.',
            ], 403);
        }

        $pr = PurchaseRequest::findOrFail($id);

        if ($pr->status_PR !== 'tertunda') {
            return response()->json([
                'success' => false,
                'message' => 'PR ini sudah diproses sebelumnya.',
            ], 422);
        }

        $pr->update(['status_PR' => 'disetujui']);

        // Notif ke Admin yang buat PR
        if ($pr->id_admin) {
            Notifikasi::create([
                'penerima_type'   => Admin::class,
                'penerima_id'     => $pr->id_admin,
                'notifiable_type' => PurchaseRequest::class,
                'notifiable_id'   => $pr->id_PR,
                'judul'           => '✅ Purchase Request Disetujui',
                'pesan'           => "PR {$pr->referensi_PR} Anda telah disetujui. Silakan buat Purchase Order.",
                'tipe'            => 'purchase_request',
                'is_read'         => false,
            ]);
        }

        ActivityLog::catat($request->user(), "Menyetujui Purchase Request {$pr->referensi_PR}", 'purchase_request');

        return response()->json([
            'success' => true,
            'message' => 'Purchase Request berhasil disetujui.',
            'data'    => $pr->load(['details.barang', 'details.supplier', 'admin', 'owner']),
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // POST /api/purchaserequest/{id}/reject
    // HANYA OWNER yang bisa reject
    // ──────────────────────────────────────────────────────────
    public function reject(Request $request, $id)
    {
        // Cek role — hanya owner yang boleh
        if (!($request->user() instanceof Owner)) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Owner yang bisa menolak Purchase Request.',
            ], 403);
        }

        $pr = PurchaseRequest::findOrFail($id);

        if ($pr->status_PR !== 'tertunda') {
            return response()->json([
                'success' => false,
                'message' => 'PR ini sudah diproses sebelumnya.',
            ], 422);
        }

        $request->validate([
            'alasan' => 'required|string|min:5',
        ]);

        $pr->update(['status_PR' => 'ditolak']);

        // Notif ke Admin yang buat PR
        if ($pr->id_admin) {
            Notifikasi::create([
                'penerima_type'   => Admin::class,
                'penerima_id'     => $pr->id_admin,
                'notifiable_type' => PurchaseRequest::class,
                'notifiable_id'   => $pr->id_PR,
                'judul'           => '❌ Purchase Request Ditolak',
                'pesan'           => "PR {$pr->referensi_PR} ditolak. Alasan: {$request->alasan}",
                'tipe'            => 'purchase_request',
                'is_read'         => false,
            ]);
        }

        ActivityLog::catat($request->user(), "Menolak Purchase Request {$pr->referensi_PR}. Alasan: {$request->alasan}", 'purchase_request');

        return response()->json([
            'success' => true,
            'message' => 'Purchase Request berhasil ditolak.',
            'data'    => $pr,
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // POST /api/purchaserequest/{id}/buat-po
    // Konversi PR yang sudah disetujui → jadi PO otomatis
    // Semua data di-copy dari PR, tidak perlu input ulang
    // ──────────────────────────────────────────────────────────
    public function buatPO(Request $request, $id)
    {
        $pr = PurchaseRequest::with('details')->findOrFail($id);

        if ($pr->status_PR !== 'disetujui') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya PR yang sudah disetujui yang bisa dikonversi ke PO.',
            ], 422);
        }

        // Cek apakah PO sudah pernah dibuat dari PR ini
        if ($pr->purchaseOrder) {
            return response()->json([
                'success' => false,
                'message' => 'PO sudah pernah dibuat dari PR ini.',
                'data'    => $pr->purchaseOrder,
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Auto-generate referensi_PO
            $lastPO = \App\Models\PurchaseOrder::orderBy('id_PO', 'desc')->first();
            $next   = $lastPO ? ($lastPO->id_PO + 1) : 1;
            $refPO  = 'PO-' . date('Y') . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);

            // Buat PO
            $po = \App\Models\PurchaseOrder::create([
                'referensi_PO' => $refPO,
                'tgl_PO'       => today(),
                'status_PO'    => 'diajukan',
                'id_PR'        => $pr->id_PR,
            ]);

            // Copy semua detail dari PR ke PO
            // hargabarangPO otomatis dari hargabarangPR
            foreach ($pr->details as $detailPR) {
                \App\Models\DetailPurchaseOrder::create([
                    'id_PO'              => $po->id_PO,
                    'id_barang'          => $detailPR->id_barang,
                    'id_supplier'        => $detailPR->id_supplier,
                    'hargabarangPO'      => $detailPR->hargabarangPR,     // ← copy dari PR
                    'kuantitasbarangPO'  => $detailPR->kuantitasbarangPR, // ← copy dari PR
                    'kuantitasterimaPO'  => null, // kosong dulu, diisi saat barang datang
                    'tgl_terima'         => null, // kosong dulu, diisi saat barang datang
                ]);
            }

            DB::commit();

            ActivityLog::catat($request->user(), "Mengkonversi PR {$pr->referensi_PR} menjadi PO {$po->referensi_PO}", 'purchase_order');

            return response()->json([
                'success' => true,
                'message' => "PR {$pr->referensi_PR} berhasil dikonversi menjadi PO {$po->referensi_PO}.",
                'data'    => $po->load(['details.barang', 'details.supplier', 'purchaseRequest']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ──────────────────────────────────────────────────────────
    // GET /api/purchaserequest/{id}/export-pdf
    // Download PDF dokumen Purchase Request
    // Frontend: axios GET dengan responseType: 'blob'
    // ──────────────────────────────────────────────────────────
    public function exportPdf(Request $request, $id)
    {
        $pr = PurchaseRequest::with([
            'admin',
            'owner',
            'details.barang',
            'details.supplier',
        ])->findOrFail($id);
 
        $pdf = Pdf::loadView('pdf.purchase_request', ['pr' => $pr])
                  ->setPaper('a4', 'portrait');
 
        $filename = "Purchase-Request-{$pr->referensi_PR}.pdf";
 
        ActivityLog::catat($request->user(), "Export PDF Purchase Request {$pr->referensi_PR}", 'purchase_request');
 
        return $pdf->download($filename);
    }
}