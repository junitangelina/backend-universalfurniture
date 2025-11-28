<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Cek role berdasarkan instance model
        $userRole = match (true) {
            $user instanceof \App\Models\Admin => 'admin',
            $user instanceof \App\Models\Owner => 'owner',
            $user instanceof \App\Models\KepalaGudang => 'kepalagudang',
            default => null,
        };

        if (!$userRole || !in_array($userRole, $roles)) {
            return response()->json(['message' => 'Forbidden: akses ditolak'], 403);
        }

        return $next($request);
    }
}
