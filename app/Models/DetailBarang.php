<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailBarang extends Model
{
    use HasFactory;

    protected $table = 'detail_barang';
        protected $primaryKey = 'id__detail_barang'; // tambahkan ini
    protected $fillable = [
        'id_barang',
        'merek',
        'tipe',
        'ukuran'
    ];

    // Relasi: detail milik 1 barang
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }
}