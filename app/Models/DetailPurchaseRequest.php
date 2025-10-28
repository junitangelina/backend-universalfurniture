<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailPurchaseRequest extends Model
{
    use HasFactory;

    protected $table = 'detail_purchase_requests';
    protected $primaryKey = 'id_detail_PR';
    protected $fillable = ['hargabarangPR', 'kuantitasbarangPR', 'id_PR', 'id_barang', 'id_supplier'];

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
