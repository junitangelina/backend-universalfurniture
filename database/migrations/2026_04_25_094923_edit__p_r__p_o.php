<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // ── Tambah referensi_PR ke purchase_request ───────────
        if (!Schema::hasColumn('purchase_request', 'referensi_PR')) {
            Schema::table('purchase_request', function (Blueprint $table) {
                $table->string('referensi_PR', 50)->nullable()->after('id_PR');
            });
        }

        // ── Tambah tgl_terima ke detail_purchase_order ────────
        if (!Schema::hasColumn('detail_purchase_order', 'tgl_terima')) {
            Schema::table('detail_purchase_order', function (Blueprint $table) {
                $table->date('tgl_terima')->nullable()->after('kuantitasterimaPO');
            });
        }
    }

    public function down()
    {
        Schema::table('purchase_request', function (Blueprint $table) {
            $table->dropColumn('referensi_PR');
        });

        Schema::table('detail_purchase_order', function (Blueprint $table) {
            $table->dropColumn('tgl_terima');
        });
    }
};