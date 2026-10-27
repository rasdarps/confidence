<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockAdjustment extends Model
{
    use HasFactory, SoftDeletes;
    use Auditable;

    protected $fillable = [
        'adjustment_date',
        'reference_no',
        'adjustment_type',
        'reason',
        'notes',
        'accounting_status',
        'accounting_journal_id',
        'accounting_posted_at',
        'total_qty',
        'total_value',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'adjustment_date' => 'date',
        'total_qty' => 'decimal:2',
        'total_value' => 'decimal:2',
        'accounting_journal_id' => 'integer',
        'accounting_posted_at' => 'datetime',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'deleted_by' => 'integer',
    ];

    public function items()
    {
        return $this->hasMany(\App\Models\StockAdjustmentItem::class, 'stock_adjustment_id');
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
