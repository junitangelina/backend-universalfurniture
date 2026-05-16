<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailTransaksi extends Model
{
    use HasFactory;

    protected $table      = 'detail_transaksi';
    protected $primaryKey = 'id_detail_transaksi';

    protected $fillable = [
        'id_transaksi',
        'id_barang',
        'bukti_pembayaran',
        'kuantitas',
        'harga_satuan',
        'subtotal',
        'jatuh_tempo',
        'status_bayar',  // lunas / pending
        'tgl_bayar',
    ];

    protected $casts = [
        'harga_satuan' => 'decimal:2',
        'subtotal'     => 'decimal:2',
        'jatuh_tempo'  => 'date',
        'tgl_bayar'    => 'date',
    ];

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class, 'id_transaksi', 'id_transaksi');
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang', 'id_barang');
    }

    // Tandai lunas
    public function bayar(): void
    {
        $this->update([
            'status_bayar' => 'lunas',
            'tgl_bayar'    => today(),
        ]);
    }
}