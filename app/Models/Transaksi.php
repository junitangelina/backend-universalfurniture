<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    use HasFactory;

    protected $table      = 'transaksi';
    protected $primaryKey = 'id_transaksi';

    protected $fillable = [
        'nama_transaksi',
        'tgl_transaksi',
        'jenis_transaksi', // masuk (customer beli) / keluar (beli dari supplier)
        'metode_bayar',    // cash / transfer_bank
        'id_PO',           // nullable, hanya untuk transaksi keluar
    ];

    protected $casts = [
        'tgl_transaksi' => 'date',
    ];

    // 1 transaksi punya 1 detail
    public function details()
    {
        return $this->hasMany(DetailTransaksi::class, 'id_transaksi', 'id_transaksi');
    }

    // Transaksi keluar bisa berasal dari PO
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'id_PO', 'id_PO');
    }

    // Hitung total dari subtotal detail
    public function getTotalAttribute(): float
    {
        return $this->detail ? $this->detail->subtotal : 0;
    }

    // Scope search
    public function scopeSearch($query, string $keyword)
    {
        return $query->where('nama_transaksi', 'LIKE', "%{$keyword}%");
    }
}