<?php

namespace App\Http\Controllers\Backend;

use App\DataTables\Admin\Report\Sales\ReceiptAuditDataTable;
use App\DataTables\Admin\Report\Sales\AllSalesDataTable;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\SaleReceiptEvent;
use App\Models\Payment;
use App\Models\PaymentDetail;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use App\Models\CashReconciliation;
use App\Services\Accounting\AccountingIntegrationService;
use App\Support\PaymentStatus;
use App\Services\StockLedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    function __construct()
    {
    $this->middleware('permission:sale-list', ['only' => ['index']]);
    $this->middleware('permission:sale-create', ['only' => ['create', 'store']]);
    $this->middleware('permission:sale-cancel', ['only' => ['cancel']]);
    $this->middleware('permission:sale-print', ['only' => ['printSaleList']]);
    $this->middleware('permission:sale-print', ['only' => ['printSale']]);
    $this->middleware('permission:sale-receipt-reprint', ['only' => ['reprintSale']]);
    $this->middleware('permission:sale-receipt-audit-view', ['only' => ['receiptAudit', 'exportReceiptAudit']]);
    $this->middleware('permission:sale-delivery-verify', ['only' => ['deliveryVerificationPage', 'verifyDelivery']]);
    $this->middleware('permission:sale-list', ['only' => ['dailyTotalSales']]);
    }

     public function index(AllSalesDataTable $dataTable){
        // $allData = Sale::orderBy('date','desc')->orderBy('id','desc')->get();
          $customers = Customer::orderBy('name')->get();
          $sales = Sale::orderBy('sale_no')->get();
          $users = User::orderBy('name')->get();
                    $isRestrictedToOwnSales = auth()->check() && auth()->user()->hasRole('Cashier');
                    return $dataTable->render('backend.sale.index', compact('customers', 'sales', 'users', 'isRestrictedToOwnSales'));

    }

    public function create(){ 

        if ($gate = $this->previousDayReconciliationGate()) {
            return redirect()->route('cash-reconciliations.create')
                ->with('error', $gate['message'])
                ->with('info', $gate['action']);
        }

        $unit = Unit::all();
        $category = Category::all();
        $products = Product::with(['category', 'unit'])->orderBy('name')->get();
        $costomer = Customer::all();

        // Get the maximum numeric sale_no
        $maxInvoiceNo = Sale::max(DB::raw('CAST(sale_no AS UNSIGNED)'));
        if ($maxInvoiceNo) {
            $sale_no = str_pad($maxInvoiceNo + 1, 2, '0', STR_PAD_LEFT);
        } else {
            $sale_no = '01';
        }

        $date = Carbon::now()->format('Y-m-d');        
        $allowNegativeStock = (bool) config('pos.allow_negative_stock', false);
        $preventBackdatedInvoices = (bool) config('pos.prevent_backdated_invoices', false);
        $allowManualDiscountEntryByStaff = (bool) config('pos.allow_manual_discount_entry_by_staff', true);

        return view('backend.sale.create',compact(
            'sale_no',
            'category',
            'date',
            'costomer',
            'products',
            'unit',
            'allowNegativeStock',
            'preventBackdatedInvoices',
            'allowManualDiscountEntryByStaff'
        ));

    } // End Method



    public function store(Request $request)
    {
        if ($gate = $this->previousDayReconciliationGate()) {
            throw ValidationException::withMessages([
                'reconciliation' => $gate['message'] . ' ' . $gate['action'],
            ]);
        }

        $allowNegativeStock = (bool) config('pos.allow_negative_stock', false);
        $preventBackdatedInvoices = (bool) config('pos.prevent_backdated_invoices', false);
        $allowManualDiscountEntryByStaff = (bool) config('pos.allow_manual_discount_entry_by_staff', true);
        $requestedStatus = PaymentStatus::normalize((string) $request->paid_status);

        // Validation rules
        $rules = [
            'date' => ['required', 'date'],
            'product_id' => ['required', 'array', 'min:1'],
            'product_id.*' => ['required', 'exists:products,id'],
            'selling_qty' => ['required', 'array', 'min:1'],
            'selling_qty.*' => ['required', 'numeric', 'min:0.01'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'paid_status' => [
                'required',
                Rule::notIn(['']),
                Rule::in(PaymentStatus::canonicalStatuses()),
            ],
            'customer_id' => ['required'],
        ];

        if ($request->customer_id == '0') {
            $rules['name'] = ['required', 'string', 'max:255'];
            $rules['mobile_no'] = ['required', 'string', 'max:20'];
            $rules['email'] = ['required', 'email', 'max:255'];
        }

        $request->validate($rules);

        if ($requestedStatus === null) {
            throw ValidationException::withMessages([
                'paid_status' => 'Invalid payment status selected.',
            ]);
        }

        if (count($request->product_id) !== count($request->selling_qty)) {
            throw ValidationException::withMessages(['selling_qty' => 'Each selected product must have exactly one quantity.']);
        }

        $products = Product::query()->whereIn('id', $request->product_id)->get()->keyBy('id');
        $unitSellingPrices = [];
        $lineTotals = [];
        foreach ($request->product_id as $index => $productId) {
            $product = $products->get($productId);
            if (!$product) {
                throw ValidationException::withMessages(['product_id.'.$index => 'Selected product is unavailable.']);
            }
            $unitPrice = round((float) $product->selling_price, 2);
            if ($unitPrice <= 0) {
                throw ValidationException::withMessages(['product_id.'.$index => 'Selected product does not have a valid selling price.']);
            }
            $unitSellingPrices[] = $unitPrice;
            $lineTotals[] = round((float) $request->selling_qty[$index] * $unitPrice, 2);
        }

        $request->merge([
            'unit_selling_price' => $unitSellingPrices,
            'selling_price' => $lineTotals,
        ]);

        $grossTotal = round(array_sum($lineTotals), 2);
        $discountAmount = $allowManualDiscountEntryByStaff
            ? round((float) ($request->input('discount_amount') ?? 0), 2)
            : 0.0;
        if ($discountAmount >= $grossTotal && $discountAmount > 0) {
            throw ValidationException::withMessages(['discount_amount' => 'Discount must be less than the server-calculated sale total.']);
        }
        $netTotal = round($grossTotal - $discountAmount, 2);

        // Tax-inclusive receipt model: total = taxable amount * 1.20.
        $taxableAmount = $netTotal / 1.20;
        $calculated = [
            'discount_amount' => $discountAmount,
            'total_amount' => $netTotal,
            'taxable_amount' => round($taxableAmount, 2),
            'nhil_amount' => round($taxableAmount * 0.025, 2),
            'getfund_amount' => round($taxableAmount * 0.025, 2),
            'vat_amount' => round($taxableAmount * 0.15, 2),
            'cash_tendered' => round(max(0, (float) ($request->input('cash_tendered') ?? 0)), 2),
            'change_due' => 0,
        ];

        $paymentRules = [];
        if ($requestedStatus === PaymentStatus::PARTIAL_PAYMENT) {
            $paymentRules['paid_amount'] = ['required', 'numeric', 'min:0.01', 'max:'.max(0, $netTotal - 0.01)];
            $paymentRules['cash_tendered'] = ['nullable', 'numeric', 'min:0'];
        } elseif ($requestedStatus === PaymentStatus::FULL_PAYMENT) {
            $paymentRules['cash_tendered'] = ['required', 'numeric', 'min:'.$netTotal];
        } else {
            $paymentRules['cash_tendered'] = ['nullable', 'numeric', 'min:0', 'max:0'];
        }
        $request->validate($paymentRules);

        $cashCustomerId = (int) (Customer::whereRaw('LOWER(name) = ?', ['cash_sales'])->value('id') ?? 0);

        if ($request->customer_id == '0') {
            $customer_id = 0;
        } else {
            $customer_id = (int) $request->customer_id;
        }

        if ($customer_id <= 0 && $request->customer_id != '0') {
            if ($requestedStatus === PaymentStatus::FULL_PAYMENT && $cashCustomerId > 0) {
                $customer_id = $cashCustomerId;
            } else {
                throw ValidationException::withMessages([
                    'customer_id' => 'Please select a valid customer account.',
                ]);
            }
        }

        if ($customer_id > 0 && !Customer::whereKey($customer_id)->exists()) {
            throw ValidationException::withMessages([
                'customer_id' => 'Selected customer account was not found.',
            ]);
        }

        if (
            $request->customer_id != '0'
            && $cashCustomerId > 0
            && $customer_id === $cashCustomerId
            && PaymentStatus::isCredit($requestedStatus)
        ) {
            throw ValidationException::withMessages([
                'customer_id' => 'Credit and partial sales cannot be posted to cash_sales account. Select a customer or create a new one.',
            ]);
        }

        $paidAmount = 0;
        $dueAmount = 0;
        $currentPaidAmount = 0;

        if ($requestedStatus === PaymentStatus::FULL_PAYMENT) {
            $paidAmount = $calculated['total_amount'];
            $dueAmount = 0;
            $currentPaidAmount = $calculated['total_amount'];
            $calculated['change_due'] = round(max(0, $calculated['cash_tendered'] - $calculated['total_amount']), 2);
        } elseif ($requestedStatus === PaymentStatus::FULL_CREDIT) {
            $paidAmount = 0;
            $dueAmount = $calculated['total_amount'];
            $currentPaidAmount = 0;
            $calculated['change_due'] = 0;
        } else {
            $paidAmount = round((float) $request->paid_amount, 2);
            $dueAmount = round(max(0, $calculated['total_amount'] - $paidAmount), 2);
            $currentPaidAmount = $paidAmount;
            $calculated['cash_tendered'] = $paidAmount;
            $calculated['change_due'] = 0;
        }

        if ($preventBackdatedInvoices && Carbon::parse($request->date)->isBefore(Carbon::today())) {
            throw ValidationException::withMessages([
                'date' => 'Backdated invoices are disabled. Please use today\'s date.',
            ]);
        }

        $Sale = null;
        $attempts = 0;
        $maxAttempts = 3;
        $saleNoClashDetected = false;

        while ($attempts < $maxAttempts) {
            $attempts++;
            try {
                $Sale = DB::transaction(function() use($request, $calculated, $allowNegativeStock, $requestedStatus, $paidAmount, $dueAmount, $currentPaidAmount, $customer_id) {

                    $maxInvoiceNo = (int) DB::table('sales')
                        ->lockForUpdate()
                        ->selectRaw('COALESCE(MAX(CAST(sale_no AS UNSIGNED)), 0) as max_no')
                        ->value('max_no');
                    $nextNo = str_pad($maxInvoiceNo + 1, 2, '0', STR_PAD_LEFT);

                    $Sale = Sale::create([
                        'sale_no' => $nextNo,
                        'date' => $request->date,
                        'description' => $request->description,
                        'created_by' => Auth::id(),
                    ]);

                    $countProducts = count($request->product_id);
                    for ($i = 0; $i < $countProducts; $i++) {
                        $product = Product::whereKey($request->product_id[$i])->lockForUpdate()->firstOrFail();

                        if (round((float) $product->selling_price, 2) !== round((float) $request->unit_selling_price[$i], 2)) {
                            throw ValidationException::withMessages([
                                'product_id.'.$i => 'Product price changed while creating the sale. Please refresh and try again.',
                            ]);
                        }

                        $categoryId = $product->category_id;
                        $unitId = $product->unit_id;

                        if (!$categoryId || !$unitId) {
                            throw ValidationException::withMessages([
                                'product_id.' . $i => 'Selected product is missing category or unit setup.',
                            ]);
                        }

                        $sale_details = new SaleDetail();
                        $sale_details->date = date('Y-m-d', strtotime($request->date));
                        $sale_details->sale_id = $Sale->id;
                        $sale_details->category_id = $categoryId;
                        $sale_details->product_id = $request->product_id[$i];
                        $sale_details->selling_qty = $request->selling_qty[$i];
                        $sale_details->unit_selling_price = $request->unit_selling_price[$i];
                        $sale_details->selling_price = $request->selling_price[$i];
                        $sale_details->unit_id = $unitId;
                        $sale_details->save();

                        // Product row already locked at loop start.
                        $beforeQty = (float) $product->quantity;
                        $lineQty = (float) $sale_details->selling_qty;

                        if ($lineQty <= 0) {
                            throw \Illuminate\Validation\ValidationException::withMessages([
                                'selling_qty.' . $i => 'Quantity for item #' . ($i + 1) . ' must be greater than zero.',
                            ]);
                        }

                        if (! $allowNegativeStock && $beforeQty < $lineQty) {
                            throw \Illuminate\Validation\ValidationException::withMessages([
                                'selling_qty.' . $i => 'One of the selected items has negative stock so remove from invoice',
                            ]);
                        }

                        $afterQty = $beforeQty - $lineQty;
                        $product->quantity = $afterQty;
                        $product->save();

                        app(StockLedgerService::class)->record([
                            'product_id' => $product->id,
                            'movement_type' => 'sale',
                            'reference_type' => 'Sale',
                            'reference_id' => $Sale->id,
                            'reference_detail_id' => $sale_details->id,
                            'movement_at' => $Sale->date,
                            'qty_in' => 0,
                            'qty_out' => $lineQty,
                            'balance_before' => $beforeQty,
                            'balance_after' => $afterQty,
                            'unit_price' => $sale_details->unit_selling_price,
                            'reason' => 'Sale stock-out',
                            'notes' => $Sale->description,
                            'created_by' => Auth::id(),
                        ]);
                    }

                    if ($request->customer_id == '0') {
                        $customer = Customer::create([
                            'name' => $request->name,
                            'mobile_no' => $request->mobile_no,
                            'email' => $request->email,
                        ]);
                        $customer_id = $customer->id;
                    }

                    $payment = new Payment();
                    $payment_details = new PaymentDetail();

                    $payment->sale_id = $Sale->id;
                    $payment->customer_id = $customer_id;
                    $payment->paid_status = $requestedStatus;
                    $payment->discount_amount = $calculated['discount_amount'];
                    $payment->total_amount = $calculated['total_amount'];
                    $payment->taxable_amount = $calculated['taxable_amount'];
                    $payment->nhil_amount = $calculated['nhil_amount'];
                    $payment->getfund_amount = $calculated['getfund_amount'];
                    $payment->vat_amount = $calculated['vat_amount'];
                    $payment->cash_tendered = $calculated['cash_tendered'];
                    $payment->change_due = $calculated['change_due'];

                    $payment->paid_amount = $paidAmount;
                    $payment->due_amount = $dueAmount;
                    $payment_details->current_paid_amount = $currentPaidAmount;
                    $payment->save();

                    $payment_details->sale_id = $Sale->id;
                    $payment_details->date = date('Y-m-d', strtotime($request->date));
                    $payment_details->save();

                    app(AccountingIntegrationService::class)->postSaleFromPayment($Sale, $payment, Auth::id());

                    return $Sale;
                });

                break;
            } catch (QueryException $e) {
                $isDuplicateInvoiceNo = (string) ($e->getCode()) === '23000'
                    && str_contains(strtolower($e->getMessage()), 'sale_no');

                if ($isDuplicateInvoiceNo) {
                    if ($attempts < $maxAttempts) {
                        continue;
                    }

                    $saleNoClashDetected = true;
                    break;
                }

                throw $e;
            }
        }

        if (!$Sale && $saleNoClashDetected) {
            $maxInvoiceNo = (int) DB::table('sales')
                ->selectRaw('COALESCE(MAX(CAST(sale_no AS UNSIGNED)), 0) as max_no')
                ->value('max_no');
            $nextNo = str_pad($maxInvoiceNo + 1, 2, '0', STR_PAD_LEFT);

            $message = 'Sale number clash detected. Another checkout used the same Sale number.';
            $action = 'Action: click Save Sale again to continue with Sale #' . $nextNo . '.';

            if ($request->ajax()) {
                return response()->json([
                    'status' => 409,
                    'error_code' => 'SALE_NO_CLASH',
                    'message' => $message,
                    'action' => $action,
                    'next_no' => $nextNo,
                ], 409);
            }

            return redirect()->back()
                ->withInput()
                ->withErrors(['sale_no' => $message . ' ' . $action]);
        }

        // Now $Sale is available here
        if ($request->ajax()) {
            // Calculate next number after commit
            $maxInvoiceNo = (int) DB::table('sales')
                ->selectRaw('COALESCE(MAX(CAST(sale_no AS UNSIGNED)), 0) as max_no')
                ->value('max_no');
            $nextNo = str_pad($maxInvoiceNo + 1, 2, '0', STR_PAD_LEFT);

            return response()->json([
                'status' => 200,
                'message' => 'Sale Data Inserted Successfully',
                'sale_id' => $Sale->id,
                'print_url' => route('print.sale', Crypt::encrypt($Sale->id)),
                'next_no' => $nextNo, // send next Sale number to frontend
            ]);
        } else {
            return redirect()->route('print.sale', Crypt::encrypt($Sale->id));
        }
    }


    public function cancel(Request $request, $encryptedId)
    {
        try {
            $id = Crypt::decrypt($encryptedId);
            $Sale = Sale::with('sale_details')->findOrFail($id);

            if ($Sale->cancelled_at) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Sale #' . $Sale->sale_no . ' is already cancelled.',
                ], 422);
            }

            $validated = $request->validate([
                'reason' => 'required|string|max:500',
            ], [
                'reason.required' => 'Please provide a reason for cancellation.',
            ]);

            DB::transaction(function () use ($Sale, $validated) {
                $paymentSnapshot = Payment::query()->where('sale_id', $Sale->id)->first();

                if ($paymentSnapshot) {
                    app(AccountingIntegrationService::class)->reverseSale($Sale, $paymentSnapshot, $validated['reason'], Auth::id());
                }

                // Restore stock for every line item
                foreach ($Sale->sale_details as $detail) {
                    $product = Product::whereKey($detail->product_id)->lockForUpdate()->first();
                    if ($product) {
                        $beforeQty = (float) $product->quantity;
                        $lineQty = (float) $detail->selling_qty;
                        $afterQty = $beforeQty + $lineQty;
                        $product->quantity = $afterQty;
                        $product->save();

                        //StockLedgerService record to log stock movement
                        app(StockLedgerService::class)->record([
                            'product_id' => $product->id,
                            'movement_type' => 'sale_cancel',
                            'reference_type' => 'Sale',
                            'reference_id' => $Sale->id,
                            'reference_detail_id' => $detail->id,
                            'movement_at' => now(),
                            'qty_in' => $lineQty,
                            'qty_out' => 0,
                            'balance_before' => $beforeQty,
                            'balance_after' => $afterQty,
                            'unit_price' => $detail->unit_selling_price,
                            'reason' => 'Sale cancellation',
                            'notes' => $validated['reason'],
                            'created_by' => Auth::id(),
                        ]);
                    }
                }

                // Zero-out line values so cancelled Sale does not affect quantity/value reporting
                SaleDetail::where('sale_id', $Sale->id)->update([
                    'selling_qty' => 0,
                    'unit_selling_price' => 0,
                    'selling_price' => 0,
                ]);

                // Zero-out payment values so cancelled Sale does not affect financial totals
                Payment::where('sale_id', $Sale->id)->update([
                    'paid_status' => PaymentStatus::FULL_PAYMENT,
                    'discount_amount' => 0,
                    'total_amount' => 0,
                    'taxable_amount' => 0,
                    'nhil_amount' => 0,
                    'getfund_amount' => 0,
                    'vat_amount' => 0,
                    'cash_tendered' => 0,
                    'change_due' => 0,
                    'paid_amount' => 0,
                    'due_amount' => 0,
                ]);

                // Zero-out payment detail entries tied to this Sale
                PaymentDetail::where('sale_id', $Sale->id)->update([
                    'current_paid_amount' => 0,
                ]);

                // Stamp cancellation on the Sale
                $Sale->update([
                    'cancelled_at'        => now(),
                    'cancelled_by'        => Auth::id(),
                    'cancellation_reason' => $validated['reason'],
                ]);
            });

            return response()->json([
                'status'  => 200,
                'message' => 'Sale #' . $Sale->sale_no . ' has been cancelled and stock has been restored.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => 422,
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 400,
                'message' => 'Failed to cancel Sale.',
            ], 400);
        }
    } // End Method

    private function previousDayReconciliationGate(): ?array
    {
        if (! config('pos.enforce_previous_day_reconciliation', false)) {
            return null;
        }

        $userId = (int) Auth::id();
        if ($userId <= 0) {
            return null;
        }

        $previousDate = Carbon::yesterday()->toDateString();

        // Gate only applies if the cashier actually sold yesterday.
        $hadSalesYesterday = Sale::query()
            ->whereDate('date', $previousDate)
            ->where('created_by', $userId)
            ->exists();

        if (! $hadSalesYesterday) {
            return null;
        }

        $hasReconciliation = CashReconciliation::query()
            ->whereDate('business_date', $previousDate)
            ->where('user_id', $userId)
            ->whereIn('status', ['submitted', 'approved'])
            ->exists();

        if ($hasReconciliation) {
            return null;
        }

        return [
            'message' => 'Yesterday cash reconciliation is missing for your account.',
            'action' => 'Action: submit yesterday reconciliation before creating today\'s Sale.',
        ];
    }

    public function printSaleList(){

       $allData = Sale::orderBy('date','desc')->orderBy('id','desc')->get();
         return view('backend.sale.print_sale_list',compact('allData'));
    } // End Method


    //detail Sale print
    public function printSale($encryptedId){

        $id = Crypt::decrypt($encryptedId);
        $request = request();
        $Sale = DB::transaction(function () use ($id, $request) {
            $sale = Sale::query()->lockForUpdate()->findOrFail($id);

            if ($sale->printed_at) {
                abort(409, 'The original receipt has already been authorized. Use the manager reprint action.');
            }

            $sale->printed_at = now();
            $sale->printed_by = Auth::id();
            $sale->save();
            $this->recordReceiptEvent($sale, 'original', null, $request);

            return $sale->load(['sale_details.category', 'sale_details.product', 'createdBy']);
        });
        $companyInfo = Company::first();
        return view('backend.sale.sale_receipt', compact('Sale', 'companyInfo') + ['printMode' => 'original']);

    } // End Method

    public function reprintSale(Request $request, $encryptedId)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);
        $id = Crypt::decrypt($encryptedId);

        $Sale = DB::transaction(function () use ($id, $validated, $request) {
            $sale = Sale::query()->lockForUpdate()->findOrFail($id);

            if ($sale->cancelled_at) {
                abort(409, 'Cancelled sales cannot be reprinted.');
            }

            if (! $sale->printed_at) {
                abort(409, 'An original receipt must be recorded before a manager reprint.');
            }

            $this->recordReceiptEvent($sale, 'reprint', trim($validated['reason']), $request);

            return $sale->load(['sale_details.category', 'sale_details.product', 'createdBy']);
        });

        $companyInfo = Company::first();
        return view('backend.sale.sale_receipt', compact('Sale', 'companyInfo') + ['printMode' => 'reprint']);
    }

    public function deliveryVerificationPage()
    {
        return view('backend.sale.delivery_verify');
    }

    public function verifyDelivery(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:100'],
        ]);
        $code = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $validated['code']));

        if (! preg_match('/^(?:D[A-Z0-9]{11}|D[A-F0-9]{32})$/', $code)) {
            return response()->json(['status' => 'invalid', 'message' => 'The delivery code is not valid.'], 422);
        }

        $result = DB::transaction(function () use ($code, $request) {
            $sale = Sale::query()->where('delivery_code', $code)->lockForUpdate()->first();

            if (! $sale) {
                return ['status' => 'missing', 'message' => 'No sale matches this delivery code.', 'http' => 404];
            }

            if ($sale->cancelled_at) {
                return ['status' => 'cancelled', 'message' => 'This sale is cancelled and cannot be delivered.', 'http' => 409];
            }

            if ($sale->delivered_at) {
                return ['status' => 'duplicate', 'message' => 'This sale was already delivered at ' . $sale->delivered_at->format('d-m-Y h:i A') . '.', 'http' => 409];
            }

            $sale->delivered_at = now();
            $sale->delivered_by = Auth::id();
            $sale->save();
            $this->recordReceiptEvent($sale, 'delivery_verified', null, $request);

            return ['status' => 'success', 'message' => 'Delivery verified for sale #' . $sale->sale_no . '.', 'http' => 200];
        });

        return response()->json($result, $result['http']);
    }

    public function receiptAudit(Request $request, ReceiptAuditDataTable $dataTable)
    {
        $filters = $this->validateReceiptAuditFilters($request);
        $operators = User::query()
            ->when(! $this->viewerIsSuperAdmin(), function ($query) {
                $query->whereRaw("LOWER(COALESCE(user_type, '')) <> ?", ['super admin'])
                    ->whereDoesntHave('roles', function ($roleQuery) {
                        $roleQuery->whereRaw('LOWER(roles.name) = ?', ['super admin']);
                    });
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        return $dataTable->render('backend.sale.receipt_audit', compact('operators', 'filters'));
    }

    public function exportReceiptAudit(Request $request)
    {
        $filters = $this->validateReceiptAuditFilters($request);
        $events = $this->receiptAuditQuery($filters)->get();

        return response()->streamDownload(function () use ($events) {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Sale Number', 'Total Print Count', 'Event Type', 'Operator', 'Reason', 'Print Time', 'Delivery Status']);

            foreach ($events as $event) {
                $sale = $event->sale;
                fputcsv($output, [
                    $sale->sale_no ?? '',
                    $sale->total_print_count ?? 0,
                    $event->event_type,
                    optional($event->operator)->name ?? '',
                    $event->reason ?? '',
                    optional($event->created_at)->format('Y-m-d H:i:s'),
                    $sale->delivered_at ? 'Delivered' : 'Pending',
                ]);
            }

            fclose($output);
        }, 'sales-receipt-audit-' . now()->format('Ymd-His') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function recordReceiptEvent(Sale $sale, string $eventType, ?string $reason, Request $request): void
    {
        $sale->receiptEvents()->create([
            'operator_id' => Auth::id(),
            'event_type' => $eventType,
            'reason' => $reason,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 2000),
            'created_at' => now(),
        ]);
    }

    private function validateReceiptAuditFilters(Request $request): array
    {
        return $request->validate([
            'sale_no' => ['nullable', 'string', 'max:100'],
            'operator_id' => ['nullable', 'integer', 'exists:users,id'],
            'event_type' => ['nullable', 'in:original,reprint,delivery_verified'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'delivery_status' => ['nullable', 'in:delivered,pending'],
        ]);
    }

    private function receiptAuditQuery(array $filters)
    {
        $query = SaleReceiptEvent::query()
            ->with([
                'operator',
                'sale' => function ($query) {
                    $query->withCount(['receiptEvents as total_print_count' => function ($events) {
                        $events->whereIn('event_type', ['original', 'reprint']);
                    }]);
                },
            ])
            ->whereHas('sale', function ($query) use ($filters) {
                if (! empty($filters['sale_no'])) {
                    $query->where('sale_no', 'like', '%' . $filters['sale_no'] . '%');
                }
                if (! empty($filters['delivery_status'])) {
                    $filters['delivery_status'] === 'delivered'
                        ? $query->whereNotNull('delivered_at')
                        : $query->whereNull('delivered_at');
                }
            })
            ->when($filters['operator_id'] ?? null, fn ($query, $operatorId) => $query->where('operator_id', $operatorId))
            ->when($filters['event_type'] ?? null, fn ($query, $eventType) => $query->where('event_type', $eventType))
            ->when($filters['from_date'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['to_date'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date));

        if (! $this->viewerIsSuperAdmin()) {
            $query->whereDoesntHave('operator', function ($operatorQuery) {
                $operatorQuery->whereRaw("LOWER(COALESCE(user_type, '')) = ?", ['super admin'])
                    ->orWhereHas('roles', function ($roleQuery) {
                        $roleQuery->whereRaw('LOWER(roles.name) = ?', ['super admin']);
                    });
            });
        }

        return $query->orderByDesc('created_at');
    }

    private function viewerIsSuperAdmin(): bool
    {
        $viewer = Auth::user();

        return $viewer && (
            strtolower((string) ($viewer->user_type ?? '')) === 'super admin'
            || DB::table('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('model_has_roles.model_type', get_class($viewer))
                ->where('model_has_roles.model_id', $viewer->id)
                ->whereRaw('LOWER(roles.name) = ?', ['super admin'])
                ->exists()
        );
    }

    public function dailyTotalSales(Request $request)
    {
        $currentUser = Auth::user();
        $canSelectCashier = $currentUser && $currentUser->hasAnyRole(['Super Admin', 'Manager', 'Supervisor']);

        $validationRules = [
            'date' => ['nullable', 'date'],
        ];

        if ($canSelectCashier) {
            $validationRules['user_id'] = ['nullable', 'integer', 'exists:users,id'];
        }

        $validated = $request->validate($validationRules);
        $selectedDate = $validated['date'] ?? Carbon::now()->toDateString();

        $cashiers = collect();
        $selectedUserId = (int) $currentUser->id;
        $selectedUser = $currentUser;

        if ($canSelectCashier) {
            $cashiers = User::role('Cashier')->orderBy('name')->get(['id', 'name', 'username']);

            $selectedUserId = (int) ($validated['user_id'] ?? 0);
            if (! $selectedUserId && $cashiers->isNotEmpty()) {
                $selectedUserId = (int) $cashiers->first()->id;
            }

            $selectedUser = $cashiers->firstWhere('id', $selectedUserId);
            if (! $selectedUser && $cashiers->isNotEmpty()) {
                $selectedUser = $cashiers->first();
                $selectedUserId = (int) $selectedUser->id;
            }

            if (! $selectedUser) {
                $selectedUser = $currentUser;
                $selectedUserId = (int) $currentUser->id;
            }
        }

        $totalSalesAmount = Payment::query()
            ->join('sales', 'payments.sale_id', '=', 'sales.id')
            ->whereDate('sales.date', $selectedDate)
            ->whereNull('sales.cancelled_at')
            ->where('sales.created_by', $selectedUserId)
            ->sum('payments.total_amount');

        $companyInfo = Company::first();

        return view('backend.sale.daily_total_sales', [
            'selectedDate' => $selectedDate,
            'username' => $selectedUser->username ?? $selectedUser->name,
            'canSelectCashier' => $canSelectCashier,
            'cashiers' => $cashiers,
            'selectedUserId' => $selectedUserId,
            'totalSalesAmount' => (float) $totalSalesAmount,
            'companyInfo' => $companyInfo,
        ]);
    }

}
 
