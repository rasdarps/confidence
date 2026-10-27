<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseEditTrail extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'edited_by',
        'before_total',
        'after_total',
        'delta_total',
        'reason',
        'changes',
    ];

    protected $casts = [
        'before_total' => 'decimal:2',
        'after_total' => 'decimal:2',
        'delta_total' => 'decimal:2',
        'changes' => 'array',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    public function editedBy()
    {
        return $this->belongsTo(User::class, 'edited_by');
    }
}
