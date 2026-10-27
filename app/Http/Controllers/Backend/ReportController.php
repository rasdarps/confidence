<?php

namespace App\Http\Controllers\Backend;

use App\DataTables\Admin\Report\ExpenseReportDataTable;
use App\DataTables\Admin\Report\Purchase\PurchaseCashMovementDataTable;
use App\DataTables\Admin\Report\Purchase\PurchaseCreditMovementDataTable;
use App\DataTables\Admin\Report\Purchase\PurchaseEditTrailDataTable;
use App\DataTables\Admin\Report\Purchase\PurchaseLinesReportDataTable;
use App\DataTables\Admin\Report\ProfitReportDataTable;
use App\DataTables\Admin\Report\Sales\SalesCashMovementDataTable;
use App\DataTables\Admin\Report\Sales\SalesCreditMovementDataTable;
use App\DataTables\Admin\Report\Sales\SalesLinesReportDataTable;
use App\DataTables\Admin\Report\StockAdjustmentReportDataTable;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CashReconciliation;
use App\Models\ExpenseCategory;
use App\Models\ExpenseVoucherItem;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\PurchaseEditTrail;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Payment;
use App\Models\PaymentDetail;
use App\Models\Product;
use App\Models\PurchasePayment;
use App\Models\PurchasePaymentDetail;
use App\Models\Supplier;
use App\Models\StockLedger;
use App\Models\StockAdjustmentItem;
use App\Models\User;
use App\Support\PaymentStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:report-profit', ['only' => ['profitReport', 'profitSummary']]);
        $this->middleware('permission:report-sales-lines', ['only' => ['salesLinesReport', 'salesLinesSummary']]);
        $this->middleware('permission:report-purchase-lines', ['only' => ['purchaseLinesReport', 'purchaseLinesSummary']]);
        $this->middleware('permission:report-purchase-edit-trail', ['only' => ['purchaseEditTrailReport']]);
        $this->middleware('permission:report-sales-cash-movement', ['only' => ['salesCashMovementReport', 'salesCashMovementSummary']]);
        $this->middleware('permission:report-purchase-cash-movement', ['only' => ['purchaseCashMovementReport', 'purchaseCashMovementSummary']]);
        $this->middleware('permission:report-sales-credit-movement', ['only' => ['salesCreditMovementReport', 'salesCreditMovementSummary']]);
        $this->middleware('permission:report-purchase-credit-movement', ['only' => ['purchaseCreditMovementReport', 'purchaseCreditMovementSummary']]);
        $this->middleware('permission:report-expense', ['only' => ['expenseReport']]);
        $this->middleware('permission:report-stock-adjustment', ['only' => ['stockAdjustmentReport']]);
        $this->middleware('permission:report-summary-ledger', ['only' => ['summaryLedger']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function profitReport(ProfitReportDataTable $dataTable){
        // $allData = Sale::orderBy('date','desc')->orderBy('id','desc')->get();
        $sales = Sale::orderBy('sale_no')->get();
        $users = User::orderBy('name')->get();
        $customers = Customer::orderBy('name')->get();
        return $dataTable->render('backend.report.profit', compact('sales', 'users', 'customers'));

    }

    public function profitSummary(Request $request)
    {
        $salesSub = DB::table('payments')
            ->selectRaw('sale_id, SUM(total_amount) as sales_total')
            ->groupBy('sale_id');

        $costSub = DB::table('sale_details as idt')
            ->selectRaw(
                'idt.sale_id, SUM(idt.selling_qty * COALESCE((
                    SELECT pd.unit_cost_price
                    FROM purchase_details pd
                    WHERE pd.product_id = idt.product_id
                    ORDER BY pd.date DESC, pd.id DESC
                    LIMIT 1
                ), 0)) as cost_total'
            )
            ->groupBy('idt.sale_id');

        $invoiceQuery = Sale::query()
            ->leftJoinSub($salesSub, 'sales_agg', function ($join) {
                $join->on('sales_agg.sale_id', '=', 'sales.id');
            })
            ->leftJoinSub($costSub, 'costs_agg', function ($join) {
                $join->on('costs_agg.sale_id', '=', 'sales.id');
            });

        if ($invoiceId = $request->get('sale_id')) {
            $invoiceQuery->where('sales.id', $invoiceId);
        }

        if ($customerId = $request->get('customer_id')) {
            $invoiceQuery->whereHas('payment.customer', function ($q) use ($customerId) {
                $q->where('id', $customerId);
            });
        }

        if ($createdBy = $request->get('created_by')) {
            $invoiceQuery->where('sales.created_by', $createdBy);
        }

        if ($fromDate = $request->get('from_date')) {
            $invoiceQuery->whereDate('sales.date', '>=', $fromDate);
        }

        if ($toDate = $request->get('to_date')) {
            $invoiceQuery->whereDate('sales.date', '<=', $toDate);
        }

        $invoiceSummary = $invoiceQuery->selectRaw('
                COALESCE(SUM(sales_agg.sales_total), 0) as gross_sales,
                COALESCE(SUM(costs_agg.cost_total), 0) as gross_cost,
                COALESCE(SUM(sales_agg.sales_total), 0) - COALESCE(SUM(costs_agg.cost_total), 0) as gross_profit
            ')
            ->first();

        $expenseQuery = ExpenseVoucherItem::query()
            ->whereHas('voucher', function ($q) use ($request) {
                if ($fromDate = $request->get('from_date')) {
                    $q->whereDate('voucher_date', '>=', $fromDate);
                }

                if ($toDate = $request->get('to_date')) {
                    $q->whereDate('voucher_date', '<=', $toDate);
                }
            });

        $totalExpenses = (float) $expenseQuery->sum('amount');
        $grossProfit = (float) ($invoiceSummary->gross_profit ?? 0);

        $cashAdjustments = $this->approvedCashAdjustmentTotals($request);
        $cashShortageLoss = $cashAdjustments['shortage_loss'];
        $cashOverageGain = $cashAdjustments['overage_gain'];
        $cashNetAdjustment = $cashOverageGain - $cashShortageLoss;

        $stockAdjustments = $this->stockAdjustmentTotals($request);
        $stockShortageLoss = $stockAdjustments['shortage_loss'];
        $stockOverageGain = $stockAdjustments['overage_gain'];
        $stockNetAdjustment = $stockOverageGain - $stockShortageLoss;

        return response()->json([
            'gross_sales' => (float) ($invoiceSummary->gross_sales ?? 0),
            'gross_cost' => (float) ($invoiceSummary->gross_cost ?? 0),
            'gross_profit' => $grossProfit,
            'total_expenses' => $totalExpenses,
            'cash_shortage_loss' => $cashShortageLoss,
            'cash_overage_gain' => $cashOverageGain,
            'cash_net_adjustment' => $cashNetAdjustment,
            'stock_shortage_loss' => $stockShortageLoss,
            'stock_overage_gain' => $stockOverageGain,
            'stock_net_adjustment' => $stockNetAdjustment,
            'net_profit' => $grossProfit - $totalExpenses - $cashShortageLoss + $cashOverageGain - $stockShortageLoss + $stockOverageGain,
        ]);
    }

    public function expenseReport(ExpenseReportDataTable $dataTable)
    {
        $users = User::orderBy('name')->get();
        $categories = ExpenseCategory::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $monthTotal = ExpenseVoucherItem::query()
            ->whereHas('voucher', function ($q) {
                $q->whereMonth('voucher_date', now()->month)
                    ->whereYear('voucher_date', now()->year);
            })
            ->sum('amount');

        $overallTotal = ExpenseVoucherItem::query()->sum('amount');

        return $dataTable->render('backend.report.expense', compact('users', 'categories', 'monthTotal', 'overallTotal'));
    }

    public function stockAdjustmentReport(StockAdjustmentReportDataTable $dataTable)
    {
        $users = User::orderBy('name')->get(['id', 'name']);
        $products = Product::orderBy('name')->get(['id', 'name']);

        $monthTotal = StockAdjustmentItem::query()
            ->whereHas('adjustment', function ($q) {
                $q->whereMonth('adjustment_date', now()->month)
                    ->whereYear('adjustment_date', now()->year);
            })
            ->sum('amount');

        $overallTotal = StockAdjustmentItem::query()->sum('amount');

        return $dataTable->render('backend.report.stock_adjustment', compact('users', 'products', 'monthTotal', 'overallTotal'));
    }

    public function salesLinesReport(SalesLinesReportDataTable $dataTable)
    {
        $sales = Sale::orderBy('sale_no')->get(['id', 'sale_no']);
        $customers = Customer::orderBy('name')->get(['id', 'name']);
        $products = Product::orderBy('name')->get(['id', 'name']);
        $categories = Category::orderBy('name')->get(['id', 'name']);

        return $dataTable->render('backend.report.sales_lines', compact('sales', 'customers', 'products', 'categories'));
    }

    public function purchaseLinesReport(PurchaseLinesReportDataTable $dataTable)
    {
        $purchases = Purchase::orderBy('purchase_no')->get(['id', 'purchase_no']);
        $suppliers = Supplier::orderBy('name')->get(['id', 'name']);
        $products = Product::orderBy('name')->get(['id', 'name']);
        $categories = Category::orderBy('name')->get(['id', 'name']);

        return $dataTable->render('backend.report.purchase_lines', compact('purchases', 'suppliers', 'products', 'categories'));
    }

    public function purchaseEditTrailReport(PurchaseEditTrailDataTable $dataTable)
    {
        $purchases = Purchase::orderBy('purchase_no')->get(['id', 'purchase_no']);
        $editedByUsers = User::whereIn('id', PurchaseEditTrail::query()->select('edited_by')->whereNotNull('edited_by'))
            ->orderBy('name')
            ->get(['id', 'name']);

        return $dataTable->render('backend.report.purchase_edit_trail', compact('purchases', 'editedByUsers'));
    }

    public function salesLinesSummary(Request $request)
    {
        $query = SaleDetail::query()
            ->leftJoin('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->leftJoin('payments', 'payments.sale_id', '=', 'sales.id');

        if ($saleId = $request->get('sale_id')) {
            $query->where('sale_details.sale_id', $saleId);
        }

        if ($customerId = $request->get('customer_id')) {
            $query->where('payments.customer_id', $customerId);
        }

        if ($productId = $request->get('product_id')) {
            $query->where('sale_details.product_id', $productId);
        }

        if ($categoryId = $request->get('category_id')) {
            $query->where('sale_details.category_id', $categoryId);
        }

        if ($fromDate = $request->get('from_date')) {
            $query->whereDate('sales.date', '>=', $fromDate);
        }

        if ($toDate = $request->get('to_date')) {
            $query->whereDate('sales.date', '<=', $toDate);
        }

        $totalLineAmount = (float) (clone $query)->sum('sale_details.selling_price');

        return response()->json([
            'total_line_amount' => round($totalLineAmount, 2),
        ]);
    }

    public function purchaseLinesSummary(Request $request)
    {
        $query = PurchaseDetail::query()
            ->leftJoin('purchases', 'purchase_details.purchase_id', '=', 'purchases.id')
            ->leftJoin('purchase_payments', 'purchase_payments.purchase_id', '=', 'purchases.id');

        if ($purchaseId = $request->get('purchase_id')) {
            $query->where('purchase_details.purchase_id', $purchaseId);
        }

        if ($supplierId = $request->get('supplier_id')) {
            $query->where('purchase_payments.supplier_id', $supplierId);
        }

        if ($productId = $request->get('product_id')) {
            $query->where('purchase_details.product_id', $productId);
        }

        if ($categoryId = $request->get('category_id')) {
            $query->where('purchase_details.category_id', $categoryId);
        }

        if ($fromDate = $request->get('from_date')) {
            $query->whereDate('purchases.date', '>=', $fromDate);
        }

        if ($toDate = $request->get('to_date')) {
            $query->whereDate('purchases.date', '<=', $toDate);
        }

        $totalLineAmount = (float) (clone $query)->sum('purchase_details.buying_price');

        return response()->json([
            'total_line_amount' => round($totalLineAmount, 2),
        ]);
    }

    public function salesCashMovementReport(SalesCashMovementDataTable $dataTable)
    {
        $sales = Sale::orderBy('sale_no')->get(['id', 'sale_no']);
        $customers = Customer::orderBy('name')->get(['id', 'name']);

        return $dataTable->render('backend.report.sales_cash_movement', compact('sales', 'customers'));
    }

    public function purchaseCashMovementReport(PurchaseCashMovementDataTable $dataTable)
    {
        $purchases = Purchase::orderBy('purchase_no')->get(['id', 'purchase_no']);
        $suppliers = Supplier::orderBy('name')->get(['id', 'name']);

        return $dataTable->render('backend.report.purchase_cash_movement', compact('purchases', 'suppliers'));
    }

    public function salesCreditMovementReport(SalesCreditMovementDataTable $dataTable)
    {
        $sales = Sale::orderBy('sale_no')->get(['id', 'sale_no']);
        $customers = Customer::orderBy('name')->get(['id', 'name']);

        return $dataTable->render('backend.report.sales_credit_movement', compact('sales', 'customers'));
    }

    public function purchaseCreditMovementReport(PurchaseCreditMovementDataTable $dataTable)
    {
        $purchases = Purchase::orderBy('purchase_no')->get(['id', 'purchase_no']);
        $suppliers = Supplier::orderBy('name')->get(['id', 'name']);

        return $dataTable->render('backend.report.purchase_credit_movement', compact('purchases', 'suppliers'));
    }

    public function salesCashMovementSummary(Request $request)
    {
        $query = PaymentDetail::query()
            ->from('payment_details')
            ->join('payments', 'payments.sale_id', '=', 'payment_details.sale_id')
            ->whereIn('payments.paid_status', [PaymentStatus::FULL_PAYMENT, PaymentStatus::PARTIAL_PAYMENT])
            ->where('payment_details.current_paid_amount', '>', 0);

        $this->applySalesCashMovementFilters($query, $request, 'payment_details', 'payments');

        $fullPaymentCash = (float) (clone $query)
            ->where('payments.paid_status', PaymentStatus::FULL_PAYMENT)
            ->sum('payment_details.current_paid_amount');

        $partialPaymentCash = (float) (clone $query)
            ->where('payments.paid_status', PaymentStatus::PARTIAL_PAYMENT)
            ->sum('payment_details.current_paid_amount');

        $saleIds = (clone $query)->distinct()->pluck('payment_details.sale_id')->filter()->values();
        $dueAmountTotal = (float) Payment::query()
            ->whereIn('sale_id', $saleIds)
            ->sum('due_amount');

        return response()->json([
            'full_payment_cash' => round($fullPaymentCash, 2),
            'partial_payment_cash' => round($partialPaymentCash, 2),
            'total_cash' => round($fullPaymentCash + $partialPaymentCash, 2),
            'total_due_amount' => round($dueAmountTotal, 2),
        ]);
    }

    public function purchaseCashMovementSummary(Request $request)
    {
        $query = PurchasePaymentDetail::query()
            ->from('purchase_payment_details')
            ->join('purchase_payments', 'purchase_payments.purchase_id', '=', 'purchase_payment_details.purchase_id')
            ->whereIn('purchase_payments.paid_status', [PaymentStatus::FULL_PAYMENT, PaymentStatus::PARTIAL_PAYMENT])
            ->where('purchase_payment_details.current_paid_amount', '>', 0);

        $this->applyPurchaseCashMovementFilters($query, $request, 'purchase_payment_details', 'purchase_payments');

        $fullPaymentCash = (float) (clone $query)
            ->where('purchase_payments.paid_status', PaymentStatus::FULL_PAYMENT)
            ->sum('purchase_payment_details.current_paid_amount');

        $partialPaymentCash = (float) (clone $query)
            ->where('purchase_payments.paid_status', PaymentStatus::PARTIAL_PAYMENT)
            ->sum('purchase_payment_details.current_paid_amount');

        $purchaseIds = (clone $query)->distinct()->pluck('purchase_payment_details.purchase_id')->filter()->values();
        $dueAmountTotal = (float) PurchasePayment::query()
            ->whereIn('purchase_id', $purchaseIds)
            ->sum('due_amount');

        return response()->json([
            'full_payment_cash' => round($fullPaymentCash, 2),
            'partial_payment_cash' => round($partialPaymentCash, 2),
            'total_cash' => round($fullPaymentCash + $partialPaymentCash, 2),
            'total_due_amount' => round($dueAmountTotal, 2),
        ]);
    }

    public function salesCreditMovementSummary(Request $request)
    {
        $query = PaymentDetail::query()
            ->from('payment_details')
            ->join('payments', 'payments.sale_id', '=', 'payment_details.sale_id')
            ->whereIn('payments.paid_status', [PaymentStatus::FULL_CREDIT, PaymentStatus::PARTIAL_PAYMENT])
            ->where('payments.due_amount', '>', 0);

        $this->applySalesCashMovementFilters(
            $query,
            $request,
            'payment_details',
            'payments',
            [PaymentStatus::FULL_CREDIT, PaymentStatus::PARTIAL_PAYMENT]
        );

        $saleIds = (clone $query)->distinct()->pluck('payment_details.sale_id')->filter()->values();

        $totalCreditAmount = (float) Payment::query()
            ->whereIn('sale_id', $saleIds)
            ->sum('total_amount');

        $partialPaidAmount = (float) (clone $query)
            ->where('payments.paid_status', PaymentStatus::PARTIAL_PAYMENT)
            ->sum('payment_details.current_paid_amount');

        $outstandingDueAmount = (float) Payment::query()
            ->whereIn('sale_id', $saleIds)
            ->sum('due_amount');

        return response()->json([
            'total_credit_amount' => round($totalCreditAmount, 2),
            'partial_paid_amount' => round($partialPaidAmount, 2),
            'due_amount_to_be_paid' => round($outstandingDueAmount, 2),
        ]);
    }

    public function purchaseCreditMovementSummary(Request $request)
    {
        $query = PurchasePaymentDetail::query()
            ->from('purchase_payment_details')
            ->join('purchase_payments', 'purchase_payments.purchase_id', '=', 'purchase_payment_details.purchase_id')
            ->whereIn('purchase_payments.paid_status', [PaymentStatus::FULL_CREDIT, PaymentStatus::PARTIAL_PAYMENT])
            ->where('purchase_payments.due_amount', '>', 0);

        $this->applyPurchaseCashMovementFilters(
            $query,
            $request,
            'purchase_payment_details',
            'purchase_payments',
            [PaymentStatus::FULL_CREDIT, PaymentStatus::PARTIAL_PAYMENT]
        );

        $purchaseIds = (clone $query)->distinct()->pluck('purchase_payment_details.purchase_id')->filter()->values();

        $totalCreditAmount = (float) PurchasePayment::query()
            ->whereIn('purchase_id', $purchaseIds)
            ->sum('total_amount');

        $partialPaidAmount = (float) (clone $query)
            ->where('purchase_payments.paid_status', PaymentStatus::PARTIAL_PAYMENT)
            ->sum('purchase_payment_details.current_paid_amount');

        $outstandingDueAmount = (float) PurchasePayment::query()
            ->whereIn('purchase_id', $purchaseIds)
            ->sum('due_amount');

        return response()->json([
            'total_credit_amount' => round($totalCreditAmount, 2),
            'partial_paid_amount' => round($partialPaidAmount, 2),
            'due_amount_to_be_paid' => round($outstandingDueAmount, 2),
        ]);
    }

    public function summaryLedger()
    {
        $totalPurchases = (float) PurchasePayment::sum('total_amount');
        $totalSales = (float) Payment::sum('total_amount');
        $cashSalesTotal = (float) Payment::where('paid_status', PaymentStatus::FULL_PAYMENT)->sum('total_amount');
        $creditSalesTotal = (float) Payment::where('paid_status', PaymentStatus::FULL_CREDIT)->sum('total_amount');
        $partialSalesTotal = (float) Payment::where('paid_status', PaymentStatus::PARTIAL_PAYMENT)->sum('total_amount');
        $partialSalesPaidPortion = (float) Payment::where('paid_status', PaymentStatus::PARTIAL_PAYMENT)->sum('paid_amount');
        $partialSalesDuePortion = (float) Payment::where('paid_status', PaymentStatus::PARTIAL_PAYMENT)->sum('due_amount');

        $cashCollectedTotal = (float) Payment::sum('paid_amount');
        $cashCollectedFromFullSales = (float) Payment::where('paid_status', PaymentStatus::FULL_PAYMENT)->sum('paid_amount');
        $cashCollectedFromPartialSales = (float) Payment::where('paid_status', PaymentStatus::PARTIAL_PAYMENT)->sum('paid_amount');

        $receivablesTotal = (float) Payment::where('due_amount', '>', 0)->sum('due_amount');
        $receivablesFromCreditSales = (float) Payment::where('paid_status', PaymentStatus::FULL_CREDIT)->sum('due_amount');
        $receivablesFromPartialSales = (float) Payment::where('paid_status', PaymentStatus::PARTIAL_PAYMENT)->sum('due_amount');

        $purchaseCashPaidTotal = (float) PurchasePayment::sum('paid_amount');
        $cashPaidToFullPurchases = (float) PurchasePayment::where('paid_status', PaymentStatus::FULL_PAYMENT)->sum('paid_amount');
        $cashPaidToPartialPurchases = (float) PurchasePayment::where('paid_status', PaymentStatus::PARTIAL_PAYMENT)->sum('paid_amount');

        $payablesTotal = (float) PurchasePayment::where('due_amount', '>', 0)->sum('due_amount');
        $payablesFromCreditPurchases = (float) PurchasePayment::where('paid_status', PaymentStatus::FULL_CREDIT)->sum('due_amount');
        $payablesFromPartialPurchases = (float) PurchasePayment::where('paid_status', PaymentStatus::PARTIAL_PAYMENT)->sum('due_amount');

        $currentStockValue = (float) Product::selectRaw('COALESCE(SUM(quantity * cost_price), 0) as total')->value('total');
        $totalExpenses = (float) ExpenseVoucherItem::sum('amount');

        $salesSub = DB::table('payments')
            ->selectRaw('sale_id, SUM(total_amount) as sales_total')
            ->groupBy('sale_id');

        $costSub = DB::table('sale_details as idt')
            ->selectRaw(
                'idt.sale_id, SUM(idt.selling_qty * COALESCE((
                    SELECT pd.unit_cost_price
                    FROM purchase_details pd
                    WHERE pd.product_id = idt.product_id
                    ORDER BY pd.date DESC, pd.id DESC
                    LIMIT 1
                ), 0)) as cost_total'
            )
            ->groupBy('idt.sale_id');

        $grossSummary = Sale::query()
            ->leftJoinSub($salesSub, 'sales', function ($join) {
                $join->on('sales.sale_id', '=', 'sales.id');
            })
            ->leftJoinSub($costSub, 'costs', function ($join) {
                $join->on('costs.sale_id', '=', 'sales.id');
            })
            ->selectRaw('COALESCE(SUM(sales.sales_total), 0) - COALESCE(SUM(costs.cost_total), 0) as gross_profit')
            ->first();

        $grossProfit = (float) ($grossSummary->gross_profit ?? 0);
        $cashAdjustments = $this->approvedCashAdjustmentTotals();
        $cashShortageLoss = $cashAdjustments['shortage_loss'];
        $cashOverageGain = $cashAdjustments['overage_gain'];
        $cashNetAdjustment = $cashOverageGain - $cashShortageLoss;
        $stockAdjustments = $this->stockAdjustmentTotals();
        $stockShortageLoss = $stockAdjustments['shortage_loss'];
        $stockOverageGain = $stockAdjustments['overage_gain'];
        $stockNetAdjustment = $stockOverageGain - $stockShortageLoss;
        $netProfit = $grossProfit - $totalExpenses - $cashShortageLoss + $cashOverageGain - $stockShortageLoss + $stockOverageGain;

        return view('backend.report.summary_ledger', compact(
            'totalPurchases',
            'currentStockValue',
            'totalExpenses',
            'totalSales',
            'cashSalesTotal',
            'creditSalesTotal',
            'partialSalesTotal',
            'partialSalesPaidPortion',
            'partialSalesDuePortion',
            'cashCollectedTotal',
            'cashCollectedFromFullSales',
            'cashCollectedFromPartialSales',
            'receivablesTotal',
            'receivablesFromCreditSales',
            'receivablesFromPartialSales',
            'purchaseCashPaidTotal',
            'cashPaidToFullPurchases',
            'cashPaidToPartialPurchases',
            'payablesTotal',
            'payablesFromCreditPurchases',
            'payablesFromPartialPurchases',
            'grossProfit',
            'cashShortageLoss',
            'cashOverageGain',
            'cashNetAdjustment',
            'stockShortageLoss',
            'stockOverageGain',
            'stockNetAdjustment',
            'netProfit'
        ));
    }

    private function approvedCashAdjustmentTotals(Request $request = null): array
    {
        $query = CashReconciliation::query()->where('status', 'approved');

        if ($request) {
            if ($fromDate = $request->get('from_date')) {
                $query->whereDate('business_date', '>=', $fromDate);
            }

            if ($toDate = $request->get('to_date')) {
                $query->whereDate('business_date', '<=', $toDate);
            }

            if ($createdBy = $request->get('created_by')) {
                $query->where('user_id', $createdBy);
            }
        }

        $shortageLoss = (float) (clone $query)
            ->where('variance', '<', 0)
            ->sum(DB::raw('ABS(variance)'));

        $overageGain = (float) (clone $query)
            ->where('variance', '>', 0)
            ->sum('variance');

        return [
            'shortage_loss' => round($shortageLoss, 2),
            'overage_gain' => round($overageGain, 2),
        ];
    }

    private function stockAdjustmentTotals(Request $request = null): array
    {
        $query = StockLedger::query()
            ->where('reference_type', 'stock_adjustment')
            ->whereIn('movement_type', ['adjustment_increase', 'adjustment_decrease']);

        if ($request) {
            if ($fromDate = $request->get('from_date')) {
                $query->whereDate('movement_at', '>=', $fromDate);
            }

            if ($toDate = $request->get('to_date')) {
                $query->whereDate('movement_at', '<=', $toDate);
            }

            if ($createdBy = $request->get('created_by')) {
                $query->where('created_by', $createdBy);
            }
        }

        $stockOverageGain = (float) (clone $query)
            ->where('movement_type', 'adjustment_increase')
            ->selectRaw('COALESCE(SUM(qty_in * COALESCE(unit_cost, 0)), 0) as total')
            ->value('total');

        $stockShortageLoss = (float) (clone $query)
            ->where('movement_type', 'adjustment_decrease')
            ->selectRaw('COALESCE(SUM(qty_out * COALESCE(unit_cost, 0)), 0) as total')
            ->value('total');

        return [
            'shortage_loss' => round($stockShortageLoss, 2),
            'overage_gain' => round($stockOverageGain, 2),
        ];
    }

    private function applySalesCashMovementFilters(
        $query,
        Request $request,
        string $detailsTable = 'payments',
        string $paymentsTable = 'payments',
        array $allowedStatuses = [PaymentStatus::FULL_PAYMENT, PaymentStatus::PARTIAL_PAYMENT]
    ): void
    {
        if ($saleId = $request->get('sale_id')) {
            $query->where("{$detailsTable}.sale_id", $saleId);
        }

        if ($customerId = $request->get('customer_id')) {
            $query->where("{$paymentsTable}.customer_id", $customerId);
        }

        if ($status = $request->get('paid_status')) {
            $normalizedStatus = PaymentStatus::normalize($status);

            if (in_array($normalizedStatus, $allowedStatuses, true)) {
                $query->where("{$paymentsTable}.paid_status", $normalizedStatus);
            }
        }

        if ($fromDate = $request->get('from_date')) {
            if ($detailsTable === 'payment_details') {
                $query->whereDate('payment_details.date', '>=', $fromDate);
            } else {
                $query->whereHas('sale', function ($q) use ($fromDate) {
                    $q->whereDate('date', '>=', $fromDate);
                });
            }
        }

        if ($toDate = $request->get('to_date')) {
            if ($detailsTable === 'payment_details') {
                $query->whereDate('payment_details.date', '<=', $toDate);
            } else {
                $query->whereHas('sale', function ($q) use ($toDate) {
                    $q->whereDate('date', '<=', $toDate);
                });
            }
        }
    }

    private function applyPurchaseCashMovementFilters(
        $query,
        Request $request,
        string $detailsTable = 'purchase_payments',
        string $paymentsTable = 'purchase_payments',
        array $allowedStatuses = [PaymentStatus::FULL_PAYMENT, PaymentStatus::PARTIAL_PAYMENT]
    ): void
    {
        if ($purchaseId = $request->get('purchase_id')) {
            $query->where("{$detailsTable}.purchase_id", $purchaseId);
        }

        if ($supplierId = $request->get('supplier_id')) {
            $query->where("{$paymentsTable}.supplier_id", $supplierId);
        }

        if ($status = $request->get('paid_status')) {
            $normalizedStatus = PaymentStatus::normalize($status);

            if (in_array($normalizedStatus, $allowedStatuses, true)) {
                $query->where("{$paymentsTable}.paid_status", $normalizedStatus);
            }
        }

        if ($fromDate = $request->get('from_date')) {
            if ($detailsTable === 'purchase_payment_details') {
                $query->whereDate('purchase_payment_details.date', '>=', $fromDate);
            } else {
                $query->whereHas('purchase', function ($q) use ($fromDate) {
                    $q->whereDate('date', '>=', $fromDate);
                });
            }
        }

        if ($toDate = $request->get('to_date')) {
            if ($detailsTable === 'purchase_payment_details') {
                $query->whereDate('purchase_payment_details.date', '<=', $toDate);
            } else {
                $query->whereHas('purchase', function ($q) use ($toDate) {
                    $q->whereDate('date', '<=', $toDate);
                });
            }
        }
    }

    private function applySalesCreditMovementFilters($query, Request $request): void
    {
        if ($saleId = $request->get('sale_id')) {
            $query->where('payments.sale_id', $saleId);
        }

        if ($customerId = $request->get('customer_id')) {
            $query->where('payments.customer_id', $customerId);
        }

        if ($status = $request->get('paid_status')) {
            $normalizedStatus = PaymentStatus::normalize($status);

            if (in_array($normalizedStatus, [PaymentStatus::FULL_CREDIT, PaymentStatus::PARTIAL_PAYMENT], true)) {
                $query->where('payments.paid_status', $normalizedStatus);
            }
        }

        if ($fromDate = $request->get('from_date')) {
            $query->whereHas('sale', function ($q) use ($fromDate) {
                $q->whereDate('date', '>=', $fromDate);
            });
        }

        if ($toDate = $request->get('to_date')) {
            $query->whereHas('sale', function ($q) use ($toDate) {
                $q->whereDate('date', '<=', $toDate);
            });
        }
    }

    private function applyPurchaseCreditMovementFilters($query, Request $request): void
    {
        if ($purchaseId = $request->get('purchase_id')) {
            $query->where('purchase_payments.purchase_id', $purchaseId);
        }

        if ($supplierId = $request->get('supplier_id')) {
            $query->where('purchase_payments.supplier_id', $supplierId);
        }

        if ($status = $request->get('paid_status')) {
            $normalizedStatus = PaymentStatus::normalize($status);

            if (in_array($normalizedStatus, [PaymentStatus::FULL_CREDIT, PaymentStatus::PARTIAL_PAYMENT], true)) {
                $query->where('purchase_payments.paid_status', $normalizedStatus);
            }
        }

        if ($fromDate = $request->get('from_date')) {
            $query->whereHas('purchase', function ($q) use ($fromDate) {
                $q->whereDate('date', '>=', $fromDate);
            });
        }

        if ($toDate = $request->get('to_date')) {
            $query->whereHas('purchase', function ($q) use ($toDate) {
                $q->whereDate('date', '<=', $toDate);
            });
        }
    }
}

