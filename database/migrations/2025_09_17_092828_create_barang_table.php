<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
         Schema::create('barang', function (Blueprint $table) {
        $table->id('id_barang');
        $table->string('nama_barang', 50);
        $table->string('kategori', 50);
        $table->integer('jumlah_stok');
        $table->integer('stok_min')->nullable(); // buat simpan ROP
        $table->unsignedBigInteger('id_supplier');
        $table->string('gambar')->nullable();
        $table->timestamps();

        $table->foreign('id_supplier')->references('id_supplier')->on('supplier');
    });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('barang');
    }
};
