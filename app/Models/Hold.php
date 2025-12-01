<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hold extends Model
{
     protected $fillable = [
        'product_id',
        'quantity',
        'expires_at',
    ];
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
}
