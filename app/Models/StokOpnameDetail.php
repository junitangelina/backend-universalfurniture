<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StokOpnameDetail extends Model
{
    use HasFactory;

    protected $table = 'stok_opname_detail';
    protected $primaryKey = 'id_opname_detail';

    protected $fillable = [
        'id_opname',
        'id_barang',
        'stok_sistem',
        'stok_asli',
        'selisih',
    ];

    public function opname()
    {
        return $this->belongsTo(StokOpname::class, 'id_opname');
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }
}
