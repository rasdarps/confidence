<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CapitalLoanEntry extends Model
{
    use HasFactory;
    use Auditable;

    protected $guarded = [];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
        'accounting_posted_at' => 'datetime',
    ];

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function journal()
    {
        return $this->belongsTo(AccountingJournal::class, 'accounting_journal_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
