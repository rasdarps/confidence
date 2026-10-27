<?php

namespace App\Console\Commands;

use App\Models\CashReconciliation;
use App\Models\ExpenseVoucher;
use App\Models\Payment;
use App\Models\PaymentDetail;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\PurchasePaymentDetail;
use App\Models\Sale;
use App\Models\StockAdjustment;
use App\Services\Accounting\AccountingIntegrationService;
use Illuminate\Console\Command;

class BackfillAccountingPostings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accounting:backfill-postings {--user=1 : User ID to stamp as posted_by}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill accounting postings for unposted transactional records';

    /**
     * Execute the console command.
     */
    public function handle(AccountingIntegrationService $integration): int
    {
        $userId = (int) $this->option('user');

        $totals = [
            'sales' => 0,
            'purchases' => 0,
            'customer_receipts' => 0,
            'supplier_settlements' => 0,
            'expense_vouchers' => 0,
            'stock_adjustments' => 0,
            'cash_reconciliations' => 0,
            'errors' => 0,
        ];

        foreach (Sale::where('accounting_status', 'unposted')->get() as $sale) {
            try {
                $payment = Payment::where('sale_id', $sale->id)->first();
                if ($payment) {
                    $integration->postSaleFromPayment($sale, $payment, $userId);
                    $totals['sales']++;
                }
            } catch (\Throwable $e) {
                $totals['errors']++;
                $this->warn('Sale #' . $sale->id . ' failed: ' . $e->getMessage());
            }
        }

        foreach (Purchase::where('accounting_status', 'unposted')->get() as $purchase) {
            try {
                $payment = PurchasePayment::where('purchase_id', $purchase->id)->first();
                if ($payment) {
                    $integration->postPurchaseFromPayment($purchase, $payment, $userId);
                    $totals['purchases']++;
                }
            } catch (\Throwable $e) {
                $totals['errors']++;
                $this->warn('Purchase #' . $purchase->id . ' failed: ' . $e->getMessage());
            }
        }

        foreach (PaymentDetail::where('accounting_status', 'unposted')->where('current_paid_amount', '>', 0)->get() as $detail) {
            try {
                $payment = Payment::where('sale_id', $detail->sale_id)->first();
                if ($payment) {
                    $integration->postCustomerReceipt($payment, $detail, $userId);
                    $totals['customer_receipts']++;
                }
            } catch (\Throwable $e) {
                $totals['errors']++;
                $this->warn('Customer receipt detail #' . $detail->id . ' failed: ' . $e->getMessage());
            }
        }

        foreach (PurchasePaymentDetail::where('accounting_status', 'unposted')->where('current_paid_amount', '>', 0)->get() as $detail) {
            try {
                $payment = PurchasePayment::where('purchase_id', $detail->purchase_id)->first();
                if ($payment) {
                    $integration->postSupplierSettlement($payment, $detail, $userId);
                    $totals['supplier_settlements']++;
                }
            } catch (\Throwable $e) {
                $totals['errors']++;
                $this->warn('Supplier settlement detail #' . $detail->id . ' failed: ' . $e->getMessage());
            }
        }

        foreach (ExpenseVoucher::where('accounting_status', 'unposted')->get() as $voucher) {
            try {
                $integration->postExpenseVoucher($voucher, $userId);
                $totals['expense_vouchers']++;
            } catch (\Throwable $e) {
                $totals['errors']++;
                $this->warn('Expense voucher #' . $voucher->id . ' failed: ' . $e->getMessage());
            }
        }

        foreach (StockAdjustment::where('accounting_status', 'unposted')->with('items')->get() as $adjustment) {
            try {
                $integration->postStockAdjustment($adjustment, $userId);
                $totals['stock_adjustments']++;
            } catch (\Throwable $e) {
                $totals['errors']++;
                $this->warn('Stock adjustment #' . $adjustment->id . ' failed: ' . $e->getMessage());
            }
        }

        foreach (CashReconciliation::where('accounting_status', 'unposted')->where('status', 'approved')->get() as $reconciliation) {
            try {
                $integration->postCashReconciliationVariance($reconciliation, $userId);
                $totals['cash_reconciliations']++;
            } catch (\Throwable $e) {
                $totals['errors']++;
                $this->warn('Cash reconciliation #' . $reconciliation->id . ' failed: ' . $e->getMessage());
            }
        }

        $this->info('Backfill complete');
        foreach ($totals as $key => $value) {
            $this->line($key . ': ' . $value);
        }

        return self::SUCCESS;
    }
}
