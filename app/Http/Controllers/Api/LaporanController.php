<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DetailTransaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller
{
    // ──────────────────────────────────────────────────────────
    // GET /api/laporan
    // Dipanggil saat klik "Terapkan"
    // Return: ringkasan 3 card + tabel pemasukan & pengeluaran per hari
    //
    // Query params (wajib):
    //   ?tgl_dari=2026-05-05
    //   ?tgl_sampai=2026-05-10
    // ──────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $request->validate([
            'tgl_dari'   => 'required|date',
            'tgl_sampai' => 'required|date|after_or_equal:tgl_dari',
        ]);

        $tglDari   = $request->tgl_dari;
        $tglSampai = $request->tgl_sampai;

        // ── 1. RINGKASAN 3 CARD ───────────────────────────────

        $totalPemasukan = DetailTransaksi::whereHas('transaksi', function ($q) use ($tglDari, $tglSampai) {
            $q->where('jenis_transaksi', 'masuk')
              ->whereBetween('tgl_transaksi', [$tglDari, $tglSampai]);
        })->sum('subtotal');

        $totalPengeluaran = DetailTransaksi::whereHas('transaksi', function ($q) use ($tglDari, $tglSampai) {
            $q->where('jenis_transaksi', 'keluar')
              ->whereBetween('tgl_transaksi', [$tglDari, $tglSampai]);
        })->sum('subtotal');

        $totalKeuntungan = $totalPemasukan - $totalPengeluaran;

        // ── 2. PEMASUKAN PER HARI ─────────────────────────────
        // Group by tanggal, SUM kuantitas & subtotal
        $pemasukanHarian = DetailTransaksi::select(
                'transaksi.tgl_transaksi',
                DB::raw('SUM(detail_transaksi.kuantitas) as total_kuantitas'),
                DB::raw('SUM(detail_transaksi.subtotal) as total_nominal')
            )
            ->join('transaksi', 'detail_transaksi.id_transaksi', '=', 'transaksi.id_transaksi')
            ->where('transaksi.jenis_transaksi', 'masuk')
            ->whereBetween('transaksi.tgl_transaksi', [$tglDari, $tglSampai])
            ->groupBy('transaksi.tgl_transaksi')
            ->orderBy('transaksi.tgl_transaksi', 'desc')
            ->get()
            ->map(fn($row) => [
                'tanggal'   => \Carbon\Carbon::parse($row->tgl_transaksi)->format('d/m/Y'),
                'kuantitas' => (int) $row->total_kuantitas,
                'total'     => (float) $row->total_nominal,
            ]);

        // ── 3. PENGELUARAN PER HARI ───────────────────────────
        $pengeluaranHarian = DetailTransaksi::select(
                'transaksi.tgl_transaksi',
                DB::raw('SUM(detail_transaksi.kuantitas) as total_kuantitas'),
                DB::raw('SUM(detail_transaksi.subtotal) as total_nominal')
            )
            ->join('transaksi', 'detail_transaksi.id_transaksi', '=', 'transaksi.id_transaksi')
            ->where('transaksi.jenis_transaksi', 'keluar')
            ->whereBetween('transaksi.tgl_transaksi', [$tglDari, $tglSampai])
            ->groupBy('transaksi.tgl_transaksi')
            ->orderBy('transaksi.tgl_transaksi', 'desc')
            ->get()
            ->map(fn($row) => [
                'tanggal'   => \Carbon\Carbon::parse($row->tgl_transaksi)->format('d/m/Y'),
                'kuantitas' => (int) $row->total_kuantitas,
                'total'     => (float) $row->total_nominal,
            ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'periode' => [
                    'dari'   => $tglDari,
                    'sampai' => $tglSampai,
                ],
                'total_keuntungan'   => (float) $totalKeuntungan,
                'total_pemasukan'    => (float) $totalPemasukan,
                'total_pengeluaran'  => (float) $totalPengeluaran,
                'pemasukan_harian'   => $pemasukanHarian,
                'pengeluaran_harian' => $pengeluaranHarian,
            ],
        ]);
    }
}