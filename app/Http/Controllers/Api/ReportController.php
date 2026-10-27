<?php

namespace App\Http\Controllers\Api;

use App\Models\BankAccount;
use App\Models\ExpenseVoucherItem;
use App\Models\Payment;
use App\Models\PaymentDetail;
use App\Models\Product;
use App\Models\PurchasePayment;
use App\Models\Sale;
use App\Services\MobileApiData;
use Illuminate\Http\Request;

class ReportController extends MobileController
{
    private function invoiceMetrics(Request $request): array
    {
        $sales = $this->salesQuery($request)->whereNull('cancelled_at');
        $payments = Payment::whereIn('sale_id', (clone $sales)->select('sales.id'));
        return [
            'invoice_count' => (clone $sales)->count(), 'invoice_total' => MobileApiData::money((clone $payments)->sum('total_amount')),
            'paid_on_invoices_to_date' => MobileApiData::money((clone $payments)->sum('paid_amount')),
            'current_due_on_invoices' => MobileApiData::money((clone $payments)->sum('due_amount')),
            'currency' => 'GHS', 'date_basis' => 'invoice_date', 'due_is_current' => true,
            'from_date' => $request->input('from_date'), 'to_date' => $request->input('to_date'),
        ];
    }

    private function collectionQuery(Request $request)
    {
        $sales = Sale::query()->whereNull('cancelled_at');
        if ($request->user()->hasRole('Cashier')) $sales->where('created_by', $request->user()->id);
        if ($request->filled('customer_id')) $sales->whereHas('payment', fn ($payment) => $payment->where('customer_id', $request->integer('customer_id')));
        if ($request->filled('sale_id')) $sales->whereKey($request->integer('sale_id'));
        $query = PaymentDetail::query()->whereIn('sale_id', $sales->select('sales.id'));
        $this->range($query, 'payment_details.date', $request);
        return $query;
    }

    private function expenseQuery(Request $request)
    {
        $query = ExpenseVoucherItem::query()->whereHas('voucher', function ($voucher) use ($request) {
            $this->range($voucher, 'voucher_date', $request);
        });
        if ($request->filled('category_id')) $query->where('expense_category_id', $request->integer('category_id'));
        return $query;
    }

    private function quantityTotals($query): array
    {
        return (clone $query)->leftJoin('units', 'units.id', '=', 'products.unit_id')
            ->selectRaw('products.unit_id, units.name as unit_name, SUM(products.quantity) as quantity')
            ->groupBy('products.unit_id', 'units.name')->orderBy('products.unit_id')->get()
            ->map(fn ($row) => ['unit_id' => $row->unit_id, 'unit_name' => $row->unit_name, 'quantity' => (float) $row->quantity])->all();
    }

    public function sales(Request $request)
    {
        $this->filters($request);
        return response()->json(['data' => $this->invoiceMetrics($request)]);
    }

    public function collections(Request $request)
    {
        $this->filters($request);
        $query = $this->collectionQuery($request);
        $total = (clone $query)->sum('current_paid_amount');
        return $this->paginated($query->with('sale')->orderByDesc('date')->orderByDesc('id'), $request, fn ($receipt) => [
            'id' => $receipt->id, 'sale_id' => $receipt->sale_id, 'sale_no' => $receipt->sale?->sale_no,
            'payment_date' => $receipt->date->toDateString(), 'amount' => MobileApiData::money($receipt->current_paid_amount),
            'reference' => $receipt->reference,
        ], ['money_collected' => MobileApiData::money($total), 'currency' => 'GHS', 'date_basis' => 'payment_date']);
    }

    public function stock(Request $request)
    {
        return $this->stockReport($request, false);
    }

    public function lowStock(Request $request)
    {
        return $this->stockReport($request, true);
    }

    private function stockReport(Request $request, bool $low)
    {
        $this->filters($request);
        $request->validate(['from_date' => ['prohibited'], 'to_date' => ['prohibited']]);
        $query = Product::with(['unit', 'category']);
        if ($low) $query->whereColumn('quantity', '<=', 'stock_level');
        if ($request->filled('search')) $query->where('name', 'like', '%'.$request->input('search').'%');
        if ($request->filled('category_id')) $query->where('category_id', $request->integer('category_id'));
        $extra = ['as_of' => now()->toISOString(), 'historical_snapshot' => false, 'quantity_totals' => $this->quantityTotals($query)];
        if ($request->user()->can('stock-value-report')) {
            $extra['stock_value_at_cost'] = MobileApiData::money((clone $query)->selectRaw('COALESCE(SUM(quantity * cost_price), 0) as value')->value('value'));
            $extra['stock_value_at_selling_price'] = MobileApiData::money((clone $query)->selectRaw('COALESCE(SUM(quantity * selling_price), 0) as value')->value('value'));
            $extra['currency'] = 'GHS';
        }
        return $this->paginated($query->orderBy('name')->orderBy('id'), $request, fn ($product) => MobileApiData::product($product, $request->user()), $extra);
    }

    public function expenses(Request $request)
    {
        $this->filters($request);
        $query = $this->expenseQuery($request);
        $amount = (clone $query)->sum('amount');
        $byCategory = (clone $query)->leftJoin('expense_categories', 'expense_categories.id', '=', 'expense_voucher_items.expense_category_id')
            ->selectRaw('expense_voucher_items.expense_category_id as category_id, expense_categories.name as category_name, SUM(expense_voucher_items.amount) as amount')
            ->groupBy('expense_voucher_items.expense_category_id', 'expense_categories.name')->orderBy('expense_voucher_items.expense_category_id')->get()
            ->map(fn ($row) => ['category_id' => $row->category_id, 'category_name' => $row->category_name, 'amount' => MobileApiData::money($row->amount)])->all();
        return response()->json(['data' => [
            'expense_total' => MobileApiData::money($amount), 'currency' => 'GHS', 'by_category' => $byCategory,
            'date_basis' => 'voucher_date', 'from_date' => $request->input('from_date'), 'to_date' => $request->input('to_date'),
        ]]);
    }

    public function dashboard(Request $request)
    {
        $this->filters($request);
        if (!$request->filled('from_date')) $request->merge(['from_date' => now()->toDateString()]);
        if (!$request->filled('to_date')) $request->merge(['to_date' => now()->toDateString()]);
        $this->filters($request);
        $user = $request->user();
        $cards = [];
        if ($user->can('sale-list') || $user->can('report-sales-lines')) $cards['sales'] = $this->invoiceMetrics($request);
        if ($user->can('report-sales-cash-movement')) $cards['collections'] = ['amount' => MobileApiData::money($this->collectionQuery($request)->sum('current_paid_amount')), 'currency' => 'GHS'];
        if ($user->can('purchase-list')) {
            $query = PurchasePayment::query()->whereHas('purchase', function ($purchase) use ($request) {
                $purchase->whereNull('cancelled_at');
                $this->range($purchase, 'date', $request);
            });
            $cards['purchases'] = ['amount' => MobileApiData::money($query->sum('total_amount')), 'currency' => 'GHS'];
        }
        if ($user->can('expense-list') || $user->can('report-expense')) $cards['expenses'] = ['amount' => MobileApiData::money($this->expenseQuery($request)->sum('amount')), 'currency' => 'GHS'];
        if ($user->can('stock-report') || $user->can('stock-value-report')) {
            $cards['stock'] = ['product_count' => Product::count(), 'low_stock_count' => Product::whereColumn('quantity', '<=', 'stock_level')->count(),
                'quantity_totals' => $this->quantityTotals(Product::query()), 'historical_snapshot' => false];
        }
        if ($user->can('bank-account-list')) {
            $cards['bank_balances'] = BankAccount::selectRaw('currency, SUM(current_balance) as amount')->groupBy('currency')->orderBy('currency')->get()
                ->map(fn ($row) => ['currency' => $row->currency, 'amount' => MobileApiData::money($row->amount), 'is_live_balance' => false])->all();
        }
        return response()->json(['data' => [
            'from_date' => $request->input('from_date'), 'to_date' => $request->input('to_date'), 'cards' => $cards,
            'own_sales_only' => $user->hasRole('Cashier'), 'timezone' => config('app.timezone'),
            'settings' => [
                'allow_negative_stock' => (bool) config('pos.allow_negative_stock'),
                'prevent_backdated_invoices' => (bool) config('pos.prevent_backdated_invoices'),
                'allow_manual_discount_entry_by_staff' => (bool) config('pos.allow_manual_discount_entry_by_staff'),
                'enforce_previous_day_reconciliation' => (bool) config('pos.enforce_previous_day_reconciliation'),
            ],
        ]]);
    }
}