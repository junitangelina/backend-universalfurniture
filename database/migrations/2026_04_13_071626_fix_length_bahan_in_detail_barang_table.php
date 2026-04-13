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
        // FIX bahan di detail_barang
        Schema::table('detail_barang', function (Blueprint $table) {
            $table->dropColumn('bahan');
        });

        Schema::table('detail_barang', function (Blueprint $table) {
            $table->string('bahan', 100)->nullable()->after('ukuran');
        });

        // FIX bukti_pembayaran di detail_transaksi
        Schema::table('detail_transaksi', function (Blueprint $table) {
            $table->dropColumn('bukti_pembayaran');
        });

        Schema::table('detail_transaksi', function (Blueprint $table) {
            $table->string('bukti_pembayaran')->nullable()->after('id_detail_transaksi');
        });
    }

    public function down()
    {
        // rollback bahan
        Schema::table('detail_barang', function (Blueprint $table) {
            $table->dropColumn('bahan');
        });

        Schema::table('detail_barang', function (Blueprint $table) {
            $table->string('bahan', 255)->nullable()->after('ukuran');
        });

        // rollback bukti_pembayaran
        Schema::table('detail_transaksi', function (Blueprint $table) {
            $table->dropColumn('bukti_pembayaran');
        });

        Schema::table('detail_transaksi', function (Blueprint $table) {
            $table->integer('bukti_pembayaran')->nullable()->after('id_detail_transaksi');
        });
    }
};
