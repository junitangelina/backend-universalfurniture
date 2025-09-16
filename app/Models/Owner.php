<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Owner extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'owner';
    protected $primaryKey = 'id_owner';
    public $timestamps = true;

    protected $fillable = [
        'username_owner',
        'password_owner',
    ];

    protected $hidden = [
        'password_owner',
    ];
}
