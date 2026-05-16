<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Tambah kolom profil ke tabel admin
        Schema::table('admin', function (Blueprint $table) {
            $table->string('nama_lengkap', 100)->nullable()->after('username_admin');
            $table->string('email', 100)->nullable()->after('nama_lengkap');
            $table->string('no_telepon', 20)->nullable()->after('email');
            $table->string('foto')->nullable()->after('no_telepon');
            $table->text('alamat_toko')->nullable()->after('foto');
        });

        // Tambah kolom profil ke tabel owner
        Schema::table('owner', function (Blueprint $table) {
            $table->string('nama_lengkap', 100)->nullable()->after('username_owner');
            $table->string('email', 100)->nullable()->after('nama_lengkap');
            $table->string('no_telepon', 20)->nullable()->after('email');
            $table->string('foto')->nullable()->after('no_telepon');
            $table->text('alamat_toko')->nullable()->after('foto');
        });

        // Tambah kolom profil ke tabel kepalagudang
        Schema::table('kepalagudang', function (Blueprint $table) {
            $table->string('nama_lengkap', 100)->nullable()->after('username_gudang');
            $table->string('email', 100)->nullable()->after('nama_lengkap');
            $table->string('no_telepon', 20)->nullable()->after('email');
            $table->string('foto')->nullable()->after('no_telepon');
            $table->text('alamat_toko')->nullable()->after('foto');
        });
    }

    public function down()
    {
        Schema::table('admin', function (Blueprint $table) {
            $table->dropColumn(['nama_lengkap', 'email', 'no_telepon', 'foto', 'alamat_toko']);
        });

        Schema::table('owner', function (Blueprint $table) {
            $table->dropColumn(['nama_lengkap', 'email', 'no_telepon', 'foto', 'alamat_toko']);
        });

        Schema::table('kepalagudang', function (Blueprint $table) {
            $table->dropColumn(['nama_lengkap', 'email', 'no_telepon', 'foto', 'alamat_toko']);
        });
    }
};