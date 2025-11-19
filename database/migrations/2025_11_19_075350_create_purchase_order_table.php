<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('purchase_order', function (Blueprint $table) {
            $table->id('id_PO');
            $table->string('referensi_PO', 50)->unique();
            $table->date('tgl_PO');
            $table->enum('status_PO', ['pending', 'completed', 'cancel'])->default('pending');
            $table->unsignedBigInteger('id_PR'); // FK ke purchase_request
            $table->timestamps();

            $table->foreign('id_PR')
                  ->references('id_PR')
                  ->on('purchase_request')
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
        Schema::dropIfExists('purchase_order');
    }
};
