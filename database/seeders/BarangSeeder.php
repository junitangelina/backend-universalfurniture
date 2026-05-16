<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BarangSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
DB::table('detail_barang')->truncate();
DB::table('barang')->truncate();
DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        // Data barang — variasi stok sengaja dibuat ada yang normal,
        // ada yang menipis, dan ada yang habis untuk test notif reorder point
        $barangs = [
            // Stok normal
            [
                'nama_barang' => 'Comforta Kursi Merah',
                'kategori'    => 'Kursi',
                'harga'       => 500000,
                'jumlah_stok' => 20,
                'stok_min'    => 5,
                'id_supplier' => 1,
                'gambar'      => null,
            ],
            [
                'nama_barang' => 'Comforta Meja Makan Putih',
                'kategori'    => 'Meja',
                'harga'       => 1500000,
                'jumlah_stok' => 15,
                'stok_min'    => 3,
                'id_supplier' => 2,
                'gambar'      => null,
            ],
            [
                'nama_barang' => 'Comforta Lemari Putih',
                'kategori'    => 'Lemari',
                'harga'       => 2000000,
                'jumlah_stok' => 10,
                'stok_min'    => 2,
                'id_supplier' => 3,
                'gambar'      => null,
            ],
            [
                'nama_barang' => 'Comforta Sofa Coklat',
                'kategori'    => 'Sofa',
                'harga'       => 3500000,
                'jumlah_stok' => 8,
                'stok_min'    => 2,
                'id_supplier' => 1,
                'gambar'      => null,
            ],
            // Stok menipis (jumlah_stok <= stok_min) → akan trigger notif
            [
                'nama_barang' => 'Comforta Kursi Kantor Abu',
                'kategori'    => 'Kursi',
                'harga'       => 750000,
                'jumlah_stok' => 3,  // ← menipis, stok_min = 5
                'stok_min'    => 5,
                'id_supplier' => 2,
                'gambar'      => null,
            ],
            [
                'nama_barang' => 'Conforta Meja Belajar Coklat',
                'kategori'    => 'Meja',
                'harga'       => 800000,
                'jumlah_stok' => 2,  // ← menipis, stok_min = 3
                'stok_min'    => 3,
                'id_supplier' => 3,
                'gambar'      => null,
            ],
            // Stok habis
            [
                'nama_barang' => 'Comforta Rak Buku Hitam',
                'kategori'    => 'Rak',
                'harga'       => 600000,
                'jumlah_stok' => 0,  // ← habis
                'stok_min'    => 2,
                'id_supplier' => 1,
                'gambar'      => null,
            ],
        ];

        foreach ($barangs as $barang) {
            $id = DB::table('barang')->insertGetId([
                ...$barang,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Insert detail barang untuk setiap barang
            DB::table('detail_barang')->insert([
                'id_barang' => $id,
                'merek'     => 'Comforta',
                'tipe'      => strtoupper(substr($barang['nama_barang'], 0, 3)) . '-100',
                'ukuran'    => '60x60x80 cm',
                'bahan'     => match($barang['kategori']) {
                    'Kursi'  => 'Busa & Kain',
                    'Meja'   => 'Kayu Jati',
                    'Lemari' => 'Kayu Mahoni',
                    'Sofa'   => 'Kulit Sintetis',
                    'Rak'    => 'Kayu Pinus',
                    default  => 'Kayu',
                },
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}