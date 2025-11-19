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
        Schema::create('detail_purchase_request', function (Blueprint $table) {
            $table->id('id_detail_PR');
        $table->decimal('hargabarangPR', 12, 2);
        $table->integer('kuantitasbarangPR');
        $table->unsignedBigInteger('id_PR');
        $table->unsignedBigInteger('id_barang');
        $table->unsignedBigInteger('id_supplier');
        $table->timestamps();

        $table->foreign('id_PR')->references('id_PR')->on('purchase_request')->onDelete('cascade');
        $table->foreign('id_barang')->references('id_barang')->on('barang')->onDelete('restrict');
        $table->foreign('id_supplier')->references('id_supplier')->on('supplier')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('detail_purchase_request');
    }
};
