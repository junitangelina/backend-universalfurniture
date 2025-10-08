<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StokOpname;
use App\Models\StokOpnameDetail;
use App\Models\Barang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StokOpnameController extends Controller
{
    // GET /api/stokopname
    public function index()
    {
        $opnames = StokOpname::with(['details.barang'])->orderBy('tgl_opname', 'desc')->get();
        return response()->json($opnames);
    }

    // POST /api/stokopname
    public function store(Request $request)
    {
        $request->validate([
            'tgl_opname' => 'required|date',
            'id_kepala_gudang' => 'required|integer',
            'details' => 'required|array',
        ]);

        DB::beginTransaction();
        try {
            // Buat record opname
            $opname = StokOpname::create([
                'tgl_opname' => $request->tgl_opname,
                'id_kepala_gudang' => $request->id_kepala_gudang,
            ]);

            // Buat detail opname
            foreach ($request->details as $d) {
                $barang = Barang::findOrFail($d['id_barang']);
                $stokSistem = $barang->jumlah_stok;
                $stokAsli = $d['stok_asli'];
                $selisih = $stokAsli - $stokSistem;

                StokOpnameDetail::create([
                    'id_opname' => $opname->id_opname,
                    'id_barang' => $barang->id_barang,
                    'stok_sistem' => $stokSistem,
                    'stok_asli' => $stokAsli,
                    'selisih' => $selisih,
                ]);
            }

            DB::commit();
            return response()->json($opname->load('details.barang'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // GET /api/stokopname/{id}
    public function show($id)
    {
        $opname = StokOpname::with(['details.barang'])->findOrFail($id);
        return response()->json($opname);
    }

    // DELETE /api/stokopname/{id}
    public function destroy($id)
    {
        $opname = StokOpname::findOrFail($id);
        $opname->delete();
        return response()->json(null, 204);
    }
}
