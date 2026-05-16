<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequest extends Model
{
    use HasFactory;

    protected $table = 'purchase_request';
    protected $primaryKey = 'id_PR';
    protected $fillable = [ 'referensi_PR',  // auto-generate: PR-2025-0001
        'tgl_PR',
        'status_PR',     // tertunda / disetujui / ditolak
        'id_admin',      // siapa yang buat (nullable, salah satu diisi)
        'id_owner',      // siapa yang buat (nullable, salah satu diisi)];
    ];

      protected $casts = [
        'tgl_PR' => 'date',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'id_admin', 'id_admin');
    }

    public function owner()
    {
        return $this->belongsTo(Owner::class, 'id_owner', 'id_owner');
    }

    public function details()
    {
        return $this->hasMany(DetailPurchaseRequest::class, 'id_PR', 'id_PR');
    }
    
     // 1 PR bisa jadi 1 PO
    public function purchaseOrder()
    {
        return $this->hasOne(PurchaseOrder::class, 'id_PR', 'id_PR');
    }
 
    // Auto-generate referensi_PR saat create
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($pr) {
            if (empty($pr->referensi_PR)) {
                $last = static::orderBy('id_PR', 'desc')->first();
                $next = $last ? ($last->id_PR + 1) : 1;
                $pr->referensi_PR = 'PR-' . date('Y') . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}
