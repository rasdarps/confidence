<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockAdjustmentItem extends Model
{
    use HasFactory, SoftDeletes;
    use Auditable;

    protected $fillable = [
        'stock_adjustment_id',
        'product_id',
        'qty',
        'unit_cost',
        'amount',
        'before_qty',
        'after_qty',
        'description',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'stock_adjustment_id' => 'integer',
        'product_id' => 'integer',
        'qty' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'amount' => 'decimal:2',
        'before_qty' => 'decimal:2',
        'after_qty' => 'decimal:2',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'deleted_by' => 'integer',
    ];

    public function adjustment()
    {
        return $this->belongsTo(\App\Models\StockAdjustment::class, 'stock_adjustment_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
