<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notifikasi extends Model
{
    protected $table    = 'notifikasi';
    protected $fillable = [
        'penerima_type',
        'penerima_id',
        'notifiable_type',
        'notifiable_id',
        'judul',
        'pesan',
        'tipe',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    // Polymorphic: notif dikirim ke siapa (Admin/Owner/KepalaGudang)
    public function penerima()
    {
        return $this->morphTo('penerima');
    }

    // Polymorphic: notif berkaitan dengan apa (Barang/PR/PO)
    public function notifiable()
    {
        return $this->morphTo();
    }

    public function markAsRead(): void
    {
        $this->update(['is_read' => true, 'read_at' => now()]);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    // Scope: ambil notif untuk user tertentu (by model instance)
    public function scopeUntuk($query, $user)
    {
        return $query->where('penerima_type', get_class($user))
                     ->where('penerima_id', $user->getKey());
    }
}