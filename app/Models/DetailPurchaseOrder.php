<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailPurchaseOrder extends Model
{
    use HasFactory;

    protected $table = 'detail_purchase_order';
    protected $primaryKey = 'id_detail_PO';

     protected $fillable = [
        'id_PO',
        'id_barang',
        'id_supplier',
        'hargabarangPO',      // otomatis copy dari hargabarangPR
        'kuantitasbarangPO',  // otomatis copy dari kuantitasbarangPR
        'kuantitasterimaPO',  // diisi saat barang datang (awalnya null)
        'tgl_terima',         // diisi saat barang datang (awalnya null)
    ];
 
    protected $casts = [
        'hargabarangPO' => 'decimal:2',
        'tgl_terima'    => 'date',
    ];

    // Relasi ke Purchase Order
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'id_PO', 'id_PO');
    }

    // Relasi ke barang
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang', 'id_barang');
    }

    // Relasi ke supplier
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'id_supplier', 'id_supplier');
    }
}
