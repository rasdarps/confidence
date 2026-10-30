<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OutletStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'outlet_id',
        'product_id',
        'quantity',
        'stock_level',
    ];

    protected $casts = [
        'outlet_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'decimal:2',
        'stock_level' => 'decimal:2',
    ];

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeInStock($query)
    {
        return $query->where('quantity', '>', 0);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('quantity', '<=', 'stock_level')
            ->where('quantity', '>', 0);
    }
}

