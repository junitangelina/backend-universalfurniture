<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequest extends Model
{
    use HasFactory;

    protected $table = 'purchase_requests';
    protected $primaryKey = 'id_PR';
    protected $fillable = ['tgl_PR', 'status_PR', 'id_admin', 'id_owner'];

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
}
