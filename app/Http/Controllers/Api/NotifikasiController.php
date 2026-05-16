<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notifikasi;
use Illuminate\Http\Request;

class NotifikasiController extends Controller
{
    // GET /api/notifikasi
    public function index(Request $request)
    {
        $user  = $request->user();
        $query = Notifikasi::untuk($user);

        if ($request->filled('tipe')) {
            $query->where('tipe', $request->tipe);
        }

        if ($request->filled('is_read')) {
            $query->where('is_read', filter_var($request->is_read, FILTER_VALIDATE_BOOLEAN));
        }

        $notifs = $query->orderBy('created_at', 'desc')
                        ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data'    => $notifs->items(),
            'meta'    => [
                'total'        => $notifs->total(),
                'per_page'     => $notifs->perPage(),
                'current_page' => $notifs->currentPage(),
                'last_page'    => $notifs->lastPage(),
            ],
            'unread_count' => Notifikasi::untuk($user)->unread()->count(),
        ]);
    }

    // PATCH /api/notifikasi/{id}/read
    public function markAsRead(Request $request, $id)
    {
        $user  = $request->user();
        $notif = Notifikasi::untuk($user)->findOrFail($id);

        $notif->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi ditandai sudah dibaca.'
        ]);
    }

    // PATCH /api/notifikasi/read-all
    public function markAllAsRead(Request $request)
    {
        $user = $request->user();

        Notifikasi::untuk($user)->unread()->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Semua notifikasi ditandai sudah dibaca.'
        ]);
    }

    // DELETE /api/notifikasi/{id}
    public function destroy(Request $request, $id)
    {
        $user  = $request->user();
        $notif = Notifikasi::untuk($user)->findOrFail($id);

        $notif->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi berhasil dihapus.'
        ]);
    }

    // DELETE /api/notifikasi/clear-all
    public function destroyAll(Request $request)
    {
        $user = $request->user();

        Notifikasi::untuk($user)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Semua notifikasi berhasil dihapus.'
        ]);
    }
}