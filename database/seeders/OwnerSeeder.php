<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class OwnerSeeder extends Seeder
{
    public function run()
    {

        DB::table('owner')->insert([
            [
                'username_owner' => 'owner1',
                'password_owner' => Hash::make('universalowner'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}