<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Backend\SaleController;
use App\Http\Controllers\Backend\PurchaseController;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Payment;
use App\Models\PaymentDetail;
use App\Models\ExpenseVoucher;
use App\Models\ExpenseVoucherItem;
use App\Services\Accounting\AccountingIntegrationService;
use App\Services\MobileApiData;
use App\Services\MobileApiMutation;
use App\Support\PaymentStatus;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    private function saveInvoice(Request $request, string $controller)
    {
        try {
            return app($controller)->store($request);
        } catch (ValidationException $exception) {
            $errors = [];
            foreach ($exception->errors() as $field => $messages) {
                $mapped = preg_replace_callback('/^(product_id|selling_qty|buying_qty|unit_cost_price|unit_selling_price)\.(\d+)$/', function ($matches) {
                    $name = in_array($matches[1], ['selling_qty', 'buying_qty'], true) ? 'quantity' : $matches[1];
                    return 'items.'.$matches[2].'.'.$name;
                }, $field);
                $errors[$mapped] = $messages;
            }
            throw ValidationException::withMessages($errors);
        }
    }

    public function purchase(Request $request, MobileApiMutation $mutations)
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'supplier_id' => ['required', 'integer', Rule::exists('suppliers', 'id')->whereNull('deleted_at')],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => ['required', 'integer', 'distinct', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost_price' => ['required', 'numeric', 'decimal:0,2', 'min:0.01'],
            'items.*.unit_selling_price' => ['required', 'numeric', 'decimal:0,2', 'min:0.01'],
            'paid_status' => ['required', Rule::in(PaymentStatus::canonicalStatuses())],
            'paid_amount' => ['nullable', 'numeric', 'decimal:0,2', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'decimal:0,2', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
            'created_by' => ['prohibited'], 'total_amount' => ['prohibited'], 'estimated_amount' => ['prohibited'],
            'items.*.line_total' => ['prohibited'],
        ]);
        return $mutations->execute($request, $data, function ($data) use ($request) {
            if (config('pos.prevent_backdated_invoices') && $data['date'] < now()->toDateString()) {
                throw ValidationException::withMessages(['date' => 'Backdated purchases are disabled.']);
            }
            $request->replace([
                'date' => $data['date'], 'supplier_id' => $data['supplier_id'],
                'product_id' => array_column($data['items'], 'product_id'), 'buying_qty' => array_column($data['items'], 'quantity'),
                'unit_cost_price' => array_column($data['items'], 'unit_cost_price'),
                'unit_selling_price' => array_column($data['items'], 'unit_selling_price'),
                'paid_status' => $data['paid_status'], 'paid_amount' => $data['paid_amount'] ?? null,
                'discount_amount' => $data['discount_amount'] ?? 0, 'description' => $data['description'] ?? null,
            ]);
            $request->headers->set('X-Requested-With', 'XMLHttpRequest');
            $response = $this->saveInvoice($request, PurchaseController::class);
            abort_unless($response->getStatusCode() === 200, $response->getStatusCode(), 'Purchase could not be created.');
            return MobileApiData::purchase(Purchase::findOrFail($response->getData(true)['purchase_id']));
        });
    }

    public function payment(Request $request, int $sale, MobileApiMutation $mutations)
    {
        Payment::visibleToSalesViewer()->where('sale_id', $sale)->whereHas('sale', fn ($query) => $query->whereNull('cancelled_at'))->firstOrFail();
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01'],
            'payment_date' => ['required', 'date_format:Y-m-d'],
            'reference' => ['nullable', 'string', 'max:120'], 'created_by' => ['prohibited'],
        ]);
        return $mutations->execute($request, $data, function ($data) use ($request, $sale) {
            $payment = Payment::visibleToSalesViewer()->where('sale_id', $sale)->whereHas('sale', fn ($query) => $query->whereNull('cancelled_at'))->lockForUpdate()->firstOrFail();
            if ($payment->due_amount <= 0 || (float) $data['amount'] > round((float) $payment->due_amount, 2)) {
                throw ValidationException::withMessages(['amount' => 'Payment must not exceed the current outstanding balance.']);
            }
            if ($data['payment_date'] < $payment->sale->date->toDateString()) {
                throw ValidationException::withMessages(['payment_date' => 'Payment cannot be dated before the invoice.']);
            }
            if (config('pos.prevent_backdated_invoices') && $data['payment_date'] < now()->toDateString()) {
                throw ValidationException::withMessages(['payment_date' => 'Backdated payments are disabled.']);
            }
            $payment->paid_amount = round((float) $payment->paid_amount + (float) $data['amount'], 2);
            $payment->due_amount = round((float) $payment->total_amount - (float) $payment->paid_amount, 2);
            $payment->paid_status = $payment->due_amount <= 0 ? PaymentStatus::FULL_PAYMENT : PaymentStatus::PARTIAL_PAYMENT;
            $payment->save();
            $detail = PaymentDetail::create([
                'sale_id' => $sale, 'date' => $data['payment_date'], 'current_paid_amount' => $data['amount'],
                'updated_by' => $request->user()->id, 'reference' => $data['reference'] ?? null,
            ]);
            app(AccountingIntegrationService::class)->postCustomerReceipt($payment, $detail, $request->user()->id);
            return ['payment_id' => $detail->id, 'reference' => $detail->reference, 'amount' => MobileApiData::money($detail->current_paid_amount),
                'payment_date' => $data['payment_date'], 'sale' => MobileApiData::sale($payment->sale)];
        });
    }

    public function expense(Request $request, MobileApiMutation $mutations)
    {
        $data = $request->validate([
            'voucher_date' => ['required', 'date_format:Y-m-d'],
            'reference_no' => ['nullable', 'string', 'max:100'], 'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.expense_category_id' => ['required', 'integer', Rule::exists('expense_categories', 'id')->whereNull('deleted_at')],
            'items.*.amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01'],
            'items.*.description' => ['nullable', 'string', 'max:1000'], 'total_amount' => ['prohibited'], 'created_by' => ['prohibited'],
        ]);
        return $mutations->execute($request, $data, function ($data) use ($request) {
            if (!empty($data['reference_no']) && ExpenseVoucher::withTrashed()->where('reference_no', $data['reference_no'])->exists()) {
                throw ValidationException::withMessages(['reference_no' => 'This reference is already used.']);
            }
            $voucher = ExpenseVoucher::create([
                'voucher_date' => $data['voucher_date'], 'reference_no' => $data['reference_no'] ?? null, 'notes' => $data['notes'] ?? null,
                'total_amount' => round(array_sum(array_column($data['items'], 'amount')), 2), 'created_by' => $request->user()->id,
            ]);
            foreach ($data['items'] as $item) {
                ExpenseVoucherItem::create(['expense_voucher_id' => $voucher->id, 'expense_category_id' => $item['expense_category_id'],
                    'amount' => $item['amount'], 'description' => $item['description'] ?? null, 'created_by' => $request->user()->id]);
            }
            app(AccountingIntegrationService::class)->postExpenseVoucher($voucher, $request->user()->id);
            return MobileApiData::expense($voucher);
        });
    }

    public function sale(Request $request, MobileApiMutation $mutations)
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->whereNull('deleted_at')],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => ['required', 'integer', 'distinct', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'items.*.quantity' => ['required', 'numeric', 'decimal:0,4', 'min:0.01'],
            'paid_status' => ['required', Rule::in(PaymentStatus::canonicalStatuses())],
            'paid_amount' => ['nullable', 'numeric', 'decimal:0,2', 'min:0'],
            'cash_tendered' => ['nullable', 'numeric', 'decimal:0,2', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'decimal:0,2', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
            'created_by' => ['prohibited'], 'total_amount' => ['prohibited'],
            'items.*.unit_selling_price' => ['prohibited'], 'items.*.line_total' => ['prohibited'],
        ]);
        return $mutations->execute($request, $data, function ($data) use ($request) {
            $request->replace([
                'date' => $data['date'], 'customer_id' => $data['customer_id'],
                'product_id' => array_column($data['items'], 'product_id'), 'selling_qty' => array_column($data['items'], 'quantity'),
                'paid_status' => $data['paid_status'], 'paid_amount' => $data['paid_amount'] ?? null,
                'cash_tendered' => $data['cash_tendered'] ?? null, 'discount_amount' => $data['discount_amount'] ?? 0,
                'description' => $data['description'] ?? null,
            ]);
            $request->headers->set('X-Requested-With', 'XMLHttpRequest');
            $response = $this->saveInvoice($request, SaleController::class);
            abort_unless($response->getStatusCode() === 200, $response->getStatusCode(), 'Sale could not be created.');
            return MobileApiData::sale(Sale::findOrFail($response->getData(true)['sale_id']));
        });
    }
}