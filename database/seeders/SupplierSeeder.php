<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
         \App\Models\Supplier::create([
        'nama_supplier' => 'PT Maju Jaya',
        'notelp_supplier' => '08123456789'
    ]);
    }
}
