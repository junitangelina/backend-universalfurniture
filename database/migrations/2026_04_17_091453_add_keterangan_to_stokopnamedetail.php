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
         Schema::table('stok_opname_detail', function (Blueprint $table) {
        $table->text('keterangan')->nullable()->after('selisih');
    });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stok_opname_detail', function (Blueprint $table) {
        $table->dropColumn('keterangan');
    });
    }
};
