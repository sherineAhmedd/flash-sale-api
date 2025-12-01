<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
      protected $fillable = [
        'name',
        'stock',
        'price',
    ];
    public function availableStock(): int
{
    // Sum active holds only
        $reserved = $this->holds()
            ->where('expires_at', '>', now())
            ->sum('quantity');

       return (int) ($this->stock - $reserved);

}
    public function holds()
    {
       return $this->hasMany(Hold::class);
    }
}
