<?php

namespace App\Models;

use App\Models\Sale;
use Illuminate\Database\Eloquent\Model;

class SalesItem extends Model
{
    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'price_at_sale',
        'subtotal',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
