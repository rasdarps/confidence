<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashReconciliation extends Model
{
    use HasFactory;
    use Auditable;

    protected $fillable = [
        'outlet_id',
        'business_date',
        'user_id',
        'opening_float',
        'expected_cash_sales',
        'expected_cash_expenses',
        'expected_closing_cash',
        'physical_counted_cash',
        'variance',
        'variance_type',
        'status',
        'notes',
        'approved_by',
        'approved_at',
        'reversed_by',
        'reversed_at',
        'reversal_reason',
        'accounting_status',
        'accounting_journal_id',
        'accounting_posted_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'outlet_id' => 'integer',
        'business_date' => 'date',
        'user_id' => 'integer',
        'opening_float' => 'decimal:2',
        'expected_cash_sales' => 'decimal:2',
        'expected_cash_expenses' => 'decimal:2',
        'expected_closing_cash' => 'decimal:2',
        'physical_counted_cash' => 'decimal:2',
        'variance' => 'decimal:2',
        'approved_at' => 'datetime',
        'reversed_at' => 'datetime',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'approved_by' => 'integer',
        'reversed_by' => 'integer',
        'accounting_journal_id' => 'integer',
        'accounting_posted_at' => 'datetime',
    ];

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function reversedBy()
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function accountingJournal()
    {
        return $this->belongsTo(AccountingJournal::class, 'accounting_journal_id');
    }
}
