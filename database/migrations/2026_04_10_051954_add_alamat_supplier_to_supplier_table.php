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
    Schema::table('supplier', function (Blueprint $table) {
        $table->string('alamat_supplier')->nullable()->after('notelp_supplier');
    });
}
    /**
     * Reverse the migrations.
     *
     * @return void
     */
   public function down()
{
    Schema::table('supplier', function (Blueprint $table) {
        $table->dropColumn('alamat_supplier');
    });
}
};
