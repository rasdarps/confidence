<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseCategory extends Model
{
    use HasFactory, SoftDeletes;
    use Auditable;

    protected $fillable = [
        'name',
        'accounting_account_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'accounting_account_id' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'deleted_by' => 'integer',
    ];

    public function account()
    {
        return $this->belongsTo(AccountingAccount::class, 'accounting_account_id');
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

    public function expenseVoucherItems()
    {
        return $this->hasMany(ExpenseVoucherItem::class, 'expense_category_id');
    }
}
