<?php

namespace App\Models;

use App\Models\ExpenseVoucherItem;
use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseVoucher extends Model
{
    use HasFactory, SoftDeletes;
    use Auditable;

    protected $fillable = [
        'outlet_id',
        'voucher_date',
        'reference_no',
        'total_amount',
        'notes',
        'accounting_status',
        'accounting_journal_id',
        'accounting_posted_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'outlet_id' => 'integer',
        'voucher_date' => 'date',
        'total_amount' => 'decimal:2',
        'accounting_journal_id' => 'integer',
        'accounting_posted_at' => 'datetime',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'deleted_by' => 'integer',
    ];

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function items()
    {
        return $this->hasMany(ExpenseVoucherItem::class, 'expense_voucher_id');
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

    public function accountingJournal()
    {
        return $this->belongsTo(AccountingJournal::class, 'accounting_journal_id');
    }
}
