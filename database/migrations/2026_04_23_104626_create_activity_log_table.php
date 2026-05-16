<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();

            // Polymorphic: siapa yang melakukan aksi (Admin/Owner/KepalaGudang)
            $table->string('user_type');
            $table->unsignedBigInteger('user_id');

            $table->string('aktivitas');  // deskripsi aksi, misal "Menambahkan barang 'Kursi Santai'"
            $table->string('modul');      // modul mana: barang, transaksi, purchase_request, dll
            $table->timestamps();

            $table->index(['user_type', 'user_id']);
            $table->index('modul');
        });
    }

    public function down()
    {
        Schema::dropIfExists('activity_log');
    }
};