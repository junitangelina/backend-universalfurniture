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
       Schema::create('stok_opname_detail', function (Blueprint $table) {
            $table->id('id_opname_detail');
            $table->unsignedBigInteger('id_opname'); // FK ke stok_opname
            $table->unsignedBigInteger('id_barang'); // FK ke barang

            $table->integer('stok_sistem'); // jumlah stok versi sistem
            $table->integer('stok_asli');   // jumlah stok hasil hitungan
            $table->integer('selisih')->nullable(); // stok_asli - stok_sistem
            $table->timestamps();
            
            $table->foreign('id_opname')
                  ->references('id_opname')
                  ->on('stok_opname')
                  ->onDelete('cascade');

            $table->foreign('id_barang')
                  ->references('id_barang')
                  ->on('barang')
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
        Schema::dropIfExists('stok_opname_detail');
    }
};
