<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailPurchaseRequest extends Model
{
    use HasFactory;

    protected $table = 'detail_purchase_request';
    protected $primaryKey = 'id_detail_PR';
   protected $fillable = [
        'id_PR',
        'id_barang',
        'id_supplier',
        'hargabarangPR',      // input manual (harga beli ke supplier)
        'kuantitasbarangPR',  // jumlah yang dipesan
    ];
 
    protected $casts = [
        'hargabarangPR' => 'decimal:2',
    ];

    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class, 'id_PR', 'id_PR');
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang', 'id_barang');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'id_supplier', 'id_supplier');
    }
}
