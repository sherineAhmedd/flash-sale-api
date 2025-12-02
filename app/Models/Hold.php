<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hold extends Model
{
    use HasFactory;
    
     protected $fillable = [
        'product_id',
        'quantity',
        'expires_at',
    ];
     public function order()
    {
        return $this->hasOne(Order::class);
    }
    protected $dates = ['expires_at'];
    // to get what this ID refers to so it can get the product.
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    //check if expired
    public function isExpired()
    {
        return now()->greaterThan($this->expires_at);
    }
    
      public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now())
                     ->where('used', false);
    }
}
