<?php

namespace App\Models;

use App\Models\ExpenseVoucher;
use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseVoucherItem extends Model
{
    use HasFactory, SoftDeletes;
    use Auditable;

    protected $fillable = [
        'expense_voucher_id',
        'expense_category_id',
        'amount',
        'description',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'expense_voucher_id' => 'integer',
        'expense_category_id' => 'integer',
        'amount' => 'decimal:2',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'deleted_by' => 'integer',
    ];

    public function voucher()
    {
        return $this->belongsTo(ExpenseVoucher::class, 'expense_voucher_id');
    }

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
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
