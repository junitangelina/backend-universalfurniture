<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupplierSeeder extends Seeder
{
    public function run()
    {
        DB::table('supplier')->insert([
            [
                'nama_supplier'   => 'Toko Jaya Furniture',
                'notelp_supplier' => '081234567890',
                'alamat_supplier' => 'Jl. Sudirman No. 10, Pekanbaru',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'nama_supplier'   => 'CV Maju Bersama',
                'notelp_supplier' => '082345678901',
                'alamat_supplier' => 'Jl. Imam Bonjol No. 5, Pekanbaru',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'nama_supplier'   => 'PT Furniture Indo',
                'notelp_supplier' => '083456789012',
                'alamat_supplier' => 'Jl. Diponegoro No. 20, Pekanbaru',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
        ]);
    }
}