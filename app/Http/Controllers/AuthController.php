<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Admin;
use App\Models\Owner;
use App\Models\KepalaGudang;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'role' => 'required|string', // admin / owner / kepalagudang
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $role = $request->role;
        $user = null;

        // pilih model sesuai role
        if ($role === 'admin') {
            $user = Admin::where('username_admin', $request->username)->first();
            $passwordColumn = 'password_admin';
        } elseif ($role === 'owner') {
            $user = Owner::where('username_owner', $request->username)->first();
            $passwordColumn = 'password_owner';
        } elseif ($role === 'kepalagudang') {
            $user = KepalaGudang::where('username_gudang', $request->username)->first();
            $passwordColumn = 'password_gudang';
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Role tidak valid'
            ], 400);
        }

        if ($user && Hash::check($request->password, $user->$passwordColumn)) {
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login berhasil',
                'role' => $role,
                'token' => $token,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Login gagal, username atau password salah',
        ], 401);
    }
}
