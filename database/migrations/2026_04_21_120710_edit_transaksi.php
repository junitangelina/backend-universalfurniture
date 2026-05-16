<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('transaksi', function (Blueprint $table) {
            // Harus dipisah 2 Schema::table karena dropColumn dan addColumn
            // tidak bisa dalam 1 closure yang sama


            $table->enum('metode_bayar', ['cash', 'transfer_bank'])
                  ->after('jenis_transaksi');

            // Nullable karena hanya transaksi keluar (dari PO) yang butuh ini
            $table->unsignedBigInteger('id_PO')
                  ->nullable()
                  ->after('metode_bayar');

            $table->foreign('id_PO')
                  ->references('id_PO')
                  ->on('purchase_order')
                  ->onDelete('set null');
        });

        // ── Update tabel detail_transaksi ──────────────────────
        Schema::table('detail_transaksi', function (Blueprint $table) {
            // Dicatat saat transaksi terjadi supaya history harga
            // tidak berubah walau harga barang diupdate nanti
            $table->decimal('harga_satuan', 15, 2)
                  ->after('kuantitas');

            // Nullable — diisi kalau customer hutang, kosong kalau bayar langsung
            $table->date('jatuh_tempo')
                  ->nullable()
                  ->after('subtotal');

            // Default lunas — berubah jadi belum_bayar kalau ada jatuh_tempo
            $table->enum('status_bayar', ['lunas', 'pending'])
                  ->default('pending')
                  ->after('jatuh_tempo');

            // Diisi otomatis saat customer bayar
            $table->date('tgl_bayar')
                  ->nullable()
                  ->after('status_bayar');
        });
    }

    public function down()
    {
        // Rollback detail_transaksi
        Schema::table('detail_transaksi', function (Blueprint $table) {
            // fix: pakai array bukan multiple argument
            $table->dropColumn(['harga_satuan', 'jatuh_tempo', 'status_bayar', 'tgl_bayar']);
        });

        // Rollback transaksi — drop foreign key & kolom baru dulu
        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropForeign(['id_PO']);
            $table->dropColumn(['id_PO', 'metode_bayar']);
        });

    }
};