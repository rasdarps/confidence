<?php

namespace App\Services\Accounting;

use App\Models\AccountingFiscalPeriod;
use App\Models\AccountingJournal;
use App\Models\AccountingJournalLine;
use App\Models\CashReconciliation;
use App\Models\ExpenseVoucher;
use App\Models\Outlet;
use App\Models\Sale;
use App\Models\StockAdjustment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class JournalPostingService
{
    /**
     * Create and post a balanced journal entry.
     *
     * @param  array<int, array<string, mixed>>  $lines
     */
    public function postJournal(
        string $sourceType,
        ?int $sourceId,
        string $referenceNo,
        string $description,
        string $postingDate,
        array $lines,
        ?int $postedBy = null,
        ?int $outletId = null
    ): AccountingJournal {
        if (empty($lines)) {
            throw new InvalidArgumentException('Journal lines are required.');
        }

        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($lines as $line) {
            $debit = (float) ($line['debit'] ?? 0);
            $credit = (float) ($line['credit'] ?? 0);

            if ($debit < 0 || $credit < 0) {
                throw new InvalidArgumentException('Debit/Credit cannot be negative.');
            }

            if (($debit > 0 && $credit > 0) || ($debit == 0.0 && $credit == 0.0)) {
                throw new InvalidArgumentException('Each line must contain either debit or credit amount.');
            }

            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            throw new InvalidArgumentException('Unbalanced journal entry.');
        }

        $closedPeriod = AccountingFiscalPeriod::query()
            ->where('is_closed', true)
            ->whereDate('start_date', '<=', $postingDate)
            ->whereDate('end_date', '>=', $postingDate)
            ->first();

        if ($closedPeriod) {
            throw new InvalidArgumentException('Posting date falls in a closed fiscal period.');
        }

        $fiscalPeriodId = AccountingFiscalPeriod::query()
            ->whereDate('start_date', '<=', $postingDate)
            ->whereDate('end_date', '>=', $postingDate)
            ->value('id');

        $resolvedOutletId = $outletId ?: $this->resolveOutletId($sourceType, $sourceId);

        return DB::transaction(function () use (
            $sourceType,
            $sourceId,
            $referenceNo,
            $description,
            $postingDate,
            $lines,
            $totalDebit,
            $totalCredit,
            $postedBy,
            $fiscalPeriodId,
            $resolvedOutletId
        ) {
            $payload = [
                'journal_no' => $this->nextJournalNo($postingDate),
                'posting_date' => $postingDate,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'reference_no' => $referenceNo,
                'description' => $description,
                'status' => 'posted',
                'total_debit' => round($totalDebit, 2),
                'total_credit' => round($totalCredit, 2),
                'fiscal_period_id' => $fiscalPeriodId,
                'posted_by' => $postedBy,
                'posted_at' => now(),
            ];

            if (Schema::hasColumn('accounting_journals', 'outlet_id')) {
                $payload['outlet_id'] = $resolvedOutletId;
            }

            $journal = AccountingJournal::create($payload);

            foreach ($lines as $line) {
                AccountingJournalLine::create([
                    'journal_id' => $journal->id,
                    'account_id' => $line['account_id'],
                    'line_description' => $line['description'] ?? null,
                    'debit' => round((float) ($line['debit'] ?? 0), 2),
                    'credit' => round((float) ($line['credit'] ?? 0), 2),
                    'entity_type' => $line['entity_type'] ?? null,
                    'entity_id' => $line['entity_id'] ?? null,
                ]);
            }

            return $journal;
        });
    }

    private function resolveOutletId(string $sourceType, ?int $sourceId): ?int
    {
        if ($sourceId && $sourceId > 0) {
            if (in_array($sourceType, ['sale', 'sale_cancel', 'sale_receipt'], true)) {
                $id = Sale::whereKey($sourceId)->value('outlet_id');
                if ($id) {
                    return (int) $id;
                }
            } elseif (in_array($sourceType, ['expense_voucher', 'expense_voucher_reversal'], true)) {
                $id = ExpenseVoucher::whereKey($sourceId)->value('outlet_id');
                if ($id) {
                    return (int) $id;
                }
            } elseif (in_array($sourceType, ['stock_adjustment', 'stock_adjustment_reversal'], true)) {
                $id = StockAdjustment::whereKey($sourceId)->value('outlet_id');
                if ($id) {
                    return (int) $id;
                }
            } elseif (in_array($sourceType, ['cash_reconciliation', 'cash_reconciliation_reversal'], true)) {
                $id = CashReconciliation::whereKey($sourceId)->value('outlet_id');
                if ($id) {
                    return (int) $id;
                }
            }
        }

        if (auth()->check() && auth()->user()->outlet_id && !in_array($sourceType, ['purchase', 'purchase_cancel', 'purchase_settlement'], true)) {
            return (int) auth()->user()->outlet_id;
        }

        $centralId = Outlet::where('is_central', true)->value('id');
        return $centralId ? (int) $centralId : null;
    }

    private function nextJournalNo(string $postingDate): string
    {
        $prefix = 'JRN-' . date('Ym', strtotime($postingDate));
        $latest = AccountingJournal::query()
            ->where('journal_no', 'like', $prefix . '-%')
            ->orderByDesc('journal_no')
            ->value('journal_no');

        if (!$latest) {
            return $prefix . '-0001';
        }

        $parts = explode('-', $latest);
        $last = (int) end($parts);

        return $prefix . '-' . str_pad((string) ($last + 1), 4, '0', STR_PAD_LEFT);
    }
}
