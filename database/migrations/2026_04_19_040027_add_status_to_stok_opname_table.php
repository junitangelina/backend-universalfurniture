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
         Schema::table('stok_opname', function (Blueprint $table) {
            $table->enum('status', ['draft', 'selesai'])
                  ->default('draft')
                  ->after('id_kepala_gudang'); // letakkan setelah kolom id_kepala_gudang
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
       Schema::table('stok_opname', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
