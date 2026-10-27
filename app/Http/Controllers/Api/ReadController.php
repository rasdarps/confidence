<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\MobileController;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Customer;
use App\Models\ExpenseCategory;
use App\Models\ExpenseVoucher;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\MobileApiData;
use Illuminate\Http\Request;

class ReadController extends MobileController
{
    public function products(Request $request)
    {
        $this->filters($request);
        $query = Product::with(['unit', 'category']);
        if ($request->filled('search')) $query->where('name', 'like', '%'.$request->input('search').'%');
        if ($request->filled('barcode')) $query->where('barcode', $request->input('barcode'));
        if ($request->filled('category_id')) $query->where('category_id', $request->integer('category_id'));
        return $this->paginated($query->orderBy('name')->orderBy('id'), $request, fn ($product) => MobileApiData::product($product, $request->user()));
    }

    public function product(Request $request, int $product)
    {
        return response()->json(['data' => MobileApiData::product(Product::with(['unit', 'category'])->findOrFail($product), $request->user())]);
    }

    public function sales(Request $request)
    {
        $this->filters($request);
        $query = $this->salesQuery($request);
        $total = Payment::whereIn('sale_id', (clone $query)->whereNull('cancelled_at')->select('sales.id'))->sum('total_amount');
        return $this->paginated($query->with(['payment.customer', 'sale_details.product.unit'])->orderByDesc('date')->orderByDesc('id'), $request,
            fn ($sale) => MobileApiData::sale($sale), ['total_sales' => MobileApiData::money($total), 'currency' => 'GHS', 'own_sales_only' => $request->user()->hasRole('Cashier')]);
    }

    public function sale(Request $request, int $sale)
    {
        return response()->json(['data' => MobileApiData::sale($this->salesQuery($request)->findOrFail($sale))]);
    }

    public function credit(Request $request)
    {
        return $this->customerPayments($request, true);
    }

    public function paid(Request $request)
    {
        return $this->customerPayments($request, false);
    }

    private function customerPayments(Request $request, bool $credit)
    {
        $this->filters($request);
        $sales = $this->salesQuery($request)->whereNull('cancelled_at')->whereHas('payment', fn ($payment) => $payment->where('due_amount', $credit ? '>' : '<=', 0));
        $due = Payment::whereIn('sale_id', (clone $sales)->select('sales.id'))->sum('due_amount');
        return $this->paginated($sales->with(['payment.customer', 'sale_details.product.unit'])->orderByDesc('date')->orderByDesc('id'), $request,
            fn ($sale) => MobileApiData::sale($sale), ['current_due' => MobileApiData::money($due), 'currency' => 'GHS']);
    }

    public function customers(Request $request)
    {
        $this->filters($request);
        $query = Customer::query();
        if ($request->filled('search')) $query->where('name', 'like', '%'.$request->input('search').'%');
        $contact = $request->user()->can('customer-list');
        return $this->paginated($query->orderBy('name')->orderBy('id'), $request, fn ($customer) => array_merge(
            ['id' => $customer->id, 'name' => $customer->name],
            $contact ? ['mobile_no' => $customer->mobile_no, 'email' => $customer->email, 'address' => $customer->address] : []
        ));
    }

    public function customer(Request $request, int $customer)
    {
        $record = Customer::findOrFail($customer);
        $payments = Payment::visibleToSalesViewer()->where('customer_id', $customer)->whereHas('sale', fn ($sale) => $sale->whereNull('cancelled_at'));
        return response()->json(['data' => [
            'id' => $record->id, 'name' => $record->name, 'mobile_no' => $record->mobile_no, 'email' => $record->email, 'address' => $record->address,
            'current_balance' => MobileApiData::money($payments->sum('due_amount')), 'currency' => 'GHS',
            'balance_scope' => $request->user()->hasRole('Cashier') ? 'own_sales_only' : 'all_authorized_sales',
        ]]);
    }

    public function suppliers(Request $request)
    {
        $this->filters($request);
        $query = Supplier::query();
        if ($request->filled('search')) $query->where('name', 'like', '%'.$request->input('search').'%');
        return $this->paginated($query->orderBy('name')->orderBy('id'), $request, fn ($supplier) => ['id' => $supplier->id, 'name' => $supplier->name]);
    }

    public function purchases(Request $request)
    {
        $this->filters($request);
        $query = Purchase::query();
        $this->range($query, 'purchases.date', $request);
        if ($request->filled('supplier_id')) $query->whereHas('purchase_payment', fn ($payment) => $payment->where('supplier_id', $request->integer('supplier_id')));
        if ($request->filled('payment_status')) $query->whereHas('purchase_payment', fn ($payment) => $payment->where('paid_status', $request->input('payment_status')));
        return $this->paginated($query->with('purchase_details.product.unit')->orderByDesc('date')->orderByDesc('id'), $request, fn ($purchase) => MobileApiData::purchase($purchase));
    }

    public function purchase(Request $request, int $purchase)
    {
        return response()->json(['data' => MobileApiData::purchase(Purchase::findOrFail($purchase))]);
    }

    public function expenseCategories(Request $request)
    {
        $this->filters($request);
        return $this->paginated(ExpenseCategory::orderBy('name')->orderBy('id'), $request, fn ($category) => ['id' => $category->id, 'name' => $category->name]);
    }

    public function expenses(Request $request)
    {
        $this->filters($request);
        $query = ExpenseVoucher::with('items.category');
        $this->range($query, 'voucher_date', $request);
        if ($request->filled('category_id')) $query->whereHas('items', fn ($item) => $item->where('expense_category_id', $request->integer('category_id')));
        return $this->paginated($query->orderByDesc('voucher_date')->orderByDesc('id'), $request, fn ($voucher) => MobileApiData::expense($voucher));
    }

    public function expense(Request $request, int $expense)
    {
        return response()->json(['data' => MobileApiData::expense(ExpenseVoucher::findOrFail($expense))]);
    }

    public function bankAccounts(Request $request)
    {
        $this->filters($request);
        return $this->paginated(BankAccount::orderBy('name')->orderBy('id'), $request, fn ($account) => [
            'id' => $account->id, 'name' => $account->name, 'bank_name' => $account->bank_name, 'currency' => $account->currency,
            'current_balance' => MobileApiData::money($account->current_balance), 'is_active' => (bool) $account->is_active,
        ], ['balance_source' => 'recorded_application_balance', 'as_of' => now()->toISOString()]);
    }

    public function bankTransactions(Request $request)
    {
        $this->filters($request);
        $query = BankTransaction::with('bankAccount');
        $this->range($query, 'transaction_date', $request);
        if ($request->filled('account_id')) $query->where('bank_account_id', $request->integer('account_id'));
        return $this->paginated($query->orderByDesc('transaction_date')->orderByDesc('id'), $request, fn ($transaction) => [
            'id' => $transaction->id, 'transaction_date' => $transaction->transaction_date->toDateString(),
            'account' => ['id' => $transaction->bank_account_id, 'name' => $transaction->bankAccount?->name, 'currency' => $transaction->bankAccount?->currency],
            'type' => $transaction->type, 'amount' => MobileApiData::money($transaction->amount),
            'reference_no' => $transaction->reference_no, 'description' => $transaction->description,
        ]);
    }
}