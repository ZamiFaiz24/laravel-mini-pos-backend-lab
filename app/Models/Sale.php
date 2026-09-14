<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'product_id',
        'total_amount',
        'status',
    ];

    public function products()
    {
        return $this->belongsTo(Product::class);
    }

    public function salesItems()
    {
        return $this->hasMany(SalesItem::class, 'sale_id');
    }
}
