<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class AccountingJournal extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function outlet()
    {
        return $this->belongsTo(Outlet::class, 'outlet_id');
    }

    public static function hasOutletColumn(): bool
    {
        static $hasColumn = null;

        if ($hasColumn === null) {
            $hasColumn = Schema::hasTable('accounting_journals')
                && Schema::hasColumn('accounting_journals', 'outlet_id');
        }

        return $hasColumn;
    }

    public static function applyOutletFilter($query, ?int $outletId, string $alias = 'j'): void
    {
        if (!$outletId) {
            return;
        }

        if (self::hasOutletColumn()) {
            $query->where($alias . '.outlet_id', '=', $outletId);
            return;
        }

        $centralOutletId = (int) (Outlet::where('is_central', true)->value('id') ?? 1);

        $query->where(function ($q) use ($outletId, $centralOutletId, $alias) {
            $q->whereRaw("({$alias}.source_type IN ('sale','sale_cancel','sale_receipt') AND EXISTS (SELECT 1 FROM sales s WHERE s.id = {$alias}.source_id AND s.outlet_id = ?))", [$outletId])
              ->orWhereRaw("({$alias}.source_type IN ('expense_voucher','expense_voucher_reversal') AND EXISTS (SELECT 1 FROM expense_vouchers ev WHERE ev.id = {$alias}.source_id AND ev.outlet_id = ?))", [$outletId])
              ->orWhereRaw("({$alias}.source_type IN ('stock_adjustment','stock_adjustment_reversal') AND EXISTS (SELECT 1 FROM stock_adjustments sa WHERE sa.id = {$alias}.source_id AND sa.outlet_id = ?))", [$outletId])
              ->orWhereRaw("({$alias}.source_type IN ('cash_reconciliation','cash_reconciliation_reversal') AND EXISTS (SELECT 1 FROM cash_reconciliations cr WHERE cr.id = {$alias}.source_id AND cr.outlet_id = ?))", [$outletId]);

            if ($outletId === $centralOutletId) {
                $q->orWhereRaw("{$alias}.source_type NOT IN ('sale','sale_cancel','sale_receipt','expense_voucher','expense_voucher_reversal','stock_adjustment','stock_adjustment_reversal','cash_reconciliation','cash_reconciliation_reversal')");
            }
        });
    }
}
