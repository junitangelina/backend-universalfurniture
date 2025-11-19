<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('detail_purchase_order', function (Blueprint $table) {
            $table->id('id_detail_PO');
            $table->integer('hargabarangPO');
            $table->integer('kuantitasbarangPO');

            $table->unsignedBigInteger('id_PO');
            $table->unsignedBigInteger('id_barang');
            $table->unsignedBigInteger('id_supplier');

            $table->timestamps();

            $table->foreign('id_PO')
                  ->references('id_PO')
                  ->on('purchase_order')
                  ->onDelete('cascade');

            $table->foreign('id_barang')
                  ->references('id_barang')
                  ->on('barang')
                  ->onDelete('restrict');

            $table->foreign('id_supplier')
                  ->references('id_supplier')
                  ->on('supplier')
                  ->onDelete('restrict');
        });
    }
 /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('detail_purchase_order');
    }
};
