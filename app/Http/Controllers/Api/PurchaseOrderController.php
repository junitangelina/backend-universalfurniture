<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\DetailPurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Barang;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    // GET /api/purchaseorder
    public function index()
    {
        $po = PurchaseOrder::with(['purchaseRequest', 'details.barang', 'details.supplier'])
                ->orderBy('tgl_PO', 'desc')
                ->get();

        return response()->json($po);
    }

    // POST /api/purchaseorder
    public function store(Request $request)
    {
        $request->validate([
            'id_PR' => 'required|integer',
            'tgl_PO' => 'required|date',
            'details' => 'required|array',
        ]);

        // Pastikan PR ada
        $pr = PurchaseRequest::findOrFail($request->id_PR);

        // Pastikan PR SUDAH DISETUJUI
        if ($pr->status_PR !== 'disetujui') {
            return response()->json([
                'error' => 'Purchase Request belum disetujui, tidak bisa membuat PO.'
            ], 400);
        }

        DB::beginTransaction();
        try {

            // Generate Referensi PO (misal: PO-2025-0001)
            $lastPO = PurchaseOrder::orderBy('id_PO', 'desc')->first();
            $nextNumber = $lastPO ? $lastPO->id_PO + 1 : 1;
            $ref = 'PO-' . date('Y') . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            // Buat PO
            $po = PurchaseOrder::create([
                'referensi_PO' => $ref,
                'tgl_PO' => $request->tgl_PO,
                'status_PO' => 'dibuat', // default
                'id_PR' => $request->id_PR,
            ]);

            // Simpan detail PO
            foreach ($request->details as $d) {
                $barang = Barang::findOrFail($d['id_barang']);
                $supplier = Supplier::findOrFail($d['id_supplier']);

                DetailPurchaseOrder::create([
                    'id_PO' => $po->id_PO,
                    'id_barang' => $barang->id_barang,
                    'id_supplier' => $supplier->id_supplier,
                    'hargabarangPO' => $d['hargabarangPO'],
                    'kuantitasbarangPO' => $d['kuantitasbarangPO'],
                ]);
            }

            DB::commit();

            return response()->json($po->load('details.barang', 'details.supplier'), 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // GET /api/purchaseorder/{id}
    public function show($id)
    {
        $po = PurchaseOrder::with(['purchaseRequest', 'details.barang', 'details.supplier'])
                ->findOrFail($id);

        return response()->json($po);
    }

    // PUT /api/purchaseorder/{id} (update status atau tanggal)
    public function update(Request $request, $id)
    {
        $po = PurchaseOrder::findOrFail($id);

        $request->validate([
            'status_PO' => 'nullable|string',
            'tgl_PO' => 'nullable|date',
        ]);

        $po->update($request->only(['status_PO', 'tgl_PO']));

        return response()->json($po);
    }

    // DELETE /api/purchaseorder/{id}
    public function destroy($id)
    {
        $po = PurchaseOrder::findOrFail($id);
        $po->delete();

        return response()->json(null, 204);
    }

    public function sendWhatsApp($id)
{
    $po = PurchaseOrder::with(['details.barang', 'details.supplier'])->findOrFail($id);

    $supplier = $po->details->first()->supplier;

    // Ambil nomor dari database
    $phone = $supplier->notelp_supplier;

    // Bersihkan karakter selain angka
    $phone = preg_replace('/[^0-9]/', '', $phone);

    // Jika nomor mulai dari 0 → ubah ke format internasional
    if (substr($phone, 0, 1) === '0') {
        $phone = '62' . substr($phone, 1);
    }

    // Format pesan
    $itemsText = "";
    $total = 0;

    foreach ($po->details as $item) {
        $subtotal = $item->hargabarangPO * $item->kuantitasbarangPO;
        $total += $subtotal;

        $itemsText .= "- {$item->barang->nama_barang} x{$item->kuantitasbarangPO} (Rp " . number_format($subtotal, 0, ',', '.') . ")\n";
    }

    $message = "Halo {$supplier->nama_supplier},\n\n"
             . "Kami ingin melakukan Purchase Order:\n\n"
             . "Nomor PO: {$po->referensi_PO}\n"
             . "Tanggal: {$po->tgl_PO}\n\n"
             . "Barang:\n"
             . $itemsText . "\n"
             . "Total Pembelian: Rp " . number_format($total, 0, ',', '.') . "\n\n"
             . "Mohon konfirmasinya.\nTerima kasih.";

    $encodedMessage = urlencode($message);

    $whatsappUrl = "https://wa.me/$phone?text=$encodedMessage";

    return response()->json([
        'success' => true,
        'message' => 'Link WhatsApp generated successfully',
        'supplier' => $supplier->nama_supplier,
        'whatsapp_url' => $whatsappUrl
    ]);
}

}
