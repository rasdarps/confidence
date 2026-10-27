<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockLedger extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'movement_type',
        'reference_type',
        'reference_id',
        'reference_detail_id',
        'movement_at',
        'qty_in',
        'qty_out',
        'delta_qty',
        'balance_before',
        'balance_after',
        'unit_cost',
        'unit_price',
        'reason',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'reference_id' => 'integer',
        'reference_detail_id' => 'integer',
        'movement_at' => 'datetime',
        'qty_in' => 'decimal:2',
        'qty_out' => 'decimal:2',
        'delta_qty' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'created_by' => 'integer',
    ];

    protected static function booted(): void
    {
        // Ledger is append-only for audit integrity.
        static::updating(function () {
            throw new \RuntimeException('Stock ledger is append-only and cannot be updated.');
        });

        static::deleting(function () {
            throw new \RuntimeException('Stock ledger is append-only and cannot be deleted.');
        });
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
