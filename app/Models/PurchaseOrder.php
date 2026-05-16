<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $table = 'purchase_order';
    protected $primaryKey = 'id_PO';

   protected $fillable = [
        'referensi_PO',
        'tgl_PO',
        'status_PO',  // diajukan / disetujui / ditolak / selesai
        'id_PR',      // dari PR mana PO ini dibuat
    ];
 
    protected $casts = [
        'tgl_PO' => 'date',
    ];
    
    // Relasi ke Purchase Request
    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class, 'id_PR', 'id_PR');
    }

    // Relasi ke detail PO
    public function details()
    {
        return $this->hasMany(DetailPurchaseOrder::class, 'id_PO', 'id_PO');
    }
}
