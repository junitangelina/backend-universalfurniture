<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\Transaksi;
use App\Models\DetailTransaksi;
use App\Models\ActivityLog;
use App\Models\Admin;
use App\Models\Owner;
use App\Models\KepalaGudang;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    // ──────────────────────────────────────────────────────────
    // GET /api/dashboard
    // Query params:
    //   ?tahun=2025  → filter grafik per tahun (default: tahun ini)
    // ──────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $tahun = $request->tahun ?? now()->year;
        $bulanIni = now()->month;

        // ── 1. CARD STATS ──────────────────────────────────────

        // Total semua stok barang
        $totalStok = Barang::sum('jumlah_stok');

        // Barang masuk bulan ini (transaksi masuk = customer beli → stok berkurang)
        // Mengambil dari detail_transaksi join transaksi
        $barangMasukBulanIni = DetailTransaksi::whereHas('transaksi', function ($q) use ($bulanIni, $tahun) {
            $q->where('jenis_transaksi', 'masuk')
              ->whereMonth('tgl_transaksi', $bulanIni)
              ->whereYear('tgl_transaksi', $tahun);
        })->sum('kuantitas');

        // Barang keluar bulan ini (transaksi keluar = beli dari supplier → stok bertambah)
        $barangKeluarBulanIni = DetailTransaksi::whereHas('transaksi', function ($q) use ($bulanIni, $tahun) {
            $q->where('jenis_transaksi', 'keluar')
              ->whereMonth('tgl_transaksi', $bulanIni)
              ->whereYear('tgl_transaksi', $tahun);
        })->sum('kuantitas');

        // Barang masuk bulan lalu (untuk hitung persentase perubahan)
        $bulanLalu = now()->subMonth()->month;
        $tahunLalu = now()->subMonth()->year;

        $barangMasukBulanLalu = DetailTransaksi::whereHas('transaksi', function ($q) use ($bulanLalu, $tahunLalu) {
            $q->where('jenis_transaksi', 'masuk')
              ->whereMonth('tgl_transaksi', $bulanLalu)
              ->whereYear('tgl_transaksi', $tahunLalu);
        })->sum('kuantitas');

        $barangKeluarBulanLalu = DetailTransaksi::whereHas('transaksi', function ($q) use ($bulanLalu, $tahunLalu) {
            $q->where('jenis_transaksi', 'keluar')
              ->whereMonth('tgl_transaksi', $bulanLalu)
              ->whereYear('tgl_transaksi', $tahunLalu);
        })->sum('kuantitas');

        // Stok menipis
        $stokMenipis = Barang::whereColumn('jumlah_stok', '<=', 'stok_min')
                             ->with('supplier')
                             ->orderBy('jumlah_stok', 'asc')
                             ->get();

        // ── 2. GRAFIK PENDAPATAN PER BULAN ────────────────────
        // Pemasukan = transaksi masuk (customer bayar ke toko)
        // Pengeluaran = transaksi keluar (toko bayar ke supplier)
        $grafik = [];
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $pemasukan = DetailTransaksi::whereHas('transaksi', function ($q) use ($bulan, $tahun) {
                $q->where('jenis_transaksi', 'masuk')
                  ->whereMonth('tgl_transaksi', $bulan)
                  ->whereYear('tgl_transaksi', $tahun);
            })->sum('subtotal');

            $pengeluaran = DetailTransaksi::whereHas('transaksi', function ($q) use ($bulan, $tahun) {
                $q->where('jenis_transaksi', 'keluar')
                  ->whereMonth('tgl_transaksi', $bulan)
                  ->whereYear('tgl_transaksi', $tahun);
            })->sum('subtotal');

            $grafik[] = [
                'bulan'       => str_pad($bulan, 2, '0', STR_PAD_LEFT), // "01", "02", dst
                'pemasukan'   => (float) $pemasukan,
                'pengeluaran' => (float) $pengeluaran,
            ];
        }

        // Total pendapatan penjualan tahun ini
        $totalPendapatan = DetailTransaksi::whereHas('transaksi', function ($q) use ($tahun) {
            $q->where('jenis_transaksi', 'masuk')
              ->whereYear('tgl_transaksi', $tahun);
        })->sum('subtotal');

        // ── 3. RIWAYAT AKTIVITAS (5 terbaru) ─────────────────
        $aktivitasTerbaru = ActivityLog::orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($log) {
                return [
                    'aktivitas' => $log->aktivitas,
                    'modul'     => $log->modul,
                    'tanggal'   => $log->created_at->format('d M, Y'),
                    'waktu'     => $log->created_at->diffForHumans(),
                    'user'      => $this->resolveUser($log->user_type, $log->user_id),
                ];
            });

        // ── 4. NAMA USER YANG LOGIN ───────────────────────────
        $user = $request->user();
        $namaUser = $user->nama_lengkap ?? match (true) {
            $user instanceof Admin        => $user->username_admin,
            $user instanceof Owner        => $user->username_owner,
            $user instanceof KepalaGudang => $user->username_gudang,
            default                       => 'User',
        };

        return response()->json([
            'success' => true,
            'data'    => [
                // Nama untuk sapaan "Hello Jonny,"
                'nama_user'     => $namaUser,

                // 4 card di atas
                'total_stok'    => (int) $totalStok,
                'barang_masuk'  => [
                    'total'      => (int) $barangMasukBulanIni,
                    'persen'     => $this->hitungPersen($barangMasukBulanIni, $barangMasukBulanLalu),
                    'naik'       => $barangMasukBulanIni >= $barangMasukBulanLalu,
                ],
                'barang_keluar' => [
                    'total'      => (int) $barangKeluarBulanIni,
                    'persen'     => $this->hitungPersen($barangKeluarBulanIni, $barangKeluarBulanLalu),
                    'naik'       => $barangKeluarBulanIni >= $barangKeluarBulanLalu,
                ],
                'stok_menipis'  => [
                    'count' => $stokMenipis->count(),
                    'data'  => $stokMenipis,
                ],

                // Grafik pendapatan
                'grafik'            => $grafik,
                'tahun_grafik'      => (int) $tahun,
                'total_pendapatan'  => (float) $totalPendapatan,

                // Riwayat aktivitas (5 terbaru)
                'aktivitas_terbaru' => $aktivitasTerbaru,
            ],
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // Private: hitung persentase perubahan bulan ini vs bulan lalu
    // Contoh: bulan ini 250, bulan lalu 200 → naik 25%
    // ──────────────────────────────────────────────────────────
    private function hitungPersen(float $sekarang, float $lalu): float
    {
        if ($lalu == 0) return $sekarang > 0 ? 100 : 0;
        return round((($sekarang - $lalu) / $lalu) * 100, 1);
    }

    // ──────────────────────────────────────────────────────────
    // Private: resolve nama user dari activity log
    // ──────────────────────────────────────────────────────────
    private function resolveUser(string $userType, int $userId): array
    {
        $user = match ($userType) {
            Admin::class        => Admin::find($userId),
            Owner::class        => Owner::find($userId),
            KepalaGudang::class => KepalaGudang::find($userId),
            default             => null,
        };

        if (!$user) return ['nama' => 'Unknown', 'role' => '-'];

        $role = match ($userType) {
            Admin::class        => 'Admin',
            Owner::class        => 'Owner',
            KepalaGudang::class => 'Kepala Gudang',
            default             => '-',
        };

        $nama = $user->nama_lengkap ?? match ($userType) {
            Admin::class        => $user->username_admin,
            Owner::class        => $user->username_owner,
            KepalaGudang::class => $user->username_gudang,
            default             => 'Unknown',
        };

        return ['nama' => $nama, 'role' => $role];
    }
}