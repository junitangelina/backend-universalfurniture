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
    Schema::table('detail_barang', function (Blueprint $table) {
        $table->dropColumn('bahan');
    });

    Schema::table('detail_barang', function (Blueprint $table) {
        $table->string('bahan')->nullable()->after('ukuran');
    });
}

public function down()
{
    Schema::table('detail_barang', function (Blueprint $table) {
        $table->dropColumn('bahan');
    });

    Schema::table('detail_barang', function (Blueprint $table) {
        $table->integer('bahan')->nullable()->after('ukuran');
    });
}
};
