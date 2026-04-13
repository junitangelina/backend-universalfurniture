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
    Schema::table('detail_purchase_order', function (Blueprint $table) {
        $table->integer('kuantitasterimaPO')
              ->nullable()
              ->after('kuantitasbarangPO');
    });
}

    /**
     * Reverse the migrations.
     *
     * @return void
     */
public function down()
{
    Schema::table('detail_purchase_order', function (Blueprint $table) {
        $table->dropColumn('kuantitasterimaPO');
    });
}
};
