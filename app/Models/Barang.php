<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    use HasFactory;

    protected $table      = 'barang';
    protected $primaryKey = 'id_barang';

    protected $fillable = [
        'nama_barang',
        'kategori',
        'harga',         
        'jumlah_stok',
        'stok_min',
        'id_supplier',
        'gambar',
    ];

    // Relasi: Barang punya banyak detail (merek, tipe, ukuran, bahan)
    public function detailBarang()
    {
        return $this->hasMany(DetailBarang::class, 'id_barang', 'id_barang');
    }

    // Relasi: Barang dimiliki 1 supplier
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'id_supplier', 'id_supplier');
    }
}