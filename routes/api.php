<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BarangController;
use App\Http\Controllers\Api\SupplierController;

Route::post('/login', [AuthController::class, 'login'])->name('login');

// Semua route berikut harus pakai token
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/profile', function (Request $request) {
        return response()->json([
            'message' => 'Halo, ini profile user login',
            'user' => $request->user(),
        ]);
    });

    Route::get('/admin/dashboard', function () {
        return response()->json(['message' => 'Welcome Admin!']);
    });

    Route::get('/owner/dashboard', function () {
        return response()->json(['message' => 'Welcome Owner!']);
    });

    Route::get('/kepalagudang/dashboard', function () {
        return response()->json(['message' => 'Welcome Kepala Gudang!']);
    });

    Route::apiResource('supplier', SupplierController::class);
    Route::apiResource('barang', BarangController::class);

});
