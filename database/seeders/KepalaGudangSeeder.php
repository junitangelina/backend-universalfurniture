<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class KepalaGudangSeeder extends Seeder
{
    public function run()
    {
        DB::table('kepalagudang')->insert([
            [
                'username_gudang' => 'gudang1',
                'password_gudang' => Hash::make('universalkepalagudang'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}