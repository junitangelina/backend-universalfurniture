<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Owner;
use App\Models\KepalaGudang;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    // ──────────────────────────────────────────────────────────
    // GET /api/profile
    // Ambil data profil user yang sedang login
    // ──────────────────────────────────────────────────────────
    public function show(Request $request)
    {
        $user = $request->user();
        $role = $this->getRole($user);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'           => $user->getKey(),
                'username'     => $this->getUsername($user, $role),
                'nama_lengkap' => $user->nama_lengkap,
                'email'        => $user->email,
                'no_telepon'   => $user->no_telepon,
                'foto'         => $user->foto ? asset('storage/' . $user->foto) : null,
                'alamat_toko'  => $user->alamat_toko,
                'role'         => $role,
            ],
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // PUT /api/profile
    // Update data profil
    // Content-Type: multipart/form-data (karena ada upload foto)
    //
    // Body:
    //   nama_lengkap → string
    //   email        → string
    //   no_telepon   → string
    //   alamat_toko  → string
    //   foto         → file (jpg/png, max 2MB)
    // ──────────────────────────────────────────────────────────
    public function update(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'nama_lengkap' => 'sometimes|string|max:100',
            'email'        => 'sometimes|string|email|max:100',
            'no_telepon'   => 'sometimes|string|max:20',
            'alamat_toko'  => 'nullable|string',
            'foto'         => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $data = $request->only(['nama_lengkap', 'email', 'no_telepon', 'alamat_toko']);

        // Upload foto baru kalau ada
        if ($request->hasFile('foto')) {
            // Hapus foto lama
            if ($user->foto) {
                Storage::disk('public')->delete($user->foto);
            }
            $data['foto'] = $request->file('foto')->store('foto_profil', 'public');
        }

        $user->update($data);

        // Catat aktivitas
        ActivityLog::catat($user, 'Memperbarui profil akun', 'profil');

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diupdate.',
            'data'    => [
                'id'           => $user->getKey(),
                'username'     => $this->getUsername($user, $this->getRole($user)),
                'nama_lengkap' => $user->nama_lengkap,
                'email'        => $user->email,
                'no_telepon'   => $user->no_telepon,
                'foto'         => $user->foto ? asset('storage/' . $user->foto) : null,
                'alamat_toko'  => $user->alamat_toko,
                'role'         => $this->getRole($user),
            ],
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // POST /api/profile/change-password
    // Ganti password
    //
    // Body:
    //   current_password → password lama
    //   password         → password baru
    //   password_confirmation → konfirmasi password baru
    // ──────────────────────────────────────────────────────────
    public function changePassword(Request $request)
    {
        $user = $request->user();
        $role = $this->getRole($user);

        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:6|confirmed',
        ]);

        $passwordColumn = $this->getPasswordColumn($role);

        // Cek password lama
        if (!Hash::check($request->current_password, $user->$passwordColumn)) {
            return response()->json([
                'success' => false,
                'message' => 'Password lama tidak sesuai.',
            ], 422);
        }

        $user->update([
            $passwordColumn => Hash::make($request->password),
        ]);

        // Catat aktivitas
        ActivityLog::catat($user, 'Mengganti password akun', 'profil');

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah.',
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────
    private function getRole($user): string
    {
        return match (true) {
            $user instanceof Admin        => 'admin',
            $user instanceof Owner        => 'owner',
            $user instanceof KepalaGudang => 'kepalagudang',
            default                       => 'unknown',
        };
    }

    private function getUsername($user, string $role): string
    {
        return match ($role) {
            'admin'        => $user->username_admin,
            'owner'        => $user->username_owner,
            'kepalagudang' => $user->username_gudang,
            default        => '',
        };
    }

    private function getPasswordColumn(string $role): string
    {
        return match ($role) {
            'admin'        => 'password_admin',
            'owner'        => 'password_owner',
            'kepalagudang' => 'password_gudang',
            default        => 'password',
        };
    }
}