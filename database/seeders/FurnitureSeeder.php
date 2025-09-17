<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Barang;
use App\Models\DetailBarang;

class FurnitureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $barang = Barang::create([
            'nama_barang' => 'Kursi Kantor',
            'kategori' => 'Kursi',
            'jumlah_stok' => 50,
            'stok_min' => 5,
            'id_supplier' => 1,
            'gambar' => 'images/kursi_kantor.jpeg'
        ]);

        DetailBarang::create([
            'id_barang' => $barang->id,
            'merek' => 'ErgoChair',
            'tipe' => 'Kursi Putar',
            'ukuran' => 'Standard'
        ]);
    }
}
