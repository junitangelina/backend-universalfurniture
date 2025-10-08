<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $jumlahBarang = Barang::count();
        $jumlahStok = Barang::sum('jumlah_stok');

        // kalau tabel transaksi sudah ada, bisa hitung barang masuk & keluar
        $barangMasuk = 0;
        $barangKeluar = 0;

        // stok menipis
        $stokMenipis = Barang::whereColumn('jumlah_stok', '<', 'stok_min')->get();

        return response()->json([
            'jumlah_barang' => $jumlahBarang,
            'total_stok' => $jumlahStok,
            'barang_masuk' => $barangMasuk,
            'barang_keluar' => $barangKeluar,
            'stok_menipis' => $stokMenipis,
        ]);
    }
}
