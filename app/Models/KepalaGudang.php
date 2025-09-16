<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KepalaGudang extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'kepalagudang';
    protected $primaryKey = 'id_kepala_gudang';
    public $timestamps = true;

    protected $fillable = [
        'username_gudang',
        'password_gudang',
    ];

    protected $hidden = [
        'password_gudang',
    ];
}
