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
        Schema::create('purchase_request', function (Blueprint $table) {
           $table->id('id_PR');
        $table->date('tgl_PR');
        $table->enum('status_PR', ['pending', 'disetujui', 'ditolak'])->default('pending');
        $table->unsignedBigInteger('id_admin')->nullable();
        $table->unsignedBigInteger('id_owner')->nullable();
        $table->timestamps();

        $table->foreign('id_admin')->references('id_admin')->on('admin')->onDelete('set null');
        $table->foreign('id_owner')->references('id_owner')->on('owner')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchase_request');
    }
};
