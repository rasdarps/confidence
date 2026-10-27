<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\BankAccount;
use App\Models\ExpenseVoucherItem;
use App\Models\Payment;
use App\Models\PaymentDetail;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\PurchasePaymentDetail;
use App\Models\Sale;
use App\Support\PaymentStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    //
     /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        // $this->middleware('permission:dashboard-view');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $dashboardData = $this->buildDashboardData();

        return view('admin.index', $dashboardData);
    }

    public function insight()
    {
        $dashboardData = $this->buildDashboardData();

        return view('admin.insight', $dashboardData);
    }

    private function buildDashboardData(): array
    {
        $total_purchase = Purchase::get()->count(); 
        $total_invoice = Sale::get()->count();
        $pending_purchase = Purchase::get()->count();
        $approved_purchase =Purchase::get()->count();
        $total_sales = Payment::sum('total_amount');
        $total_cash = (float) Payment::query()
            ->whereIn('paid_status', [PaymentStatus::FULL_PAYMENT, PaymentStatus::PARTIAL_PAYMENT])
            ->sum('paid_amount');
        $total_credit_sales = (float) Payment::query()
            ->where('paid_status', PaymentStatus::FULL_CREDIT)
            ->sum('total_amount')
            + (float) Payment::query()
                ->where('paid_status', PaymentStatus::PARTIAL_PAYMENT)
                ->sum('due_amount');
        $total_purchases = PurchasePayment::sum('total_amount');
        $total_purchase_cash = (float) PurchasePayment::query()
            ->whereIn('paid_status', [PaymentStatus::FULL_PAYMENT, PaymentStatus::PARTIAL_PAYMENT])
            ->sum('paid_amount');
        $total_credit_purchases = (float) PurchasePayment::query()
            ->where('paid_status', PaymentStatus::FULL_CREDIT)
            ->sum('total_amount')
            + (float) PurchasePayment::query()
                ->where('paid_status', PaymentStatus::PARTIAL_PAYMENT)
                ->sum('due_amount');
        $total_stock_balance = (float) Product::sum('quantity');
        $total_stock_value = (float) Product::selectRaw('COALESCE(SUM(quantity * cost_price), 0) as total')
            ->value('total');

        // Dashboard finance snapshot metrics
        $cash_in_hand = $this->accountBalanceByCode('1000', 'asset');
        $cash_at_bank = (float) BankAccount::query()->sum('current_balance');
        $total_expenses = (float) ExpenseVoucherItem::query()->sum('amount');
        $debtors_balance = (float) Payment::query()->sum('due_amount');
        $creditors_balance = (float) PurchasePayment::query()->sum('due_amount');
        $total_stocks_quantity = $total_stock_balance;
       
        $products = DB::select('SELECT * FROM products WHERE quantity < stock_level');
        
        // Hybrid trend for last 30 days (invoice value, cash collected, and credit due).
        $trendStart = Carbon::today()->subDays(29);
        $dailyInvoiceTotals = DB::table('sales')
            ->join('payments', 'sales.id', '=', 'payments.sale_id')
            ->select(
                DB::raw('DATE(sales.date) as sale_date'),
                DB::raw('SUM(payments.total_amount) as total')
            )
            ->whereDate('sales.date', '>=', $trendStart->toDateString())
            ->groupBy(DB::raw('DATE(sales.date)'))
            ->orderBy(DB::raw('DATE(sales.date)'))
            ->pluck('total', 'sale_date')
            ->toArray();

        $dailyCashCollected = PaymentDetail::query()
            ->select(
                DB::raw('DATE(payment_details.date) as paid_date'),
                DB::raw('SUM(payment_details.current_paid_amount) as total')
            )
            ->whereDate('payment_details.date', '>=', $trendStart->toDateString())
            ->groupBy(DB::raw('DATE(payment_details.date)'))
            ->orderBy(DB::raw('DATE(payment_details.date)'))
            ->pluck('total', 'paid_date')
            ->toArray();

        $dailyCreditDue = DB::table('sales')
            ->join('payments', 'sales.id', '=', 'payments.sale_id')
            ->select(
                DB::raw('DATE(sales.date) as sale_date'),
                DB::raw('SUM(payments.due_amount) as total')
            )
            ->whereDate('sales.date', '>=', $trendStart->toDateString())
            ->where('payments.due_amount', '>', 0)
            ->groupBy(DB::raw('DATE(sales.date)'))
            ->orderBy(DB::raw('DATE(sales.date)'))
            ->pluck('total', 'sale_date')
            ->toArray();

        $salesTrendLabels = [];
        $salesTrendInvoiceTotals = [];
        $salesTrendCashCollectedTotals = [];
        $salesTrendCreditDueTotals = [];
        $salesTrendMovingAverage = [];

        for ($offset = 0; $offset < 30; $offset++) {
            $day = $trendStart->copy()->addDays($offset);
            $dayKey = $day->toDateString();
            $salesTrendLabels[] = $day->format('d M');
            $salesTrendInvoiceTotals[] = round((float) ($dailyInvoiceTotals[$dayKey] ?? 0), 2);
            $salesTrendCashCollectedTotals[] = round((float) ($dailyCashCollected[$dayKey] ?? 0), 2);
            $salesTrendCreditDueTotals[] = round((float) ($dailyCreditDue[$dayKey] ?? 0), 2);
        }

        foreach ($salesTrendInvoiceTotals as $index => $value) {
            $windowStart = max(0, $index - 6);
            $windowSlice = array_slice($salesTrendInvoiceTotals, $windowStart, $index - $windowStart + 1);
            $windowAvg = count($windowSlice) > 0 ? array_sum($windowSlice) / count($windowSlice) : 0;
            $salesTrendMovingAverage[] = round($windowAvg, 2);
        }

        // Hybrid purchase trend for last 30 days (invoice value, cash paid, and credit due).
        $dailyPurchaseInvoiceTotals = DB::table('purchases')
            ->join('purchase_payments', 'purchases.id', '=', 'purchase_payments.purchase_id')
            ->select(
                DB::raw('DATE(purchases.date) as purchase_date'),
                DB::raw('SUM(purchase_payments.total_amount) as total')
            )
            ->whereDate('purchases.date', '>=', $trendStart->toDateString())
            ->groupBy(DB::raw('DATE(purchases.date)'))
            ->orderBy(DB::raw('DATE(purchases.date)'))
            ->pluck('total', 'purchase_date')
            ->toArray();

        $dailyPurchaseCashPaid = PurchasePaymentDetail::query()
            ->select(
                DB::raw('DATE(purchase_payment_details.date) as paid_date'),
                DB::raw('SUM(purchase_payment_details.current_paid_amount) as total')
            )
            ->whereDate('purchase_payment_details.date', '>=', $trendStart->toDateString())
            ->groupBy(DB::raw('DATE(purchase_payment_details.date)'))
            ->orderBy(DB::raw('DATE(purchase_payment_details.date)'))
            ->pluck('total', 'paid_date')
            ->toArray();

        $dailyPurchaseCreditDue = DB::table('purchases')
            ->join('purchase_payments', 'purchases.id', '=', 'purchase_payments.purchase_id')
            ->select(
                DB::raw('DATE(purchases.date) as purchase_date'),
                DB::raw('SUM(purchase_payments.due_amount) as total')
            )
            ->whereDate('purchases.date', '>=', $trendStart->toDateString())
            ->where('purchase_payments.due_amount', '>', 0)
            ->groupBy(DB::raw('DATE(purchases.date)'))
            ->orderBy(DB::raw('DATE(purchases.date)'))
            ->pluck('total', 'purchase_date')
            ->toArray();

        $purchaseTrendLabels = [];
        $purchaseTrendInvoiceTotals = [];
        $purchaseTrendCashPaidTotals = [];
        $purchaseTrendCreditDueTotals = [];
        $purchaseTrendMovingAverage = [];

        for ($offset = 0; $offset < 30; $offset++) {
            $day = $trendStart->copy()->addDays($offset);
            $dayKey = $day->toDateString();
            $purchaseTrendLabels[] = $day->format('d M');
            $purchaseTrendInvoiceTotals[] = round((float) ($dailyPurchaseInvoiceTotals[$dayKey] ?? 0), 2);
            $purchaseTrendCashPaidTotals[] = round((float) ($dailyPurchaseCashPaid[$dayKey] ?? 0), 2);
            $purchaseTrendCreditDueTotals[] = round((float) ($dailyPurchaseCreditDue[$dayKey] ?? 0), 2);
        }

        foreach ($purchaseTrendInvoiceTotals as $index => $value) {
            $windowStart = max(0, $index - 6);
            $windowSlice = array_slice($purchaseTrendInvoiceTotals, $windowStart, $index - $windowStart + 1);
            $windowAvg = count($windowSlice) > 0 ? array_sum($windowSlice) / count($windowSlice) : 0;
            $purchaseTrendMovingAverage[] = round($windowAvg, 2);
        }

        // Top category contribution by sales amount.
        $topCategoryRows = DB::table('sale_details')
            ->join('categories', 'sale_details.category_id', '=', 'categories.id')
            ->select(
                'categories.name as category_name',
                DB::raw('SUM(sale_details.selling_price) as total_sales')
            )
            ->groupBy('sale_details.category_id', 'categories.name')
            ->orderByDesc('total_sales')
            ->limit(6)
            ->get();

        $topCategoryLabels = $topCategoryRows->pluck('category_name')->values()->all();
        $topCategoryTotals = $topCategoryRows
            ->pluck('total_sales')
            ->map(fn ($value) => round((float) $value, 2))
            ->values()
            ->all();

        // Stock health risk: products below stock level ordered by shortfall.
        $stockRiskRows = Product::query()
            ->select('name', 'quantity', 'stock_level')
            ->whereColumn('quantity', '<', 'stock_level')
            ->orderByRaw('(stock_level - quantity) DESC')
            ->limit(8)
            ->get();

        $stockRiskLabels = $stockRiskRows->pluck('name')->values()->all();
        $stockRiskShortfall = $stockRiskRows
            ->map(function ($row) {
                return round(max(0, (float) $row->stock_level - (float) $row->quantity), 2);
            })
            ->values()
            ->all();

        // Expense insight: spent amount grouped by expense category.
        $expenseInsightRows = ExpenseVoucherItem::query()
            ->join('expense_categories', 'expense_voucher_items.expense_category_id', '=', 'expense_categories.id')
            ->select(
                'expense_categories.name as category_name',
                DB::raw('SUM(expense_voucher_items.amount) as total_spent')
            )
            ->groupBy('expense_categories.id', 'expense_categories.name')
            ->havingRaw('SUM(expense_voucher_items.amount) > 0')
            ->orderByDesc('total_spent')
            ->limit(8)
            ->get();

        $expenseInsightLabels = $expenseInsightRows->pluck('category_name')->values()->all();
        $expenseInsightTotals = $expenseInsightRows
            ->pluck('total_spent')
            ->map(fn ($value) => round((float) $value, 2))
            ->values()
            ->all();

        // Monthly hybrid overview for current year only (Jan-Dec).
        $currentYear = Carbon::today()->year;
        $monthlyInvoiceRaw = DB::table('sales')
            ->join('payments', 'sales.id', '=', 'payments.sale_id')
            ->select(
                DB::raw('MONTH(sales.date) as sale_month'),
                DB::raw('SUM(payments.total_amount) as total')
            )
            ->whereYear('sales.date', $currentYear)
            ->groupBy(DB::raw('MONTH(sales.date)'))
            ->orderBy(DB::raw('MONTH(sales.date)'))
            ->pluck('total', 'sale_month')
            ->toArray();

        $monthlyCashCollectedRaw = PaymentDetail::query()
            ->select(
                DB::raw('MONTH(payment_details.date) as paid_month'),
                DB::raw('SUM(payment_details.current_paid_amount) as total')
            )
            ->whereYear('payment_details.date', $currentYear)
            ->groupBy(DB::raw('MONTH(payment_details.date)'))
            ->orderBy(DB::raw('MONTH(payment_details.date)'))
            ->pluck('total', 'paid_month')
            ->toArray();

        $monthlyCreditDueRaw = DB::table('sales')
            ->join('payments', 'sales.id', '=', 'payments.sale_id')
            ->select(
                DB::raw('MONTH(sales.date) as sale_month'),
                DB::raw('SUM(payments.due_amount) as total')
            )
            ->whereYear('sales.date', $currentYear)
            ->where('payments.due_amount', '>', 0)
            ->groupBy(DB::raw('MONTH(sales.date)'))
            ->orderBy(DB::raw('MONTH(sales.date)'))
            ->pluck('total', 'sale_month')
            ->toArray();

        $monthlyPurchaseInvoiceRaw = DB::table('purchases')
            ->join('purchase_payments', 'purchases.id', '=', 'purchase_payments.purchase_id')
            ->select(
                DB::raw('MONTH(purchases.date) as purchase_month'),
                DB::raw('SUM(purchase_payments.total_amount) as total')
            )
            ->whereYear('purchases.date', $currentYear)
            ->groupBy(DB::raw('MONTH(purchases.date)'))
            ->orderBy(DB::raw('MONTH(purchases.date)'))
            ->pluck('total', 'purchase_month')
            ->toArray();

        $monthlyPurchaseCashPaidRaw = PurchasePaymentDetail::query()
            ->select(
                DB::raw('MONTH(purchase_payment_details.date) as paid_month'),
                DB::raw('SUM(purchase_payment_details.current_paid_amount) as total')
            )
            ->whereYear('purchase_payment_details.date', $currentYear)
            ->groupBy(DB::raw('MONTH(purchase_payment_details.date)'))
            ->orderBy(DB::raw('MONTH(purchase_payment_details.date)'))
            ->pluck('total', 'paid_month')
            ->toArray();

        $monthlyPurchaseCreditDueRaw = DB::table('purchases')
            ->join('purchase_payments', 'purchases.id', '=', 'purchase_payments.purchase_id')
            ->select(
                DB::raw('MONTH(purchases.date) as purchase_month'),
                DB::raw('SUM(purchase_payments.due_amount) as total')
            )
            ->whereYear('purchases.date', $currentYear)
            ->where('purchase_payments.due_amount', '>', 0)
            ->groupBy(DB::raw('MONTH(purchases.date)'))
            ->orderBy(DB::raw('MONTH(purchases.date)'))
            ->pluck('total', 'purchase_month')
            ->toArray();

        $monthlySalesLabels = [];
        $monthlyInvoiceTotals = [];
        $monthlyCashCollectedTotals = [];
        $monthlyCreditDueTotals = [];
        $monthlyPurchaseInvoiceTotals = [];
        $monthlyPurchaseCashPaidTotals = [];
        $monthlyPurchaseCreditDueTotals = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthlySalesLabels[] = Carbon::createFromDate($currentYear, $month, 1)->format('M');
            $monthlyInvoiceTotals[] = round((float) ($monthlyInvoiceRaw[$month] ?? 0), 2);
            $monthlyCashCollectedTotals[] = round((float) ($monthlyCashCollectedRaw[$month] ?? 0), 2);
            $monthlyCreditDueTotals[] = round((float) ($monthlyCreditDueRaw[$month] ?? 0), 2);
            $monthlyPurchaseInvoiceTotals[] = round((float) ($monthlyPurchaseInvoiceRaw[$month] ?? 0), 2);
            $monthlyPurchaseCashPaidTotals[] = round((float) ($monthlyPurchaseCashPaidRaw[$month] ?? 0), 2);
            $monthlyPurchaseCreditDueTotals[] = round((float) ($monthlyPurchaseCreditDueRaw[$month] ?? 0), 2);
        }

        $company = Company::first();
        return compact(
            'total_purchase',
            'total_invoice',
            'total_sales',
            'total_cash',
            'total_credit_sales',
            'total_purchases',
            'total_purchase_cash',
            'total_credit_purchases',
            'total_stock_balance',
            'total_stock_value',
            'cash_in_hand',
            'cash_at_bank',
            'total_expenses',
            'debtors_balance',
            'creditors_balance',
            'total_stocks_quantity',
            'pending_purchase',
            'products',
            'approved_purchase',
            'salesTrendLabels',
            'salesTrendInvoiceTotals',
            'salesTrendCashCollectedTotals',
            'salesTrendCreditDueTotals',
            'salesTrendMovingAverage',
            'purchaseTrendLabels',
            'purchaseTrendInvoiceTotals',
            'purchaseTrendCashPaidTotals',
            'purchaseTrendCreditDueTotals',
            'purchaseTrendMovingAverage',
            'topCategoryLabels',
            'topCategoryTotals',
            'stockRiskLabels',
            'stockRiskShortfall',
            'expenseInsightLabels',
            'expenseInsightTotals',
            'currentYear',
            'monthlySalesLabels',
            'monthlyInvoiceTotals',
            'monthlyCashCollectedTotals',
            'monthlyCreditDueTotals',
            'monthlyPurchaseInvoiceTotals',
            'monthlyPurchaseCashPaidTotals',
            'monthlyPurchaseCreditDueTotals',
            'company'
        );
    }

    private function accountBalanceByCode(string $code, string $type): float
    {
        $row = DB::table('accounting_accounts as a')
            ->leftJoin('accounting_journal_lines as l', 'l.account_id', '=', 'a.id')
            ->leftJoin('accounting_journals as j', function ($join) {
                $join->on('j.id', '=', 'l.journal_id')
                    ->where('j.status', '=', 'posted');
            })
            ->where('a.code', $code)
            ->selectRaw('ROUND(COALESCE(SUM(CASE WHEN j.id IS NOT NULL THEN l.debit ELSE 0 END),0),2) as debit_total, ROUND(COALESCE(SUM(CASE WHEN j.id IS NOT NULL THEN l.credit ELSE 0 END),0),2) as credit_total')
            ->first();

        $debit = (float) ($row->debit_total ?? 0);
        $credit = (float) ($row->credit_total ?? 0);

        if (in_array($type, ['asset', 'expense'], true)) {
            return round($debit - $credit, 2);
        }

        return round($credit - $debit, 2);
    }

    
}
