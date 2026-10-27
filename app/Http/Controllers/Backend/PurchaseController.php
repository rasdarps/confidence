<?php

namespace App\Http\Controllers\Backend;

use App\DataTables\Admin\Report\Purchase\AllPurchasesDataTable;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\PurchaseEditTrail;
use App\Models\PurchasePayment;
use App\Models\PurchasePaymentDetail;
use App\Models\Supplier;
use App\Support\PaymentStatus;
use App\Models\Unit;
use App\Models\User;
use App\Services\Accounting\AccountingIntegrationService;
use App\Services\StockLedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
  
class PurchaseController extends Controller

{
    function __construct()
    {
    $this->middleware('permission:purchase-list', ['only' => ['index']]);
    $this->middleware('permission:purchase-create', ['only' => ['create', 'store']]);
    $this->middleware('permission:purchase-edit', ['only' => ['edit', 'update']]);
    $this->middleware('permission:purchase-cancel', ['only' => ['cancel']]);
    $this->middleware('permission:purchase-print', ['only' => ['PrintPurchaseList', 'PrintPurchase']]);
    }

   
    public function index(AllPurchasesDataTable $dataTable){
        $suppliers = Supplier::orderBy('name')->get();
        $purchase_invoices = Purchase::orderBy('purchase_no')->get();
        $users = User::orderBy('name')->get();
        return $dataTable->render('backend.purchase.index', compact('suppliers', 'purchase_invoices', 'users'));
    } // End Method

    public function create(){ 
        $unit = Unit::all();
        $category = Category::all();
        $products = Product::with(['category', 'unit'])->orderBy('name')->get();
        $suppliers = Supplier::all();

        // Get the maximum numeric purchase_no
        $maxPurchaseNo = Purchase::max(DB::raw('CAST(purchase_no AS UNSIGNED)'));
        if ($maxPurchaseNo) {
            $purchase_no = str_pad($maxPurchaseNo + 1, 2, '0', STR_PAD_LEFT);
        } else {
            $purchase_no = '01';
        }

        $date = Carbon::now()->format('Y-m-d');        
       
         return view('backend.purchase.create',compact('purchase_no','category','date','suppliers','unit', 'products'));
    } // End Method


        public function store(Request $request)
    {
            $requestedStatus = PaymentStatus::normalize((string) $request->paid_status);

        // Backend validation
        $rules = [
            'date' => ['required', 'date'],
            'product_id' => ['required', 'array', 'min:1'],
            'product_id.*' => ['required', 'exists:products,id'],
            'buying_qty' => ['required', 'array', 'min:1'],
            'buying_qty.*' => ['required', 'numeric', 'min:1'],
            'unit_cost_price' => ['required', 'array', 'min:1'],
            'unit_cost_price.*' => ['required', 'numeric', 'min:0.01'],
            'unit_selling_price' => ['required', 'array', 'min:1'],
            'unit_selling_price.*' => ['required', 'numeric', 'min:0.01'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'paid_status' => [
                'required',
                Rule::notIn(['']),
                Rule::in(PaymentStatus::canonicalStatuses()),
            ],
            'supplier_id' => ['required'],
        ];

        // New supplier fields required if supplier_id == 0
        if ($request->supplier_id == '0') {
            $rules['name'] = ['required', 'string', 'max:255'];
            $rules['mobile_no'] = ['required', 'digits:10'];
            $rules['email'] = ['required', 'email', 'max:255'];
        }

        $request->validate($rules);

        if ($requestedStatus === null) {
            throw ValidationException::withMessages([
                'paid_status' => 'Invalid payment status selected.',
            ]);
        }

        $itemCount = count($request->product_id);
        foreach (['buying_qty', 'unit_cost_price', 'unit_selling_price'] as $field) {
            if (count($request->input($field, [])) !== $itemCount) {
                throw ValidationException::withMessages([$field => 'Each purchase product must have matching quantity and prices.']);
            }
        }

        $buyingPrices = [];
        foreach ($request->product_id as $index => $productId) {
            $product = Product::query()->find($productId);
            if (!$product) {
                throw ValidationException::withMessages(['product_id.'.$index => 'Selected product is unavailable.']);
            }
            $cost = round((float) $request->unit_cost_price[$index], 2);
            $selling = round((float) $request->unit_selling_price[$index], 2);
            if ($selling <= $cost) {
                throw ValidationException::withMessages([
                    'unit_selling_price.'.$index => 'Unit selling price must be greater than unit cost price for item #'.($index + 1).'.',
                ]);
            }
            $buyingPrices[] = round((float) $request->buying_qty[$index] * $cost, 2);
        }

        $request->merge(['buying_price' => $buyingPrices]);
        $subtotal = round(array_sum($buyingPrices), 2);
        $discountAmount = round((float) ($request->input('discount_amount') ?? 0), 2);
        if ($discountAmount >= $subtotal && $discountAmount > 0) {
            throw ValidationException::withMessages(['discount_amount' => 'Discount must be less than the server-calculated purchase subtotal.']);
        }
        $totalAmount = round($subtotal - $discountAmount, 2);
        $request->merge(['estimated_amount' => $totalAmount]);

        if ($requestedStatus === PaymentStatus::PARTIAL_PAYMENT) {
            $request->validate([
                'paid_amount' => ['required', 'numeric', 'min:0.01', 'max:'.max(0, $totalAmount - 0.01)],
            ]);
        }

        $supplier_id = $request->supplier_id == '0' ? 0 : (int) $request->supplier_id;
        $cashSupplierId = (int) (Supplier::whereRaw('LOWER(name) = ?', ['cash_purchase'])->value('id') ?? 0);

        if ($supplier_id <= 0 && $request->supplier_id != '0') {
            if ($requestedStatus === PaymentStatus::FULL_PAYMENT && $cashSupplierId > 0) {
                $supplier_id = $cashSupplierId;
            } else {
                throw ValidationException::withMessages([
                    'supplier_id' => 'Please select a valid supplier account.',
                ]);
            }
        }

        if ($supplier_id > 0 && !Supplier::whereKey($supplier_id)->exists()) {
            throw ValidationException::withMessages([
                'supplier_id' => 'Selected supplier account was not found.',
            ]);
        }

        if (
            $request->supplier_id != '0'
            && $cashSupplierId > 0
            && $supplier_id === $cashSupplierId
            && PaymentStatus::isCredit($requestedStatus)
        ) {
            throw ValidationException::withMessages([
                'supplier_id' => 'Credit and partial purchases cannot be posted to cash_purchase account. Select a supplier or create a new one.',
            ]);
        }

        if ($requestedStatus === PaymentStatus::PARTIAL_PAYMENT && $totalAmount <= 0) {
            throw ValidationException::withMessages([
                'paid_amount' => 'Partial payment is not valid when total amount is zero.',
            ]);
        }

        $paidAmount = 0;
        $dueAmount = 0;
        $currentPaidAmount = 0;

        if ($requestedStatus === PaymentStatus::FULL_PAYMENT) {
            $paidAmount = $totalAmount;
            $dueAmount = 0;
            $currentPaidAmount = $totalAmount;
        } elseif ($requestedStatus === PaymentStatus::FULL_CREDIT) {
            $paidAmount = 0;
            $dueAmount = $totalAmount;
            $currentPaidAmount = 0;
        } else {
            $paidAmount = round((float) $request->paid_amount, 2);
            $dueAmount = round(max(0, $totalAmount - $paidAmount), 2);
            $currentPaidAmount = $paidAmount;
        }

        foreach ($request->unit_cost_price as $index => $costPrice) {
            $sellingPrice = $request->unit_selling_price[$index] ?? null;
            if ((float) $sellingPrice <= (float) $costPrice) {
                throw ValidationException::withMessages([
                    'unit_selling_price.' . $index => 'Unit selling price must be greater than unit cost price for item #' . ($index + 1) . '.',
                ]);
            }
        }

        $attempts = 0;
        $maxAttempts = 3;

        while ($attempts < $maxAttempts) {
            $attempts++;
            try {
                $purchase = DB::transaction(function () use ($request, $requestedStatus, $paidAmount, $dueAmount, $currentPaidAmount, $supplier_id, $totalAmount) {

                    $maxPurchaseNo = (int) DB::table('purchases')
                        ->lockForUpdate()
                        ->selectRaw('COALESCE(MAX(CAST(purchase_no AS UNSIGNED)), 0) as max_no')
                        ->value('max_no');
                    $nextNo = str_pad($maxPurchaseNo + 1, 2, '0', STR_PAD_LEFT);

                    $purchase = Purchase::create([
                        'purchase_no' => $nextNo,
                        'date' => $request->date,
                        'description' => $request->description,
                        'created_by' => Auth::id(),
                    ]);

                    $countProducts = count($request->product_id);
                    for ($i = 0; $i < $countProducts; $i++) {
                        $product = Product::whereKey($request->product_id[$i])->lockForUpdate()->firstOrFail();

                        $categoryId = $product->category_id;
                        $unitId = $product->unit_id;

                        if (!$categoryId || !$unitId) {
                            throw ValidationException::withMessages([
                                'product_id.' . $i => 'Selected product is missing category or unit setup.',
                            ]);
                        }

                        $purchase_details = new PurchaseDetail();
                        $purchase_details->date = date('Y-m-d', strtotime($request->date));
                        $purchase_details->purchase_id = $purchase->id;
                        $purchase_details->category_id = $categoryId;
                        $purchase_details->product_id = $request->product_id[$i];
                        $purchase_details->buying_qty = $request->buying_qty[$i];
                        $purchase_details->unit_cost_price = $request->unit_cost_price[$i];
                        $purchase_details->buying_price = $request->buying_price[$i];
                        $purchase_details->unit_id = $unitId;
                        $purchase_details->save();

                        $centralOutletId = \App\Models\Outlet::where('is_central', true)->value('id') ?? 1;
                        $outletStock = \App\Models\OutletStock::firstOrCreate([
                            'outlet_id' => $centralOutletId,
                            'product_id' => $product->id,
                        ], [
                            'quantity' => 0,
                            'stock_level' => 0,
                        ]);
                        $outletStock = \App\Models\OutletStock::where('id', $outletStock->id)->lockForUpdate()->first();
                        $beforeQty = (float) $outletStock->quantity;
                        $lineQty = (float) $purchase_details->buying_qty;
                        $afterQty = $beforeQty + $lineQty;
                        $outletStock->quantity = $afterQty;
                        $outletStock->save();

                        $priceChanged = false;

                        if (empty($product->cost_price) || $product->cost_price != $purchase_details->unit_cost_price) {
                            $product->cost_price = $purchase_details->unit_cost_price;
                            $priceChanged = true;
                        }

                        if (!empty($request->unit_selling_price[$i])) {
                            if (empty($product->selling_price) || $product->selling_price != $request->unit_selling_price[$i]) {
                                $product->selling_price = $request->unit_selling_price[$i];
                                $priceChanged = true;
                            }
                        }

                        $product->save();

                        if ($priceChanged) {
                            ProductPrice::create([
                                'product_id'    => $product->id,
                                'cost_price'    => $product->cost_price,
                                'selling_price' => $product->selling_price,
                                'created_by'    => Auth::id(),
                            ]);
                        }

                        app(StockLedgerService::class)->record([
                            'outlet_id' => $centralOutletId,
                            'product_id' => $product->id,
                            'movement_type' => 'purchase',
                            'reference_type' => 'purchase',
                            'reference_id' => $purchase->id,
                            'reference_detail_id' => $purchase_details->id,
                            'movement_at' => $purchase->date,
                            'qty_in' => $lineQty,
                            'qty_out' => 0,
                            'balance_before' => $beforeQty,
                            'balance_after' => $afterQty,
                            'unit_cost' => $purchase_details->unit_cost_price,
                            'reason' => 'Purchase stock-in',
                            'notes' => $purchase->description,
                            'created_by' => Auth::id(),
                        ]);
                    }

                    if ($request->supplier_id == '0') {
                        $supplier = Supplier::create([
                            'name' => $request->name,
                            'mobile_no' => $request->mobile_no,
                            'email' => $request->email,
                            'created_by' => Auth::id(),
                        ]);
                        $supplier_id = $supplier->id;
                    }

                    $purchase_payment = new PurchasePayment();
                    $purchase_payment_details = new PurchasePaymentDetail();

                    $purchase_payment->purchase_id = $purchase->id;
                    $purchase_payment->supplier_id = $supplier_id;
                    $purchase_payment->paid_status = $requestedStatus;
                    $purchase_payment->discount_amount = $request->discount_amount;
                    $purchase_payment->total_amount = $totalAmount;
                    $purchase_payment->paid_amount = $paidAmount;
                    $purchase_payment->due_amount = $dueAmount;
                    $purchase_payment_details->current_paid_amount = $currentPaidAmount;
                    $purchase_payment->save();

                    $purchase_payment_details->purchase_id = $purchase->id;
                    $purchase_payment_details->date = date('Y-m-d', strtotime($request->date));
                    $purchase_payment_details->save();

                    app(AccountingIntegrationService::class)->postPurchaseFromPayment($purchase, $purchase_payment, Auth::id());

                    return $purchase;
                });

                break;
            } catch (QueryException $e) {
                $isDuplicatePurchaseNo = (string) ($e->getCode()) === '23000'
                    && str_contains(strtolower($e->getMessage()), 'purchase_no');

                if ($isDuplicatePurchaseNo && $attempts < $maxAttempts) {
                    continue;
                }

                throw $e;
            }
        }

        // Return JSON for AJAX, redirect for normal
        if ($request->ajax()) {
            // Calculate next number after commit
            $maxPurchaseNo = (int) DB::table('purchases')
                ->selectRaw('COALESCE(MAX(CAST(purchase_no AS UNSIGNED)), 0) as max_no')
                ->value('max_no');
            $nextNo = str_pad($maxPurchaseNo + 1, 2, '0', STR_PAD_LEFT);

            return response()->json([
                'status' => 200,
                'message' => 'Purchase Data Inserted Successfully',
                'purchase_id' => $purchase->id,
                'next_no' => $nextNo, // send next number to frontend
            ]);
        } else {
            $notification = [
                'message' => 'Purchase Data Inserted Successfully',
                'alert-type' => 'success'
            ];
            return redirect()->route('purchases.create')->with($notification);
        }
    }

    public function edit(string $encryptedId)
    {
        try {
            $id = Crypt::decrypt($encryptedId);
            $purchase = Purchase::with([
                'purchase_details.product.unit',
                'purchase_payment.supplier',
                'updatedBy',
            ])->findOrFail($id);

            if ($purchase->cancelled_at) {
                return redirect()->route('purchases.index')->with([
                    'message' => 'Cancelled purchases cannot be edited.',
                    'alert-type' => 'error',
                ]);
            }

            [$canEdit, $blockMessage, $windowEndsAt, $windowHours] = $this->purchaseEditAccessState($purchase);
            if (! $canEdit) {
                return redirect()->route('purchases.index')->with([
                    'message' => $blockMessage,
                    'alert-type' => 'error',
                ]);
            }

            $purchase->purchase_details->each(function ($detail) {
                $oldQty = (float) $detail->buying_qty;
                $currentQty = (float) optional($detail->product)->quantity;

                // Minimum quantity that can be retained without driving pooled stock negative.
                $minQty = max(0, round($oldQty - $currentQty, 2));
                $detail->editable_min_qty = $minQty;
                $detail->already_sold_min = round(max(0, $oldQty - $minQty), 2);
            });

            return view('backend.purchase.edit', [
                'purchase' => $purchase,
                'encryptedId' => $encryptedId,
                'editWindowHours' => $windowHours,
                'windowEndsAt' => $windowEndsAt,
            ]);
        } catch (\Exception $e) {
            return redirect()->route('purchases.index')->with([
                'message' => 'Invalid purchase ID.',
                'alert-type' => 'error',
            ]);
        }
    }

    public function update(Request $request, string $encryptedId)
    {
        $id = Crypt::decrypt($encryptedId);

        $purchase = Purchase::with(['purchase_details.product', 'purchase_payment'])
            ->findOrFail($id);

        if ($purchase->cancelled_at) {
            return redirect()->route('purchases.index')->with([
                'message' => 'Cancelled purchases cannot be edited.',
                'alert-type' => 'error',
            ]);
        }

        [$canEdit, $blockMessage] = $this->purchaseEditAccessState($purchase);
        if (! $canEdit) {
            return redirect()->route('purchases.index')->with([
                'message' => $blockMessage,
                'alert-type' => 'error',
            ]);
        }

        $validated = $request->validate([
            'qty' => ['required', 'array', 'min:1'],
            'qty.*' => ['required', 'numeric', 'min:0'],
            'unit_cost_price' => ['required', 'array', 'min:1'],
            'unit_cost_price.*' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
        ]);

        $details = $purchase->purchase_details->keyBy('id');

        if ($details->isEmpty()) {
            return redirect()->route('purchases.index')->with([
                'message' => 'This purchase has no editable line items.',
                'alert-type' => 'error',
            ]);
        }

        $requestedDetailIds = collect(array_keys($validated['qty']))
            ->map(fn ($idValue) => (int) $idValue)
            ->unique()
            ->values();

        $existingDetailIds = $details->keys()->map(fn ($idValue) => (int) $idValue)->values();

        if ($requestedDetailIds->sort()->values()->all() !== $existingDetailIds->sort()->values()->all()) {
            return redirect()->back()->withInput()->withErrors([
                'qty' => 'Submitted line items do not match the purchase lines. Please refresh and try again.',
            ]);
        }

        $newLineTotalsByDetailId = [];
        $oldQtyByProduct = [];
        $newQtyByProduct = [];
        $hasPositiveLine = false;

        foreach ($details as $detailId => $detail) {
            $newQty = round((float) ($validated['qty'][$detailId] ?? 0), 2);
            $newUnitCost = round((float) ($validated['unit_cost_price'][$detailId] ?? 0), 2);
            $newLineTotal = round($newQty * $newUnitCost, 2);

            $newLineTotalsByDetailId[$detailId] = [
                'qty' => $newQty,
                'unit_cost_price' => $newUnitCost,
                'buying_price' => $newLineTotal,
            ];

            $productId = (int) $detail->product_id;
            $oldQtyByProduct[$productId] = ($oldQtyByProduct[$productId] ?? 0) + (float) $detail->buying_qty;
            $newQtyByProduct[$productId] = ($newQtyByProduct[$productId] ?? 0) + $newQty;

            if ($newQty > 0) {
                $hasPositiveLine = true;
            }
        }

        if (! $hasPositiveLine) {
            return redirect()->back()->withInput()->withErrors([
                'qty' => 'At least one purchase line must have quantity greater than zero.',
            ]);
        }

        DB::transaction(function () use ($purchase, $validated, $details, $oldQtyByProduct, $newQtyByProduct, $newLineTotalsByDetailId) {
            $purchase = Purchase::query()->lockForUpdate()->findOrFail($purchase->id);
            $payment = PurchasePayment::query()->lockForUpdate()->where('purchase_id', $purchase->id)->firstOrFail();
            $beforeTotal = round((float) ($payment->total_amount ?? 0), 2);
            $changeRows = [];

            $productLocks = Product::query()
                ->whereIn('id', array_keys($oldQtyByProduct))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($oldQtyByProduct as $productId => $oldTotalQty) {
                $newTotalQty = (float) ($newQtyByProduct[$productId] ?? 0);
                $qtyReduction = round(max(0, $oldTotalQty - $newTotalQty), 2);
                $product = $productLocks->get((int) $productId);
                $availableQty = (float) optional($product)->quantity;

                if (! $product) {
                    throw ValidationException::withMessages([
                        'qty' => ['One or more products in this purchase are missing.'],
                    ]);
                }

                if ($qtyReduction > $availableQty) {
                    throw ValidationException::withMessages([
                        'qty' => [
                            "Cannot reduce {$product->name} by {$qtyReduction}. Available stock is {$availableQty}.",
                        ],
                    ]);
                }
            }

            app(AccountingIntegrationService::class)->reversePurchase(
                $purchase,
                $payment,
                'Purchase edited and reposted',
                Auth::id()
            );

            $touchedProductIds = [];

            foreach ($details as $detailId => $detail) {
                $newLine = $newLineTotalsByDetailId[$detailId];
                $oldQty = round((float) $detail->buying_qty, 2);
                $oldUnitCost = round((float) $detail->unit_cost_price, 2);
                $newQty = $newLine['qty'];
                $newUnitCost = round((float) $newLine['unit_cost_price'], 2);
                $deltaQty = round($newQty - $oldQty, 2);

                if ($oldQty !== $newQty || $oldUnitCost !== $newUnitCost) {
                    $changeRows[] = [
                        'product_id' => (int) $detail->product_id,
                        'product_name' => (string) optional($detail->product)->name,
                        'old_qty' => $oldQty,
                        'new_qty' => $newQty,
                        'old_unit_cost_price' => $oldUnitCost,
                        'new_unit_cost_price' => $newUnitCost,
                    ];
                }

                $product = $productLocks->get((int) $detail->product_id);
                $beforeQty = round((float) $product->quantity, 2);
                $afterQty = round($beforeQty + $deltaQty, 2);

                if ($afterQty < 0) {
                    throw ValidationException::withMessages([
                        'qty' => ["Stock for {$product->name} would go negative after update."],
                    ]);
                }

                if ($deltaQty !== 0.0) {
                    $product->quantity = $afterQty;
                    $product->save();

                    if ($deltaQty > 0) {
                        app(StockLedgerService::class)->record([
                            'outlet_id' => $centralOutletId,
                            'product_id' => $product->id,
                            'movement_type' => 'purchase',
                            'reference_type' => 'purchase',
                            'reference_id' => $purchase->id,
                            'reference_detail_id' => $detail->id,
                            'movement_at' => now(),
                            'qty_in' => $deltaQty,
                            'qty_out' => 0,
                            'balance_before' => $beforeQty,
                            'balance_after' => $afterQty,
                            'unit_cost' => $newLine['unit_cost_price'],
                            'reason' => 'Purchase edit stock increase',
                            'notes' => 'Purchase #' . $purchase->purchase_no . ' edit repost',
                            'created_by' => Auth::id(),
                        ]);
                    } else {
                        app(StockLedgerService::class)->record([
                            'product_id' => $product->id,
                            'movement_type' => 'purchase_cancel',
                            'reference_type' => 'purchase',
                            'reference_id' => $purchase->id,
                            'reference_detail_id' => $detail->id,
                            'movement_at' => now(),
                            'qty_in' => 0,
                            'qty_out' => abs($deltaQty),
                            'balance_before' => $beforeQty,
                            'balance_after' => $afterQty,
                            'unit_cost' => $newLine['unit_cost_price'],
                            'reason' => 'Purchase edit stock reduction',
                            'notes' => 'Purchase #' . $purchase->purchase_no . ' edit repost',
                            'created_by' => Auth::id(),
                        ]);
                    }
                }

                if ($newQty <= 0) {
                    $detail->delete();
                } else {
                    $detail->buying_qty = $newLine['qty'];
                    $detail->unit_cost_price = $newLine['unit_cost_price'];
                    $detail->buying_price = $newLine['buying_price'];
                    $detail->save();
                }

                $touchedProductIds[(int) $product->id] = (int) $product->id;
            }

            $remainingLines = PurchaseDetail::query()->where('purchase_id', $purchase->id)->get();
            $grossTotal = round((float) $remainingLines->sum('buying_price'), 2);
            $discountAmount = round((float) ($payment->discount_amount ?? 0), 2);
            $newTotal = round(max(0, $grossTotal - $discountAmount), 2);
            $currentPaid = round((float) ($payment->paid_amount ?? 0), 2);

            if ($newTotal <= 0) {
                throw ValidationException::withMessages([
                    'qty' => ['Updated purchase total cannot be zero. Keep at least one valid line amount.'],
                ]);
            }

            if ($currentPaid > $newTotal) {
                throw ValidationException::withMessages([
                    'qty' => [
                        'Updated total is below amount already paid. Reduce paid amount via payment reversal before editing this purchase.',
                    ],
                ]);
            }

            $newDue = round(max(0, $newTotal - $currentPaid), 2);
            $newStatus = $newDue <= 0
                ? PaymentStatus::FULL_PAYMENT
                : ($currentPaid > 0 ? PaymentStatus::PARTIAL_PAYMENT : PaymentStatus::FULL_CREDIT);

            $payment->total_amount = $newTotal;
            $payment->due_amount = $newDue;
            $payment->paid_status = $newStatus;
            $payment->save();

            $deltaTotal = round($newTotal - $beforeTotal, 2);
            PurchaseEditTrail::create([
                'purchase_id' => $purchase->id,
                'edited_by' => Auth::id(),
                'before_total' => $beforeTotal,
                'after_total' => $newTotal,
                'delta_total' => $deltaTotal,
                'reason' => $validated['reason'],
                'changes' => $changeRows,
            ]);

            $purchase->description = $validated['description'] ?? $purchase->description;
            $purchase->updated_by = Auth::id();
            $purchase->save();

            app(AccountingIntegrationService::class)->postPurchaseFromPayment($purchase, $payment, Auth::id());

            foreach ($touchedProductIds as $productId) {
                $product = Product::query()->lockForUpdate()->find($productId);
                if (! $product) {
                    continue;
                }

                $latestLine = PurchaseDetail::query()
                    ->join('purchases', 'purchases.id', '=', 'purchase_details.purchase_id')
                    ->whereNull('purchases.cancelled_at')
                    ->where('purchase_details.product_id', $productId)
                    ->where('purchase_details.buying_qty', '>', 0)
                    ->orderByDesc('purchases.date')
                    ->orderByDesc('purchase_details.id')
                    ->select('purchase_details.unit_cost_price')
                    ->first();

                if ($latestLine && (float) $product->cost_price !== (float) $latestLine->unit_cost_price) {
                    $product->cost_price = (float) $latestLine->unit_cost_price;
                    $product->save();

                    ProductPrice::create([
                        'product_id' => $product->id,
                        'cost_price' => $product->cost_price,
                        'selling_price' => $product->selling_price,
                        'created_by' => Auth::id(),
                    ]);
                }
            }
        });

        return redirect()->route('purchases.index')->with([
            'message' => 'Purchase updated and reposted successfully.',
            'alert-type' => 'success',
        ]);
    }

    private function purchaseEditAccessState(Purchase $purchase): array
    {
        $windowHours = max(24, min(720, (int) config('pos.purchase_edit_window_hours', 24)));
        $editEnabled = (bool) config('pos.purchase_edit_enabled', false);
        $windowEndsAt = Carbon::parse($purchase->created_at)->copy()->addHours($windowHours);

        if (! $editEnabled) {
            return [false, 'Purchase editing is disabled in settings.', $windowEndsAt, $windowHours];
        }

        if (now()->greaterThan($windowEndsAt)) {
            return [
                false,
                'Purchase edit window has expired for this transaction.',
                $windowEndsAt,
                $windowHours,
            ];
        }

        return [true, null, $windowEndsAt, $windowHours];
    }


    public function cancel(Request $request, $encryptedId)
    {
        try {
            $id = Crypt::decrypt($encryptedId);
            $purchase = Purchase::with('purchase_details')->findOrFail($id);

            if ($purchase->cancelled_at) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Purchase #' . $purchase->purchase_no . ' is already cancelled.',
                ], 422);
            }

            $validated = $request->validate([
                'reason' => 'required|string|max:500',
            ], [
                'reason.required' => 'Please provide a reason for cancellation.',
            ]);

            DB::transaction(function () use ($purchase, $validated) {
                $paymentSnapshot = PurchasePayment::query()->where('purchase_id', $purchase->id)->first();

                if ($paymentSnapshot) {
                    app(AccountingIntegrationService::class)->reversePurchase($purchase, $paymentSnapshot, $validated['reason'], Auth::id());
                }

                // Reverse stock-in for every line item. If stock has been consumed, block cancellation.
                $centralOutletId = \App\Models\Outlet::where('is_central', true)->value('id') ?? 1;
                foreach ($purchase->purchase_details as $detail) {
                    $outletStock = \App\Models\OutletStock::where('outlet_id', $centralOutletId)->where('product_id', $detail->product_id)->lockForUpdate()->first();
                    if ($outletStock) {
                        $beforeQty = (float) $outletStock->quantity;
                        $lineQty = (float) $detail->buying_qty;
                        $newQty = $beforeQty - $lineQty;
                        if ($newQty < 0) {
                            throw ValidationException::withMessages([
                                'reason' => ['Cannot cancel this purchase because some purchased stock has already been used/sold.'],
                            ]);
                        }
                        $outletStock->quantity = $newQty;
                        $outletStock->save();

                        app(StockLedgerService::class)->record([
                            'outlet_id' => $centralOutletId,
                            'product_id' => $detail->product_id,
                            'movement_type' => 'purchase_cancel',
                            'reference_type' => 'purchase',
                            'reference_id' => $purchase->id,
                            'reference_detail_id' => $detail->id,
                            'movement_at' => now(),
                            'qty_in' => 0,
                            'qty_out' => $lineQty,
                            'balance_before' => $beforeQty,
                            'balance_after' => $newQty,
                            'unit_cost' => $detail->unit_cost_price,
                            'reason' => 'Purchase cancellation',
                            'notes' => $validated['reason'],
                            'created_by' => Auth::id(),
                        ]);
                    }
                }

                // Zero-out line values so cancelled purchase does not affect quantity/value reporting
                PurchaseDetail::where('purchase_id', $purchase->id)->update([
                    'buying_qty' => 0,
                    'unit_cost_price' => 0,
                    'buying_price' => 0,
                ]);

                // Zero-out payment values so cancelled purchase does not affect financial totals
                PurchasePayment::where('purchase_id', $purchase->id)->update([
                    'paid_status' => PaymentStatus::FULL_PAYMENT,
                    'paid_amount' => 0,
                    'due_amount' => 0,
                    'total_amount' => 0,
                    'discount_amount' => 0,
                ]);

                // Zero-out payment detail entries tied to this purchase
                PurchasePaymentDetail::where('purchase_id', $purchase->id)->update([
                    'current_paid_amount' => 0,
                ]);

                // Stamp cancellation on the purchase
                $purchase->update([
                    'cancelled_at'        => now(),
                    'cancelled_by'        => Auth::id(),
                    'cancellation_reason' => $validated['reason'],
                ]);
            });

            return response()->json([
                'status'  => 200,
                'message' => 'Purchase #' . $purchase->purchase_no . ' has been cancelled successfully.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => 422,
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 400,
                'message' => 'Failed to cancel purchase.',
            ], 400);
        }
    } // End Method


    public function PrintPurchaseList(){

    $allData = Purchase::orderBy('date','desc')->orderBy('id','desc')->get();
       return view('backend.purchase.print_purchase_list',compact('allData'));
    } // End Method


    //detail purchase print
    public function PrintPurchase($encryptedId){
        
        $id = Crypt::decrypt($encryptedId);
        $purchase = Purchase::with('purchase_details')->findOrFail($id);
        $companyInfo = Company::first();

        return view('backend.purchase.purchase_receipt',compact('purchase','companyInfo'));

    } // End Method

}
