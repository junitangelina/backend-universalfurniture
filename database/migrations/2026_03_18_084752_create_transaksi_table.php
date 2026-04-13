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
        Schema::create('transaksi', function (Blueprint $table) {
            $table->id('id_transaksi');
            $table->string('nama_transaksi', 100);
            $table->date('tgl_transaksi');
            $table->enum('jenis_transaksi', ['debit', 'kredit']); // sesuai ERD: jenis_transaksi
            $table->timestamps();
            $table->index(['jenis_transaksi', 'tgl_transaksi']);
            $table->index('nama_transaksi');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transaksi');
    }
};
