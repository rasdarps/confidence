<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransferItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_transfer_id',
        'product_id',
        'qty_dispatched',
        'qty_received',
        'unit_cost',
    ];

    protected $casts = [
        'stock_transfer_id' => 'integer',
        'product_id' => 'integer',
        'qty_dispatched' => 'decimal:2',
        'qty_received' => 'decimal:2',
        'unit_cost' => 'decimal:2',
    ];

    public function transfer()
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

