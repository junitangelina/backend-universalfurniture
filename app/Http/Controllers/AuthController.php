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

     public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['success' => true, 'message' => 'Logout berhasil.']);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        $role = match (true) {
            $user instanceof Admin        => 'admin',
            $user instanceof Owner        => 'owner',
            $user instanceof KepalaGudang => 'kepalagudang',
            default                       => 'unknown',
        };

        $usernameField = match ($role) {
            'admin'        => 'username_admin',
            'owner'        => 'username_owner',
            'kepalagudang' => 'username_gudang',
            default        => null,
        };

        return response()->json([
            'success' => true,
            'data'    => [
                'id'       => $user->getKey(),
                'username' => $usernameField ? $user->$usernameField : null,
                'role'     => $role,
            ],
        ]);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:6|confirmed',
        ]);

        $user = $request->user();
        $role = match (true) {
            $user instanceof Admin        => 'admin',
            $user instanceof Owner        => 'owner',
            $user instanceof KepalaGudang => 'kepalagudang',
            default                       => null,
        };

        $passwordColumn = match ($role) {
            'admin'        => 'password_admin',
            'owner'        => 'password_owner',
            'kepalagudang' => 'password_gudang',
            default        => null,
        };

        if (!$passwordColumn || !Hash::check($request->current_password, $user->$passwordColumn)) {
            return response()->json(['success' => false, 'message' => 'Password lama tidak sesuai.'], 422);
        }

        $user->update([$passwordColumn => Hash::make($request->password)]);

        return response()->json(['success' => true, 'message' => 'Password berhasil diubah.']);
    }

}


