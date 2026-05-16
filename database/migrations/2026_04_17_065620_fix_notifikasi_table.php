<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Drop tabel lama yang salah kalau masih ada
        Schema::dropIfExists('notifikasi');

        Schema::create('notifikasi', function (Blueprint $table) {
            $table->id();

            // Polymorphic: siapa penerimanya (Admin / Owner)
            // penerima_type → nama class model: "App\Models\Admin"
            // penerima_id   → id_admin atau id_owner
            $table->string('penerima_type');
            $table->unsignedBigInteger('penerima_id');

            // Polymorphic: notif ini berkaitan dengan apa
            // notifiable_type → "App\Models\Barang" / "App\Models\PurchaseRequest" / dll
            // notifiable_id   → id_barang / id_PR / dll
            $table->string('notifiable_type');
            $table->unsignedBigInteger('notifiable_id');

            $table->string('judul');
            $table->text('pesan');
            $table->enum('tipe', [
                'stok_minimum',     // notif reorder point
                'purchase_request', // notif PR baru / disetujui / ditolak
                'purchase_order',   // notif PO selesai
                'info'
            ])->default('info');

            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable(); // diisi saat notif dibaca

            $table->timestamps();

            // Index supaya query notif per user lebih cepat
            $table->index(['penerima_type', 'penerima_id']);
            $table->index(['notifiable_type', 'notifiable_id']);
            $table->index(['is_read', 'tipe']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('notifikasi');
    }
};