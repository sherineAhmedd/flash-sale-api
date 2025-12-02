<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentWebhook extends Model
{
    use HasFactory;
    
     protected $fillable = [
        'idempotency_key',
        'order_id',
        'status',
        'payload',
    ];
      
    protected $casts = [
        'payload' => 'array',  
    ];

     public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
