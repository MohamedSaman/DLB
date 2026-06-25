<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnsProduct extends Model
{
    use HasFactory;
    protected $fillable = [
        'sale_id',
        'product_id',
        'variant_id',
        'variant_value',
        'return_quantity',
        'usable_quantity',
        'damaged_quantity',
        'selling_price',
        'total_amount',
        'notes',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function product()
    {
        return $this->belongsTo(ProductDetail::class, 'product_id');
    }
}
