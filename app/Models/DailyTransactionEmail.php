<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DailyTransactionEmail extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'business_date',
        'sales_total',
        'expenses_total',
        'cash_shortage',
        'recipient_email',
        'sent_at',
        'sent_by',
        'type',
        'notes',
    ];

    protected $casts = [
        'business_date' => 'date',
        'sales_total' => 'float',
        'expenses_total' => 'float',
        'cash_shortage' => 'float',
        'sent_at' => 'datetime',
    ];

    /**
     * Get the company that owns this email record
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who sent this email
     */
    public function sentBy()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    /**
     * Scope: Get records sent today
     */
    public function scopeSentToday($query)
    {
        return $query->whereDate('sent_at', today());
    }

    /**
     * Scope: Get unsent records
     */
    public function scopeUnsent($query)
    {
        return $query->whereNull('sent_at');
    }

    /**
     * Check if email already sent for this company and date
     */
    public static function alreadySentToday($companyId, $date = null)
    {
        $date = $date ?? today();
        return static::where('company_id', $companyId)
            ->whereDate('business_date', $date)
            ->whereNotNull('sent_at')
            ->exists();
    }

    /**
     * Get the last sent email record for this company
     */
    public static function lastSent($companyId)
    {
        return static::where('company_id', $companyId)
            ->whereNotNull('sent_at')
            ->latest('sent_at')
            ->first();
    }

      public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
