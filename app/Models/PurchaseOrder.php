<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_code',
        'supplier_id',
        'order_date',
        'received_date',
        'grn_number',
        'grn_date',
        'status',
        'total_amount',
        'due_amount',
        'discount_amount',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];

    public function supplier()
    {
        return $this->belongsTo(ProductSupplier::class, 'supplier_id');
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class, 'order_id');
    }

    public function detail()
    {
        return $this->hasOne(ProductDetail::class, 'code');
    }

    public function returns()
    {
        return $this->hasMany(ReturnSupplier::class, 'purchase_order_id');
    }
}
