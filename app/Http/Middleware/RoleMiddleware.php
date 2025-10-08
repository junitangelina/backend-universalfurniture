<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, $role)
    {
        $user = $request->user();

        // Cek role berdasarkan instance model
        switch ($role) {
            case 'admin':
                if (!($user instanceof \App\Models\Admin)) {
                    return response()->json(['message' => 'Unauthorized: hanya admin yang bisa mengakses'], 403);
                }
                break;

            case 'owner':
                if (!($user instanceof \App\Models\Owner)) {
                    return response()->json(['message' => 'Unauthorized: hanya owner yang bisa mengakses'], 403);
                }
                break;

            case 'kepalagudang':
                if (!($user instanceof \App\Models\KepalaGudang)) {
                    return response()->json(['message' => 'Unauthorized: hanya kepala gudang yang bisa mengakses'], 403);
                }
                break;

            default:
                return response()->json(['message' => 'Role tidak dikenali'], 403);
        }

        return $next($request);
    }
}
