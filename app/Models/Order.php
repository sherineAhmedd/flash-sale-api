<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
     use HasFactory;
     
     protected $fillable = ['hold_id', 'status'];

    public function hold()
    {
        return $this->belongsTo(Hold::class);
    }
    public function paymentWebhooks()
    {
    return $this->hasMany(PaymentWebhook::class);
    }
}
