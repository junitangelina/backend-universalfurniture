<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;   // 
use Illuminate\Support\Facades\Hash; // 

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('admin')->insert([
            'username_admin' => 'admin1',
            'password_admin' => Hash::make('123456'),
        ]);

        // OWNER
        DB::table('owner')->insert([
            'username_owner' => 'owner1',
            'password_owner' => Hash::make('123457'),
        ]);

        // KEPALA GUDANG
        DB::table('kepalagudang')->insert([
            'username_gudang' => 'gudang1',
            'password_gudang' => Hash::make('123458'),
        ]);
    }
}
