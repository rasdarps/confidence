<?php

namespace App\Services\Accounting;

use App\Models\AccountingAccountMapping;
use App\Models\AccountingJournal;
use App\Models\AccountingAccount;
use App\Models\CashReconciliation;
use App\Models\ExpenseVoucher;
use App\Models\Payment;
use App\Models\PaymentDetail;
use App\Models\PurchaseDetail;
use App\Models\PurchasePayment;
use App\Models\PurchasePaymentDetail;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\StockAdjustment;
use App\Support\PaymentStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccountingIntegrationService
{
    public function __construct(private JournalPostingService $journalPostingService)
    {
    }

    public function postSaleFromPayment(Sale $sale, Payment $payment, ?int $userId = null): ?int
    {
        if (!$this->accountingReady()) {
            return null;
        }

        $total = round((float) $payment->total_amount, 2);
        $paid = round((float) $payment->paid_amount, 2);
        $due = round((float) $payment->due_amount, 2);

        if ($total <= 0) {
            return null;
        }

        [$cashAccount, $receivableAccount, $salesAccount, $cogsAccount, $inventoryAccount] = $this->accountIds(['1000', '1100', '4000', '5000', '1200']);

        $lines = [];
        if ($paid > 0) {
            $lines[] = [
                'account_id' => $cashAccount,
                'debit' => $paid,
                'credit' => 0,
                'description' => 'Cash received for sale ' . $sale->sale_no,
            ];
        }

        if ($due > 0 || PaymentStatus::isCredit((string) $payment->paid_status)) {
            $receivableAmount = max($due, $total - $paid);
            if ($receivableAmount > 0) {
                $lines[] = [
                    'account_id' => $receivableAccount,
                    'debit' => $receivableAmount,
                    'credit' => 0,
                    'description' => 'Receivable created for sale ' . $sale->sale_no,
                    'entity_type' => 'customer',
                    'entity_id' => $payment->customer_id,
                ];
            }
        }

        $lines[] = [
            'account_id' => $salesAccount,
            'debit' => 0,
            'credit' => $total,
            'description' => 'Sales revenue for sale ' . $sale->sale_no,
        ];

        $cogsTotal = $this->calculateSaleCogsTotal($sale);
        if ($cogsTotal > 0) {
            $lines[] = [
                'account_id' => $cogsAccount,
                'debit' => $cogsTotal,
                'credit' => 0,
                'description' => 'COGS recognized for sale ' . $sale->sale_no,
            ];

            $lines[] = [
                'account_id' => $inventoryAccount,
                'debit' => 0,
                'credit' => $cogsTotal,
                'description' => 'Inventory reduction for sale ' . $sale->sale_no,
            ];
        }

        $journal = $this->journalPostingService->postJournal(
            sourceType: 'sale',
            sourceId: (int) $sale->id,
            referenceNo: (string) $sale->sale_no,
            description: 'Auto posting for Sale #' . $sale->sale_no,
            postingDate: (string) $sale->date,
            lines: $lines,
            postedBy: $userId
        );

        Sale::whereKey($sale->id)->update([
            'accounting_status' => 'posted',
            'accounting_journal_id' => $journal->id,
            'accounting_posted_at' => now(),
        ]);

        Payment::whereKey($payment->id)->update([
            'accounting_status' => 'posted',
            'accounting_journal_id' => $journal->id,
            'accounting_posted_at' => now(),
        ]);

        PaymentDetail::where('sale_id', $sale->id)->update([
            'accounting_status' => 'posted',
            'accounting_journal_id' => $journal->id,
            'accounting_posted_at' => now(),
        ]);

        return $journal->id;
    }

    public function postPurchaseFromPayment(Purchase $purchase, PurchasePayment $payment, ?int $userId = null): ?int
    {
        if (!$this->accountingReady()) {
            return null;
        }

        $total = round((float) $payment->total_amount, 2);
        $paid = round((float) $payment->paid_amount, 2);
        $due = round((float) $payment->due_amount, 2);

        if ($total <= 0) {
            return null;
        }

        [$cashAccount, $payableAccount, $inventoryAccount] = $this->accountIds(['1000', '2000', '1200']);

        $lines = [[
            'account_id' => $inventoryAccount,
            'debit' => $total,
            'credit' => 0,
            'description' => 'Inventory recognized for purchase ' . $purchase->purchase_no,
        ]];

        if ($paid > 0) {
            $lines[] = [
                'account_id' => $cashAccount,
                'debit' => 0,
                'credit' => $paid,
                'description' => 'Cash paid for purchase ' . $purchase->purchase_no,
            ];
        }

        if ($due > 0 || PaymentStatus::isCredit((string) $payment->paid_status)) {
            $payableAmount = max($due, $total - $paid);
            if ($payableAmount > 0) {
                $lines[] = [
                    'account_id' => $payableAccount,
                    'debit' => 0,
                    'credit' => $payableAmount,
                    'description' => 'Payable created for purchase ' . $purchase->purchase_no,
                    'entity_type' => 'supplier',
                    'entity_id' => $payment->supplier_id,
                ];
            }
        }

        $journal = $this->journalPostingService->postJournal(
            sourceType: 'purchase',
            sourceId: (int) $purchase->id,
            referenceNo: (string) $purchase->purchase_no,
            description: 'Auto posting for Purchase #' . $purchase->purchase_no,
            postingDate: (string) $purchase->date,
            lines: $lines,
            postedBy: $userId
        );

        Purchase::whereKey($purchase->id)->update([
            'accounting_status' => 'posted',
            'accounting_journal_id' => $journal->id,
            'accounting_posted_at' => now(),
        ]);

        PurchasePayment::whereKey($payment->id)->update([
            'accounting_status' => 'posted',
            'accounting_journal_id' => $journal->id,
            'accounting_posted_at' => now(),
        ]);

        PurchasePaymentDetail::where('purchase_id', $purchase->id)->update([
            'accounting_status' => 'posted',
            'accounting_journal_id' => $journal->id,
            'accounting_posted_at' => now(),
        ]);

        return $journal->id;
    }

    public function reverseSale(Sale $sale, Payment $payment, string $reason, ?int $userId = null): ?int
    {
        if (!$this->accountingReady()) {
            return null;
        }

        $total = round((float) $payment->total_amount, 2);
        $paid = round((float) $payment->paid_amount, 2);
        $due = round((float) $payment->due_amount, 2);

        if ($total <= 0) {
            return null;
        }

        [$cashAccount, $receivableAccount, $salesAccount, $cogsAccount, $inventoryAccount] = $this->accountIds(['1000', '1100', '4000', '5000', '1200']);

        $lines = [[
            'account_id' => $salesAccount,
            'debit' => $total,
            'credit' => 0,
            'description' => 'Reversal of sale revenue ' . $sale->sale_no,
        ]];

        if ($paid > 0) {
            $lines[] = [
                'account_id' => $cashAccount,
                'debit' => 0,
                'credit' => $paid,
                'description' => 'Reverse cash for sale cancellation ' . $sale->sale_no,
            ];
        }

        if ($due > 0) {
            $lines[] = [
                'account_id' => $receivableAccount,
                'debit' => 0,
                'credit' => $due,
                'description' => 'Reverse receivable for sale cancellation ' . $sale->sale_no,
                'entity_type' => 'customer',
                'entity_id' => $payment->customer_id,
            ];
        }

        $cogsTotal = $this->postedSaleCogsTotal($sale, $cogsAccount, $inventoryAccount);
        if ($cogsTotal > 0) {
            $lines[] = [
                'account_id' => $inventoryAccount,
                'debit' => $cogsTotal,
                'credit' => 0,
                'description' => 'Reverse inventory reduction for sale cancellation ' . $sale->sale_no,
            ];

            $lines[] = [
                'account_id' => $cogsAccount,
                'debit' => 0,
                'credit' => $cogsTotal,
                'description' => 'Reverse COGS for sale cancellation ' . $sale->sale_no,
            ];
        }

        $journal = $this->journalPostingService->postJournal(
            sourceType: 'sale_cancel',
            sourceId: (int) $sale->id,
            referenceNo: (string) $sale->sale_no,
            description: 'Sale cancellation posting. Reason: ' . $reason,
            postingDate: now()->toDateString(),
            lines: $lines,
            postedBy: $userId
        );

        Sale::whereKey($sale->id)->update([
            'accounting_status' => 'reversed',
            'accounting_journal_id' => $journal->id,
            'accounting_posted_at' => now(),
        ]);

        Payment::whereKey($payment->id)->update([
            'accounting_status' => 'reversed',
            'accounting_journal_id' => $journal->id,
            'accounting_posted_at' => now(),
        ]);

        PaymentDetail::where('sale_id', $sale->id)->update([
            'accounting_status' => 'reversed',
            'accounting_journal_id' => $journal->id,
            'accounting_posted_at' => now(),
        ]);

        return $journal->id;
    }

    public function reversePurchase(Purchase $purchase, PurchasePayment $payment, string $reason, ?int $userId = null): ?int
    {
        if (!$this->accountingReady()) {
            return null;
        }

        $total = round((float) $payment->total_amount, 2);
        $paid = round((float) $payment->paid_amount, 2);
        $due = round((float) $payment->due_amount, 2);

        if ($total <= 0) {
            return null;
        }

        [$cashAccount, $payableAccount, $inventoryAccount] = $this->accountIds(['1000', '2000', '1200']);

        $lines = [];
        if ($paid > 0) {
            $lines[] = [
                'account_id' => $cashAccount,
                'debit' => $paid,
                'credit' => 0,
                'description' => 'Reverse cash paid for purchase cancellation ' . $purchase->purchase_no,
            ];
        }

        if ($due > 0) {
            $lines[] = [
                'account_id' => $payableAccount,
                'debit' => $due,
                'credit' => 0,
                'description' => 'Reverse payable for purchase cancellation ' . $purchase->purchase_no,
                'entity_type' => 'supplier',
                'entity_id' => $payment->supplier_id,
            ];
        }

        $lines[] = [
            'account_id' => $inventoryAccount,
            'debit' => 0,
            'credit' => $total,
            'description' => 'Reverse inventory recognition ' . $purchase->purchase_no,
        ];

        $journal = $this->journalPostingService->postJournal(
            sourceType: 'purchase_cancel',
            sourceId: (int) $purchase->id,
            referenceNo: (string) $purchase->purchase_no,
            description: 'Purchase cancellation posting. Reason: ' . $reason,
            postingDate: now()->toDateString(),
            lines: $lines,
            postedBy: $userId
        );

        Purchase::whereKey($purchase->id)->update([
            'accounting_status' => 'reversed',
            'accounting_journal_id' => $journal->id,
            'accounting_posted_at' => now(),
        ]);

        PurchasePayment::whereKey($payment->id)->update([
            'accounting_status' => 'reversed',
            'accounting_journal_id' => $journal->id,
            'accounting_posted_at' => now(),
        ]);

        PurchasePaymentDetail::where('purchase_id', $purchase->id)->update([
            'accounting_status' => 'reversed',
            'accounting_journal_id' => $journal->id,
            'accounting_posted_at' => now(),
        ]);

        return $journal->id;
    }

    public function postCustomerReceipt(Payment $payment, PaymentDetail $paymentDetail, ?int $userId = null): ?int
    {
        if (!$this->accountingReady()) {
            return null;
        }

        $amount = round((float) $paymentDetail->current_paid_amount, 2);
        if ($amount <= 0) {
            return null;
        }

        [$cashAccount, $receivableAccount] = $this->accountIds(['1000', '1100']);

        $journal = $this->journalPostingService->postJournal(
            sourceType: 'sale_receipt',
            sourceId: (int) $payment->sale_id,
            referenceNo: 'SALE-' . (string) $payment->sale_id,
            description: 'Customer receipt for sale #' . $payment->sale_id,
            postingDate: (string) $paymentDetail->date,
            lines: [
                [
                    'account_id' => $cashAccount,
                    'debit' => $amount,
                    'credit' => 0,
                    'description' => 'Cash received from customer',
                ],
                [
                    'account_id' => $receivableAccount,
                    'debit' => 0,
                    'credit' => $amount,
                    'description' => 'Reduce accounts receivable',
                    'entity_type' => 'customer',
                    'entity_id' => $payment->customer_id,
                ],
            ],
            postedBy: $userId
        );

        PaymentDetail::whereKey($paymentDetail->id)->update([
            'accounting_status' => 'posted',
            'accounting_journal_id' => $journal->id,
            'accounting_posted_at' => now(),
        ]);

        return $journal->id;
    }

    public function postSupplierSettlement(PurchasePayment $payment, PurchasePaymentDetail $paymentDetail, ?int $userId = null): ?int
    {
        if (!$this->accountingReady()) {
            return null;
        }

        $amount = round((float) $paymentDetail->current_paid_amount, 2);
        if ($amount <= 0) {
            return null;
        }

        [$cashAccount, $payableAccount] = $this->accountIds(['1000', '2000']);

        $journal = $this->journalPostingService->postJournal(
            sourceType: 'purchase_settlement',
            sourceId: (int) $payment->purchase_id,
            referenceNo: 'PUR-' . (string) $payment->purchase_id,
            description: 'Supplier payment for purchase #' . $payment->purchase_id,
            postingDate: (string) $paymentDetail->date,
            lines: [
                [
                    'account_id' => $payableAccount,
                    'debit' => $amount,
                    'credit' => 0,
                    'description' => 'Reduce accounts payable',
                    'entity_type' => 'supplier',
                    'entity_id' => $payment->supplier_id,
                ],
                [
                    'account_id' => $cashAccount,
                    'debit' => 0,
                    'credit' => $amount,
                    'description' => 'Cash paid to supplier',
                ],
            ],
            postedBy: $userId
        );

        PurchasePaymentDetail::whereKey($paymentDetail->id)->update([
            'accounting_status' => 'posted',
            'accounting_journal_id' => $journal->id,
            'accounting_posted_at' => now(),
        ]);

        return $journal->id;
    }

    public function postExpenseVoucher(ExpenseVoucher $voucher, ?int $userId = null): ?int
    {
        if (!$this->accountingReady()) {
            return null;
        }

        $total = round((float) $voucher->total_amount, 2);
        if ($total <= 0) {
            return null;
        }

        [$debitAccountId, $creditAccountId] = $this->resolveMapping('expense.payment', '5200', '1000');

        $journal = $this->journalPostingService->postJournal(
            sourceType: 'expense_voucher',
            sourceId: (int) $voucher->id,
            referenceNo: (string) ($voucher->reference_no ?? ('EXP-' . $voucher->id)),
            description: 'Expense voucher posting #' . $voucher->id,
            postingDate: (string) $voucher->voucher_date,
            lines: [
                [
                    'account_id' => $debitAccountId,
                    'debit' => $total,
                    'credit' => 0,
                    'description' => 'Expense recognized',
                ],
                [
                    'account_id' => $creditAccountId,
                    'debit' => 0,
                    'credit' => $total,
                    'description' => 'Expense payment source',
                ],
            ],
            postedBy: $userId
        );

        ExpenseVoucher::whereKey($voucher->id)->update([
            'accounting_status' => 'posted',
            'accounting_journal_id' => $journal->id,
            'accounting_posted_at' => now(),
        ]);

        return $journal->id;
    }

    public function reverseExpenseVoucher(ExpenseVoucher $voucher, string $reason, ?int $userId = null): ?int
    {
        if (!$this->accountingReady()) {
            return null;
        }

        $journalId = (int) ($voucher->accounting_journal_id ?? 0);
        if ($journalId <= 0) {
            return null;
        }

        $reversalId = $this->reverseJournal(
            originalJournalId: $journalId,
            sourceType: 'expense_voucher_reversal',
            sourceId: (int) $voucher->id,
            postingDate: now()->toDateString(),
            reason: $reason,
            userId: $userId
        );

        ExpenseVoucher::whereKey($voucher->id)->update([
            'accounting_status' => 'reversed',
            'accounting_journal_id' => $reversalId ?: $journalId,
            'accounting_posted_at' => now(),
        ]);

        return $reversalId;
    }

    public function postStockAdjustment(StockAdjustment $adjustment, ?int $userId = null): ?int
    {
        if (!$this->accountingReady()) {
            return null;
        }

        $total = round((float) $adjustment->total_value, 2);
        if ($total <= 0) {
            return null;
        }

        $type = (string) ($adjustment->adjustment_type ?? 'mixed');
        $lines = [];

        if ($type === 'increase') {
            [$debitAccountId, $creditAccountId] = $this->resolveMapping('stock.adjustment.increase', '1200', '3000');
            $lines[] = ['account_id' => $debitAccountId, 'debit' => $total, 'credit' => 0, 'description' => 'Stock adjustment increase'];
            $lines[] = ['account_id' => $creditAccountId, 'debit' => 0, 'credit' => $total, 'description' => 'Stock adjustment offset'];
        } elseif ($type === 'decrease') {
            [$debitAccountId, $creditAccountId] = $this->resolveMapping('stock.adjustment.decrease', '5200', '1200');
            $lines[] = ['account_id' => $debitAccountId, 'debit' => $total, 'credit' => 0, 'description' => 'Stock adjustment decrease'];
            $lines[] = ['account_id' => $creditAccountId, 'debit' => 0, 'credit' => $total, 'description' => 'Inventory reduction'];
        } else {
            // Mixed lines are posted with net inventory effect: debit when net increase, credit when net decrease.
            $delta = (float) $adjustment->items()->get()->sum(function ($item) {
                $before = (float) $item->before_qty;
                $after = (float) $item->after_qty;
                $unitCost = (float) $item->unit_cost;
                return ($after - $before) * $unitCost;
            });

            if (round($delta, 2) === 0.0) {
                return null;
            }

            if ($delta > 0) {
                [$debitAccountId, $creditAccountId] = $this->resolveMapping('stock.adjustment.increase', '1200', '3000');
                $lines[] = ['account_id' => $debitAccountId, 'debit' => round($delta, 2), 'credit' => 0, 'description' => 'Net stock adjustment increase'];
                $lines[] = ['account_id' => $creditAccountId, 'debit' => 0, 'credit' => round($delta, 2), 'description' => 'Net stock adjustment offset'];
            } else {
                $amount = round(abs($delta), 2);
                [$debitAccountId, $creditAccountId] = $this->resolveMapping('stock.adjustment.decrease', '5200', '1200');
                $lines[] = ['account_id' => $debitAccountId, 'debit' => $amount, 'credit' => 0, 'description' => 'Net stock adjustment decrease'];
                $lines[] = ['account_id' => $creditAccountId, 'debit' => 0, 'credit' => $amount, 'description' => 'Net inventory reduction'];
            }
        }

        if (empty($lines)) {
            return null;
        }

        $journal = $this->journalPostingService->postJournal(
            sourceType: 'stock_adjustment',
            sourceId: (int) $adjustment->id,
            referenceNo: (string) ($adjustment->reference_no ?? ('STK-ADJ-' . $adjustment->id)),
            description: 'Stock adjustment posting #' . $adjustment->id,
            postingDate: (string) $adjustment->adjustment_date,
            lines: $lines,
            postedBy: $userId
        );

        StockAdjustment::whereKey($adjustment->id)->update([
            'accounting_status' => 'posted',
            'accounting_journal_id' => $journal->id,
            'accounting_posted_at' => now(),
        ]);

        return $journal->id;
    }

    public function reverseStockAdjustment(StockAdjustment $adjustment, string $reason, ?int $userId = null): ?int
    {
        if (!$this->accountingReady()) {
            return null;
        }

        $journalId = (int) ($adjustment->accounting_journal_id ?? 0);
        if ($journalId <= 0) {
            return null;
        }

        $reversalId = $this->reverseJournal(
            originalJournalId: $journalId,
            sourceType: 'stock_adjustment_reversal',
            sourceId: (int) $adjustment->id,
            postingDate: now()->toDateString(),
            reason: $reason,
            userId: $userId
        );

        StockAdjustment::whereKey($adjustment->id)->update([
            'accounting_status' => 'reversed',
            'accounting_journal_id' => $reversalId ?: $journalId,
            'accounting_posted_at' => now(),
        ]);

        return $reversalId;
    }

    public function postCashReconciliationVariance(CashReconciliation $reconciliation, ?int $userId = null): ?int
    {
        if (!$this->accountingReady()) {
            return null;
        }

        $variance = round((float) $reconciliation->variance, 2);
        if ($variance === 0.0) {
            return null;
        }

        $cashAccount = $this->accountIds(['1000'])[0];
        $lines = [];

        if ($variance > 0) {
            // Overage: increase cash and credit an income account.
            $incomeAccount = $this->resolveMapping('cash.reconciliation.overage', '1000', '5400')[1];
            $amount = abs($variance);
            $lines[] = ['account_id' => $cashAccount, 'debit' => $amount, 'credit' => 0, 'description' => 'Cash overage recognized'];
            $lines[] = ['account_id' => $incomeAccount, 'debit' => 0, 'credit' => $amount, 'description' => 'Cash overage offset'];
        } else {
            // Shortage: debit expense and reduce cash.
            $expenseAccount = $this->resolveMapping('cash.reconciliation.shortage', '5200', '1000')[0];
            $amount = abs($variance);
            $lines[] = ['account_id' => $expenseAccount, 'debit' => $amount, 'credit' => 0, 'description' => 'Cash shortage recognized'];
            $lines[] = ['account_id' => $cashAccount, 'debit' => 0, 'credit' => $amount, 'description' => 'Cash shortage offset'];
        }

        $journal = $this->journalPostingService->postJournal(
            sourceType: 'cash_reconciliation',
            sourceId: (int) $reconciliation->id,
            referenceNo: 'CASH-REC-' . $reconciliation->id,
            description: 'Cash reconciliation variance posting #' . $reconciliation->id,
            postingDate: (string) $reconciliation->business_date,
            lines: $lines,
            postedBy: $userId
        );

        CashReconciliation::whereKey($reconciliation->id)->update([
            'accounting_status' => 'posted',
            'accounting_journal_id' => $journal->id,
            'accounting_posted_at' => now(),
        ]);

        return $journal->id;
    }

    public function reverseCashReconciliationVariance(CashReconciliation $reconciliation, string $reason, ?int $userId = null): ?int
    {
        if (!$this->accountingReady()) {
            return null;
        }

        $journalId = (int) ($reconciliation->accounting_journal_id ?? 0);
        if ($journalId <= 0) {
            return null;
        }

        $reversalId = $this->reverseJournal(
            originalJournalId: $journalId,
            sourceType: 'cash_reconciliation_reversal',
            sourceId: (int) $reconciliation->id,
            postingDate: now()->toDateString(),
            reason: $reason,
            userId: $userId
        );

        CashReconciliation::whereKey($reconciliation->id)->update([
            'accounting_status' => 'reversed',
            'accounting_journal_id' => $reversalId ?: $journalId,
            'accounting_posted_at' => now(),
        ]);

        return $reversalId;
    }

    private function reverseJournal(
        int $originalJournalId,
        string $sourceType,
        int $sourceId,
        string $postingDate,
        string $reason,
        ?int $userId = null
    ): ?int {
        $original = AccountingJournal::query()->find($originalJournalId);
        if (!$original) {
            return null;
        }

        if ((string) $original->status === 'reversed' && (int) ($original->reversed_journal_id ?? 0) > 0) {
            return (int) $original->reversed_journal_id;
        }

        $originalLines = DB::table('accounting_journal_lines')
            ->where('journal_id', $original->id)
            ->get();

        if ($originalLines->isEmpty()) {
            return null;
        }

        $reversalLines = $originalLines->map(function ($line) {
            return [
                'account_id' => (int) $line->account_id,
                'debit' => (float) $line->credit,
                'credit' => (float) $line->debit,
                'description' => 'Reversal: ' . ($line->line_description ?? ''),
                'entity_type' => $line->entity_type,
                'entity_id' => $line->entity_id,
            ];
        })->toArray();

        $reversal = $this->journalPostingService->postJournal(
            sourceType: $sourceType,
            sourceId: $sourceId,
            referenceNo: (string) ($original->reference_no ?? $original->journal_no),
            description: 'Reversal of journal ' . $original->journal_no . '. Reason: ' . $reason,
            postingDate: $postingDate,
            lines: $reversalLines,
            postedBy: $userId
        );

        $original->update([
            'status' => 'reversed',
            'reversed_journal_id' => $reversal->id,
        ]);

        return (int) $reversal->id;
    }

    private function resolveMapping(string $eventKey, string $defaultDebitCode, string $defaultCreditCode): array
    {
        $mapping = AccountingAccountMapping::query()
            ->where('event_key', $eventKey)
            ->where('is_active', true)
            ->first();

        if ($mapping && $mapping->debit_account_id && $mapping->credit_account_id) {
            return [(int) $mapping->debit_account_id, (int) $mapping->credit_account_id];
        }

        [$debitId, $creditId] = $this->accountIds([$defaultDebitCode, $defaultCreditCode]);
        return [$debitId, $creditId];
    }

    private function calculateSaleCogsTotal(Sale $sale): float
    {
        $saleDate = (string) $sale->date;

        $details = SaleDetail::query()
            ->where('sale_id', $sale->id)
            ->get(['product_id', 'selling_qty']);

        $total = 0.0;
        foreach ($details as $detail) {
            $qty = (float) ($detail->selling_qty ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $unitCost = (float) PurchaseDetail::query()
                ->join('purchases', 'purchases.id', '=', 'purchase_details.purchase_id')
                ->where('purchase_details.product_id', $detail->product_id)
                ->whereNull('purchases.cancelled_at')
                ->whereDate('purchase_details.date', '<=', $saleDate)
                ->orderByDesc('purchase_details.date')
                ->orderByDesc('purchase_details.id')
                ->value('purchase_details.unit_cost_price');

            $total += round($qty * max(0, $unitCost), 2);
        }

        return round($total, 2);
    }

    private function postedSaleCogsTotal(Sale $sale, int $cogsAccountId, int $inventoryAccountId): float
    {
        $saleJournalId = (int) DB::table('accounting_journals')
            ->where('source_type', 'sale')
            ->where('source_id', (int) $sale->id)
            ->where('status', 'posted')
            ->orderByDesc('id')
            ->value('id');

        if ($saleJournalId <= 0) {
            return $this->calculateSaleCogsTotal($sale);
        }

        $cogsFromJournal = (float) DB::table('accounting_journal_lines')
            ->where('journal_id', $saleJournalId)
            ->where('account_id', $cogsAccountId)
            ->sum('debit');

        if ($cogsFromJournal > 0) {
            return round($cogsFromJournal, 2);
        }

        $inventoryCreditFromJournal = (float) DB::table('accounting_journal_lines')
            ->where('journal_id', $saleJournalId)
            ->where('account_id', $inventoryAccountId)
            ->sum('credit');

        if ($inventoryCreditFromJournal > 0) {
            return round($inventoryCreditFromJournal, 2);
        }

        return $this->calculateSaleCogsTotal($sale);
    }

    private function accountingReady(): bool
    {
        return Schema::hasTable('accounting_accounts')
            && Schema::hasTable('accounting_journals')
            && Schema::hasTable('accounting_journal_lines');
    }

    /**
     * @param  array<int, string>  $codes
     * @return array<int, int>
     */
    private function accountIds(array $codes): array
    {
        $map = AccountingAccount::query()->whereIn('code', $codes)->pluck('id', 'code');
        $ids = [];

        foreach ($codes as $code) {
            $id = $map[$code] ?? null;
            if (!$id) {
                throw new \RuntimeException('Missing chart of accounts code: ' . $code);
            }
            $ids[] = (int) $id;
        }

        return $ids;
    }
}
