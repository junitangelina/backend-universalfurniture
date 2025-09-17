<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('barang');
    }

    public function down(): void
    {
        // optional: kalau rollback, bisa bikin ulang strukturnya
        Schema::create('barang', function ($table) {
            $table->id('id_barang');
            $table->string('nama_barang', 50);
            $table->string('kategori', 50);
            $table->integer('jumlah_stok');
            $table->integer('stok_min');
            $table->unsignedBigInteger('id_supplier');
            $table->timestamps();
        });
    }
};
