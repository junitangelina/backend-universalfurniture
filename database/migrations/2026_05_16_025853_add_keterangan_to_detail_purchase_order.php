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
   public function up(): void
{
    Schema::table('detail_purchase_order', function (Blueprint $table) {
        $table->text('keterangan')->nullable()->after('tgl_terima');
    });
}

    /**
     * Reverse the migrations.
     *
     * @return void
     */
   public function down(): void
{
    Schema::table('detail_purchase_order', function (Blueprint $table) {
        $table->dropColumn('keterangan');
    });
}
};
