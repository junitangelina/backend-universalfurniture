<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table   = 'activity_log';
    protected $fillable = [
        'user_type',
        'user_id',
        'aktivitas',
        'modul',
    ];

    // Polymorphic: siapa yang lakukan aksi
    public function user()
    {
        return $this->morphTo('user');
    }

    // Helper: catat aktivitas dari controller manapun
    public static function catat($user, string $aktivitas, string $modul): void
    {
        static::create([
            'user_type' => get_class($user),
            'user_id'   => $user->getKey(),
            'aktivitas' => $aktivitas,
            'modul'     => $modul,
        ]);
    }
}