<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    use HasFactory;

    protected $table = 'barang';
    protected $primaryKey = 'id_barang'; // tambahkan ini
    protected $fillable = [
        'nama_barang',
        'kategori',
        'jumlah_stok',
        'stok_min',
        'id_supplier',
        'gambar'
    ];

    // Relasi: Barang punya 1 detail
    public function detailBarang()
    {
        return $this->hasMany(DetailBarang::class, 'id_barang', 'id_barang');
    }

    // Relasi: Barang dimiliki 1 supplier
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'id_supplier');
    }
}
