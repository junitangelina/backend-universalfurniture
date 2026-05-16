<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Admin;
use App\Models\Owner;
use App\Models\KepalaGudang;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    // ──────────────────────────────────────────────────────────
    // GET /api/activity-log
    // Semua role bisa lihat
    //
    // Query params:
    //   ?search=barang      → cari aktivitas atau nama user
    //   ?modul=barang       → filter by modul
    //   ?tgl_dari=2025-01-01
    //   ?tgl_sampai=2025-12-31
    //   ?per_page=10
    // ──────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = ActivityLog::orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $keyword = $request->search;
            $query->where('aktivitas', 'LIKE', "%{$keyword}%");
        }

        if ($request->filled('modul')) {
            $query->where('modul', $request->modul);
        }

        if ($request->filled('tgl_dari')) {
            $query->whereDate('created_at', '>=', $request->tgl_dari);
        }

        if ($request->filled('tgl_sampai')) {
            $query->whereDate('created_at', '<=', $request->tgl_sampai);
        }

        $logs = $query->paginate($request->per_page ?? 10);

        // Resolve nama & role user untuk setiap log
        $items = collect($logs->items())->map(function ($log) {
            return [
                'id'         => $log->id,
                'aktivitas'  => $log->aktivitas,
                'modul'      => $log->modul,
                'tanggal'    => $log->created_at->format('d/m/Y'),
                'waktu'      => $log->created_at->diffForHumans(), // "2 menit yang lalu"
                'user'       => $this->resolveUser($log->user_type, $log->user_id),
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $items,
            'meta'    => [
                'total'        => $logs->total(),
                'per_page'     => $logs->perPage(),
                'current_page' => $logs->currentPage(),
                'last_page'    => $logs->lastPage(),
                'from'         => $logs->firstItem(),
                'to'           => $logs->lastItem(),
            ],
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // Private: resolve data user dari polymorphic
    // ──────────────────────────────────────────────────────────
    private function resolveUser(string $userType, int $userId): array
    {
        $user = match ($userType) {
            Admin::class        => Admin::find($userId),
            Owner::class        => Owner::find($userId),
            KepalaGudang::class => KepalaGudang::find($userId),
            default             => null,
        };

        if (!$user) {
            return ['nama' => 'Unknown', 'email' => '-', 'role' => '-'];
        }

        $role = match ($userType) {
            Admin::class        => 'admin',
            Owner::class        => 'owner',
            KepalaGudang::class => 'kepalagudang',
            default             => '-',
        };

        $email = match ($role) {
            'admin'        => $user->email ?? $user->username_admin,
            'owner'        => $user->email ?? $user->username_owner,
            'kepalagudang' => $user->email ?? $user->username_gudang,
            default        => '-',
        };

        return [
            'nama'  => $user->nama_lengkap ?? $email,
            'email' => $email,
            'role'  => $role,
        ];
    }
}