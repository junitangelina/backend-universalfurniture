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
    // Tambah nilai 'sudah_diproses' ke enum status_PO
    DB::statement("ALTER TABLE purchase_order MODIFY status_PO ENUM('diajukan','disetujui','ditolak','selesai','sudah_diproses') NOT NULL DEFAULT 'diajukan'");
}

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
{
    // Kembalikan enum tanpa 'sudah_diproses'
    DB::statement("ALTER TABLE purchase_order MODIFY status_PO ENUM('diajukan','disetujui','ditolak','selesai') NOT NULL DEFAULT 'diajukan'");
}
};
