<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\DetailPurchaseOrder;
use App\Models\Barang;
use App\Models\ActivityLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    // ──────────────────────────────────────────────────────────
    // GET /api/purchaseorder
    // Query params:
    //   ?search=PO-2025     → cari referensi_PO
    //   ?status=diajukan    → filter status
    //   ?per_page=10
    // ──────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = PurchaseOrder::with([
            'purchaseRequest.details.supplier',
            'details.barang',
            'details.supplier',
        ]);

        if ($request->filled('search')) {
            $query->where('referensi_PO', 'LIKE', "%{$request->search}%");
        }

        if ($request->filled('status')) {
            $query->where('status_PO', $request->status);
        }

        if ($request->boolean('untuk_transaksi')) {
            $query->where('status_PO', 'selesai');
        }

        $pos = $query->orderBy('tgl_PO', 'desc')->paginate($request->per_page ?? 10);

        return response()->json([
            'success' => true,
            'data'    => $pos->items(),
            'meta'    => [
                'total'        => $pos->total(),
                'per_page'     => $pos->perPage(),
                'current_page' => $pos->currentPage(),
                'last_page'    => $pos->lastPage(),
                'from'         => $pos->firstItem(),
                'to'           => $pos->lastItem(),
            ],
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // GET /api/purchaseorder/{id}
    // ──────────────────────────────────────────────────────────
    public function show($id)
    {
        $po = PurchaseOrder::with([
            'purchaseRequest.admin',
            'purchaseRequest.owner',
            'details.barang',
            'details.supplier',
        ])->findOrFail($id);

        return response()->json(['success' => true, 'data' => $po]);
    }

    // ──────────────────────────────────────────────────────────
    // PUT /api/purchaseorder/{id}
    // Update status PO atau isi kuantitas_terima & tgl_terima
    // saat barang datang
    //
    // Body (update status saja):
    // { "status_PO": "disetujui" }
    //
    // Body (saat barang datang):
    // {
    //   "status_PO": "selesai",
    //   "details": [
    //     {
    //       "id_detail_PO": 1,
    //       "kuantitasterimaPO": 10,
    //       "tgl_terima": "2025-12-10"
    //     }
    //   ]
    // }
    // ──────────────────────────────────────────────────────────
    public function update(Request $request, $id)
    {
        $po = PurchaseOrder::with('details')->findOrFail($id);

        if ($po->status_PO === 'selesai') {
            return response()->json([
                'success' => false,
                'message' => 'PO yang sudah selesai tidak bisa diubah.',
            ], 422);
        }

        $request->validate([
            'status_PO' => 'sometimes|in:diajukan,disetujui,ditolak,selesai',
            'details'   => 'sometimes|array',
            'details.*.id_detail_PO'      => 'required|exists:detail_purchase_order,id_detail_PO',
            'details.*.kuantitasterimaPO' => 'required|integer|min:0',
            'details.*.tgl_terima'        => 'required|date',
            'details.*.keterangan'        => 'nullable|string|max:255',
        ]);

        // Validasi keterangan dulu sebelum transaction dimulai
if ($request->has('details')) {
    foreach ($request->details as $d) {
        $detailPO = DetailPurchaseOrder::where('id_detail_PO', $d['id_detail_PO'])
                        ->where('id_PO', $po->id_PO)
                        ->firstOrFail();

        if ($d['kuantitasterimaPO'] < $detailPO->kuantitasbarangPO && empty($d['keterangan'])) {
            return response()->json([
                'success' => false,
                'message' => "Kuantitas barang \"{$detailPO->barang->nama_barang}\" tidak sesuai pesanan ({$detailPO->kuantitasbarangPO} unit). Wajib isi keterangan.",
                'field'   => "details.{$d['id_detail_PO']}.keterangan",
            ], 422);
        }
    }
}

     DB::beginTransaction();
try {
    // Update status PO
    if ($request->filled('status_PO')) {
        $po->update(['status_PO' => $request->status_PO]);
    }

    // Update detail (kuantitas & tanggal terima) saat barang datang
    if ($request->has('details')) {
        foreach ($request->details as $d) {
            $detailPO = DetailPurchaseOrder::where('id_detail_PO', $d['id_detail_PO'])
                            ->where('id_PO', $po->id_PO)
                            ->firstOrFail();

            $detailPO->update([
                'kuantitasterimaPO' => $d['kuantitasterimaPO'],
                'tgl_terima'        => $d['tgl_terima'],
                'keterangan'        => $d['keterangan'] ?? null,
            ]);
        }
    }
            // Kalau status selesai → update stok barang
            if ($request->status_PO === 'selesai') {
                $po->refresh();
                foreach ($po->details as $detail) {
                    if ($detail->kuantitasterimaPO > 0) {
                        Barang::where('id_barang', $detail->id_barang)
                              ->increment('jumlah_stok', $detail->kuantitasterimaPO);
                    }
                }
            }

            DB::commit();

            ActivityLog::catat(
                $request->user(),
                "Memperbarui Purchase Order {$po->referensi_PO} → status: {$po->status_PO}",
                'purchase_order'
            );

            return response()->json([
                'success' => true,
                'message' => 'Purchase Order berhasil diupdate.',
                'data'    => $po->load(['details.barang', 'details.supplier', 'purchaseRequest']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ──────────────────────────────────────────────────────────
    // DELETE /api/purchaseorder/{id}
    // Hanya bisa hapus PO yang masih diajukan
    // ──────────────────────────────────────────────────────────
    public function destroy(Request $request, $id)
    {
        $po = PurchaseOrder::findOrFail($id);

        if ($po->status_PO !== 'diajukan') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya PO dengan status diajukan yang bisa dihapus.',
            ], 422);
        }

        $referensi = $po->referensi_PO;
        $po->delete();

        ActivityLog::catat($request->user(), "Menghapus Purchase Order {$referensi}", 'purchase_order');

        return response()->json(['success' => true, 'message' => 'PO berhasil dihapus.']);
    }

    // ──────────────────────────────────────────────────────────
    // GET /api/purchaseorder/{id}/whatsapp
    // Generate link WA ke supplier
    // Dipanggil setelah PO dibuat dari PR
    // ──────────────────────────────────────────────────────────
    public function whatsapp($id)
    {
        $po = PurchaseOrder::with([
            'details.barang',
            'details.supplier',
            'purchaseRequest',
        ])->findOrFail($id);

        // Ambil supplier dari detail pertama (semua detail supplier sama)
        $supplier = $po->details->first()?->supplier;

        if (!$supplier) {
            return response()->json([
                'success' => false,
                'message' => 'Supplier tidak ditemukan.',
            ], 404);
        }

        // Format nomor WA
        $phone = preg_replace('/[^0-9]/', '', $supplier->notelp_supplier);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        // Susun pesan WA
        $itemsText = '';
        $total     = 0;
        foreach ($po->details as $item) {
            $subtotal   = $item->hargabarangPO * $item->kuantitasbarangPO;
            $total     += $subtotal;
            $itemsText .= "- {$item->barang->nama_barang} x{$item->kuantitasbarangPO} unit "
                        . "@ Rp " . number_format($item->hargabarangPO, 0, ',', '.') . "\n"
                        . "  Subtotal: Rp " . number_format($subtotal, 0, ',', '.') . "\n";
        }

        $pesan = "Halo Kak {$supplier->nama_supplier}, selamat siang 🙏\n\n"
       . "Kami dari Universal Furniture mau melakukan pemesanan barang nih. "
       . "Berikut detailnya:\n\n"
       . "No. PO  : {$po->referensi_PO}\n"
       . "Tanggal : " . now()->format('d/m/Y') . "\n\n"
       . $itemsText . "\n"
       . "Total keseluruhan: Rp " . number_format($total, 0, ',', '.') . "\n\n"
       . "Kalau ada yang perlu dikonfirmasi atau diklarifikasi, "
       . "silakan balas pesan ini ya Kak. Terima kasih banyak 🙏";

        return response()->json([
            'success'       => true,
            'supplier'      => $supplier->nama_supplier,
            'no_telepon'    => $supplier->notelp_supplier,
            'whatsapp_url'  => 'https://wa.me/' . $phone . '?text=' . urlencode($pesan),
        ]);
    }
    // ──────────────────────────────────────────────────────────
    // GET /api/purchaseorder/{id}/export-pdf
    // Download PDF dokumen Purchase Order
    // Frontend: axios GET dengan responseType: 'blob'
    // ──────────────────────────────────────────────────────────
    public function exportPdf(Request $request, $id)
    {
        $po = PurchaseOrder::with([
            'purchaseRequest.admin',
            'purchaseRequest.owner',
            'details.barang',
            'details.supplier',
        ])->findOrFail($id);
 
        $pdf = Pdf::loadView('pdf.purchase_order', ['po' => $po])
                  ->setPaper('a4', 'portrait');
 
        $filename = "Purchase-Order-{$po->referensi_PO}.pdf";
 
        ActivityLog::catat($request->user(), "Export PDF Purchase Order {$po->referensi_PO}", 'purchase_order');
 
        return $pdf->download($filename);
    }
}