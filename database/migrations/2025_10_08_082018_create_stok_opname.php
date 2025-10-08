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
        Schema::create('stok_opname', function (Blueprint $table) {
            $table->id('id_opname');
            $table->date('tgl_opname');
            $table->unsignedBigInteger('id_kepala_gudang'); // FK ke user atau tabel kepala_gudang
            $table->timestamps();

            // Sesuaikan dengan tabel user/kepala_gudang kamu
            $table->foreign('id_kepala_gudang')
                  ->references('id_kepala_gudang')
                  ->on('kepalagudang')  // atau 'users' kalau pakai tabel user tunggal
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stok_opname');
    }
};
