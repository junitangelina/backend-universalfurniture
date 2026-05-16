<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\BarangController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\StokOpnameController;
use App\Http\Controllers\Api\PurchaseRequestController;
use App\Http\Controllers\Api\PurchaseOrderController;
use App\Http\Controllers\Api\TransaksiController;
use App\Http\Controllers\Api\NotifikasiController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\LaporanController;
use App\Http\Controllers\Api\ActivityLogController;
use Illuminate\Http\Request;

// ─── Public ──────────────────────────────────────────────────
Route::post('/login', [AuthController::class, 'login'])->name('login');

// ─── Protected ───────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout',          [AuthController::class, 'logout']);
    Route::get('/me',               [AuthController::class, 'me']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    // Profile (semua role)
    Route::get('/profile',                  [ProfileController::class, 'show']);
    Route::post('/profile',                 [ProfileController::class, 'update']);
    Route::post('/profile/change-password', [ProfileController::class, 'changePassword']);

    // Activity Log (semua role)
    Route::get('/activity-log', [ActivityLogController::class, 'index']);

    // Dashboard (semua role)
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Notifikasi (semua role)
    Route::prefix('notifikasi')->group(function () {
        Route::get('/',             [NotifikasiController::class, 'index']);
        Route::patch('/read-all',   [NotifikasiController::class, 'markAllAsRead']);
        Route::delete('/clear-all', [NotifikasiController::class, 'destroyAll']);
        Route::patch('/{id}/read',  [NotifikasiController::class, 'markAsRead']);
        Route::delete('/{id}',      [NotifikasiController::class, 'destroy']);
    });

    // Barang - semua role bisa lihat
    Route::get('/barang',              [BarangController::class, 'index']);
    Route::get('/barang-stok-minimum', [BarangController::class, 'stokMinimum']);
    Route::get('/barang-kategori',     [BarangController::class, 'kategoriList']); // FIX: endpoint kategori baru
    Route::get('/barang/{id}',         [BarangController::class, 'show']);
 

    // ─── Admin & Owner ────────────────────────────────────────
    Route::middleware('role:admin,owner')->group(function () {

        // Supplier
        Route::get('/supplier',         [SupplierController::class, 'index']);
        Route::post('/supplier',        [SupplierController::class, 'store']);
        Route::get('/supplier/{id}',    [SupplierController::class, 'show']);
        Route::put('/supplier/{id}',    [SupplierController::class, 'update']);
        Route::delete('/supplier/{id}', [SupplierController::class, 'destroy']);

        // Barang CRUD
        Route::post('/barang',        [BarangController::class, 'store']);
        Route::post('/barang/{id}',   [BarangController::class, 'update']); // POST + _method:PUT untuk form-data
        Route::delete('/barang/{id}', [BarangController::class, 'destroy']);

        // Transaksi
        Route::get('/transaksi',              [TransaksiController::class, 'index']);
        Route::post('/transaksi',             [TransaksiController::class, 'store']);
        Route::get('/transaksi/{id}',         [TransaksiController::class, 'show']);
        Route::put('/transaksi/{id}',         [TransaksiController::class, 'update']);
        Route::post('/transaksi/{id}',        [TransaksiController::class, 'update']); // POST + _method:PUT untuk form-data
        Route::delete('/transaksi/{id}',      [TransaksiController::class, 'destroy']);
        Route::patch('/transaksi/{id}/bayar', [TransaksiController::class, 'bayar']);

        // Purchase Request
        Route::get('/purchaserequest',                [PurchaseRequestController::class, 'index']);
        Route::post('/purchaserequest',               [PurchaseRequestController::class, 'store']);
        Route::get('/purchaserequest/{id}',           [PurchaseRequestController::class, 'show']);
        Route::put('/purchaserequest/{id}',           [PurchaseRequestController::class, 'update']);
        Route::delete('/purchaserequest/{id}',        [PurchaseRequestController::class, 'destroy']);
        Route::post('/purchaserequest/{id}/approve',  [PurchaseRequestController::class, 'approve']);
        Route::post('/purchaserequest/{id}/reject',   [PurchaseRequestController::class, 'reject']);
        Route::post('/purchaserequest/{id}/buat-po',  [PurchaseRequestController::class, 'buatPO']);
        Route::get('/purchaserequest/{id}/export-pdf',[PurchaseRequestController::class, 'exportPdf']);
     
        

        // Purchase Order
        Route::get('/purchaseorder',                    [PurchaseOrderController::class, 'index']);
        Route::get('/purchaseorder/{id}',               [PurchaseOrderController::class, 'show']);
        Route::put('/purchaseorder/{id}',               [PurchaseOrderController::class, 'update']);
        Route::delete('/purchaseorder/{id}',            [PurchaseOrderController::class, 'destroy']);
        Route::get('/purchaseorder/{id}/whatsapp',      [PurchaseOrderController::class, 'whatsapp']);
        Route::get('/purchaseorder/{id}/for-transaksi', [TransaksiController::class, 'previewDariPO']); // preview PO untuk form transaksi keluar
        Route::get('/purchaseorder/{id}/export-pdf',    [PurchaseOrderController::class, 'exportPdf']); 
        
        // Laporan Transaksi
        Route::get('/laporan', [LaporanController::class, 'index']);
    });

    // ─── Kepala Gudang ────────────────────────────────────────
    Route::middleware('role:admin,owner,kepalagudang')->group(function () {
        Route::get('/stokopname',               [StokOpnameController::class, 'index']);
        Route::post('/stokopname',              [StokOpnameController::class, 'store']);
        Route::get('/stokopname/{id}',          [StokOpnameController::class, 'show']);
        Route::put('/stokopname/{id}',          [StokOpnameController::class, 'update']);
        Route::delete('/stokopname/{id}',       [StokOpnameController::class, 'destroy']);
        Route::post('/stokopname/{id}/selesai', [StokOpnameController::class, 'selesai']);
    });
});