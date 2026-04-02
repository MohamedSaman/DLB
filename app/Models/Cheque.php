<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cheque extends Model
{
    use HasFactory;

    protected $fillable = [
        'cheque_number',
        'cheque_date',
        'bank_name',
        'cheque_amount',
        'type',
        'name',
        'note',
        'status',
        'customer_id',
        'payment_id',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
