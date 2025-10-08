<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StokOpname extends Model
{
    use HasFactory;

    protected $table = 'stok_opname';
    protected $primaryKey = 'id_opname';

    protected $fillable = [
        'tgl_opname',
        'id_kepala_gudang',
    ];

    public function details()
    {
        return $this->hasMany(StokOpnameDetail::class, 'id_opname', 'id_opname');
    }

    public function kepalaGudang()
    {
        // sesuaikan nama model dengan tabel user/kepala_gudang kamu
        return $this->belongsTo(KepalaGudang::class, 'id_kepala_gudang');
    }
}
