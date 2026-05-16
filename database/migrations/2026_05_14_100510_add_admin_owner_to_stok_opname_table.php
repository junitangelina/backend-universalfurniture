<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah kolom id_admin dan id_owner
        Schema::table('stok_opname', function (Blueprint $table) {
            $table->unsignedBigInteger('id_admin')->nullable()->after('id_kepala_gudang');
            $table->unsignedBigInteger('id_owner')->nullable()->after('id_admin');

            $table->foreign('id_admin')
                  ->references('id_admin')
                  ->on('admin')
                  ->onDelete('set null');

            $table->foreign('id_owner')
                  ->references('id_owner')
                  ->on('owner')
                  ->onDelete('set null');
        });

        // Ubah id_kepala_gudang jadi nullable pakai raw SQL
        // (tidak pakai .change() supaya tidak butuh doctrine/dbal)
        DB::statement('ALTER TABLE stok_opname MODIFY id_kepala_gudang BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        Schema::table('stok_opname', function (Blueprint $table) {
            $table->dropForeign(['id_admin']);
            $table->dropForeign(['id_owner']);
            $table->dropColumn(['id_admin', 'id_owner']);
        });

        // Kembalikan id_kepala_gudang jadi not null
        DB::statement('ALTER TABLE stok_opname MODIFY id_kepala_gudang BIGINT UNSIGNED NOT NULL');
    }
};