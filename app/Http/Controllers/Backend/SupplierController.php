<?php

namespace App\Http\Controllers\Backend;

use App\DataTables\Admin\Report\Purchase\CashPurchasesDataTable;
use App\DataTables\Admin\Report\Purchase\CreditPurchasesDataTable;
use App\DataTables\Admin\Settings\SupplierDataTable;
use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\PurchasePaymentDetail;
use App\Services\Accounting\AccountingIntegrationService;
use App\Models\Supplier;
use App\Support\PaymentStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SupplierController extends Controller
{
    function __construct()
    {
                     $this->middleware('permission:supplier-list', ['only' => ['index','show']]);
         $this->middleware('permission:supplier-create', ['only' => ['create','store']]);
                $this->middleware('permission:supplier-edit', ['only' => ['edit','update']]);
         $this->middleware('permission:supplier-delete', ['only' => ['destroy']]);

                $this->middleware('permission:payment-purchases-credit-list', ['only' => ['CreditSupplier']]);
                $this->middleware('permission:payment-purchases-cash-list', ['only' => ['PaidSupplier']]);
                $this->middleware('permission:payment-purchases-credit-pay', ['only' => ['SupplierEditPurchase', 'SupplierUpdatePurchase']]);
    }

    public function index(SupplierDataTable $dataTable){
        return $dataTable->render('backend.supplier.index');
    } // End Method 

    public function create(){
        $currentUser = auth()->user();
        if ($currentUser && ! $currentUser->isCentral()) {
            abort(403, 'Supplier management is restricted to Central HQ.');
        }

        return view('backend.supplier.create');
    } // End Method 

    public function store(Request $request){
        $currentUser = auth()->user();
        if ($currentUser && ! $currentUser->isCentral()) {
            return response()->json([
                'status' => 403,
                'message' => 'Supplier management is restricted to Central HQ.',
            ], 403);
        }

        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('suppliers', 'name')->whereNull('deleted_at')],
            'mobile_no' => ['required', 'string', 'max:15', Rule::unique('suppliers', 'mobile_no')->whereNull('deleted_at')],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('suppliers', 'email')->whereNull('deleted_at')],
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'type' => 'nullable|string|in:Distributor,Wholesaler,Retailer,Manufacturer,Other',
        ], [
            'name.required' => 'Please enter a supplier name',
            'name.unique' => 'This supplier name already exists',
            'mobile_no.required' => 'Please enter a mobile number',
            'mobile_no.unique' => 'This mobile number already exists',
            'email.email' => 'Please enter a valid email address',
            'email.unique' => 'This email address already exists',
            'type.in' => 'Please select a valid supplier type',
        ]);

        try {
            // Use only validated data and add created_by
            $data = $validatedData;
            $data['created_by'] = Auth::id();
            
            // Use a transaction to ensure data integrity
            DB::transaction(function () use ($data) {
                Supplier::create($data);
            });

            return response()->json([
                'status' => 200,
                'message' => "Supplier '{$data['name']}' was successfully created",
            ]);

        } catch(\Throwable $e) {
            return response()->json([
                'status' => 400,
                'message' => $e->getMessage(),
            ]); 
        }
    } // End Method 

    /**
     * Display the specified resource.
     *
     * @param  string  $encryptedId
     * @return \Illuminate\Http\Response
     */
    public function show($encryptedId)
    {
        try {
            $id = Crypt::decrypt($encryptedId);
            $supplier = Supplier::find($id);
            
            if (!$supplier) {
                return redirect()->route('suppliers.index')->with('error', 'Supplier not found.');
            }
            
            return view('backend.supplier.show', compact('supplier'));
        } catch (\Exception $e) {
            return redirect()->route('suppliers.index')->with('error', 'Invalid supplier ID.');
        }
    }

    public function edit($encryptedId){
        $currentUser = auth()->user();
        if ($currentUser && ! $currentUser->isCentral()) {
            abort(403, 'Supplier management is restricted to Central HQ.');
        }

        try {
            $id = Crypt::decrypt($encryptedId);
            $supplier = Supplier::find($id);
            
            if (!$supplier) {
                return redirect()->route('suppliers.index')->with('error', 'Supplier not found.');
            }
            
            return view('backend.supplier.edit', compact('supplier'));
        } catch (\Exception $e) {
            return redirect()->route('suppliers.index')->with('error', 'Invalid supplier ID.');
        }
    }// End Method 

    public function update(Request $request, $encryptedId){ 
        $currentUser = auth()->user();
        if ($currentUser && ! $currentUser->isCentral()) {
            return response()->json([
                'status' => 403,
                'message' => 'Supplier management is restricted to Central HQ.',
            ], 403);
        }

        try {
            $id = Crypt::decrypt($encryptedId);
            $supplier = Supplier::find($id);
            
            if (!$supplier) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Supplier not found.',
                ]);
            }

            $isCashPurchase = (int) $supplier->id === 1 || strtolower((string) $supplier->name) === 'cash_purchase';
            if ($isCashPurchase && strtolower(trim((string) $request->name)) !== 'cash_purchase') {
                return response()->json([
                    'status' => 422,
                    'message' => 'The default system supplier name must remain "cash_purchase" to preserve cash purchase intake functionality.',
                ], 422);
            }

            $validatedData = $request->validate([
                'name' => 'required|string|max:255|unique:suppliers,name,' . $id,
                'mobile_no' => 'required|string|max:15|unique:suppliers,mobile_no,' . $id,
                'email' => 'nullable|email|max:255|unique:suppliers,email,' . $id,
                'address' => 'nullable|string|max:500',
                'city' => 'nullable|string|max:100',
                'type' => 'nullable|string|in:Distributor,Wholesaler,Retailer,Manufacturer,Other',
            ], [
                'name.required' => 'Please enter a supplier name',
                'name.unique' => 'This supplier name already exists',
                'mobile_no.required' => 'Please enter a mobile number',
                'mobile_no.unique' => 'This mobile number already exists',
                'email.email' => 'Please enter a valid email address',
                'email.unique' => 'This email address already exists',
                'type.in' => 'Please select a valid supplier type',
            ]);

            try {
                DB::transaction(function () use ($validatedData, $supplier) {
                    $updateData = [
                        'name' => $validatedData['name'],
                        'mobile_no' => $validatedData['mobile_no'],
                        'email' => $validatedData['email'],
                        'address' => $validatedData['address'],
                        'city' => $validatedData['city'],
                        'type' => $validatedData['type'],
                        'updated_by' => Auth::id(),
                    ];
                    
                    $supplier->update($updateData);
                });

                return response()->json([
                    'status' => 200,
                    'message' => "Supplier '{$validatedData['name']}' was successfully updated",
                    'redirect' => route('suppliers.index')
                ]);

            } catch(\Throwable $e) {
                return response()->json([
                    'status' => 400,
                    'message' => $e->getMessage(),
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 400,
                'message' => 'Invalid supplier ID.',
            ]);
        }
    }// End Method 

    public function destroy($encryptedId){
        $currentUser = auth()->user();
        if ($currentUser && ! $currentUser->isCentral()) {
            return response()->json([
                'status' => 403,
                'message' => 'Supplier management is restricted to Central HQ.',
            ], 403);
        }

        try {
            $id = Crypt::decrypt($encryptedId);
            $supplier = Supplier::find($id);

            if (!$supplier) {
                $message = 'Supplier not found.';
                if (request()->expectsJson() || request()->ajax()) {
                    return response()->json(['status' => 404, 'message' => $message], 404);
                }
                return redirect()->route('suppliers.index')->with(['message' => $message, 'alert-type' => 'error']);
            }

            if ((int) $supplier->id === 1 || strtolower((string) $supplier->name) === 'cash_purchase') {
                $message = 'The system default cash purchase supplier cannot be deleted.';
                if (request()->expectsJson() || request()->ajax()) {
                    return response()->json(['status' => 422, 'message' => $message], 422);
                }
                return redirect()->route('suppliers.index')->with(['message' => $message, 'alert-type' => 'error']);
            }

            if (Purchase::where('supplier_id', $supplier->id)->exists()) {
                $message = 'Cannot delete supplier with associated purchase records.';
                if (request()->expectsJson() || request()->ajax()) {
                    return response()->json(['status' => 422, 'message' => $message], 422);
                }
                return redirect()->route('suppliers.index')->with(['message' => $message, 'alert-type' => 'error']);
            }

            $supplierName = $supplier->name;

            DB::transaction(function () use ($supplier) {
                $supplier->delete();
            });

            $message = "Supplier '{$supplierName}' was successfully deleted.";

            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'status' => 200,
                    'message' => $message,
                ]);
            }

            return redirect()->route('suppliers.index')->with([
                'message' => $message,
                'alert-type' => 'success',
            ]);

        } catch (\Exception $e) {
            $message = 'Failed to delete supplier.';

            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'status' => 400,
                    'message' => $message,
                ], 400);
            }

            return redirect()->route('suppliers.index')->with([
                'message' => $message,
                'alert-type' => 'error',
            ]);
        }
    } // End Method 

    public function CreditSupplier(CreditPurchasesDataTable $dataTable)
    {
        $payments = PurchasePayment::with(['supplier:id,name', 'purchase:id,purchase_no'])
            ->where('due_amount', '>', 0)
            ->get();

        $purchase_invoices = $payments->pluck('purchase')->filter()->unique('id')->sortBy('purchase_no')->values();
        $suppliers = $payments->pluck('supplier')->filter()->unique('id')->sortBy('name')->values();

        return $dataTable->render('backend.supplier.supplier_credit', compact('purchase_invoices', 'suppliers'));
    }

    public function SupplierEditPurchase($purchase_id)
    {
        $currentUser = auth()->user();
        if ($currentUser && ! $currentUser->isCentral()) {
            abort(403, 'Supplier purchase settlements are managed at Central HQ only.');
        }

        $payment = PurchasePayment::with(['supplier', 'purchase.purchase_details.product', 'purchase.purchase_details.category'])
            ->where('purchase_id', $purchase_id)
            ->firstOrFail();

        return view('backend.supplier.edit_customer_sale', compact('payment'));
    }

    public function SupplierUpdatePurchase(Request $request, $purchase_id)
    {
        $currentUser = auth()->user();
        if ($currentUser && ! $currentUser->isCentral()) {
            abort(403, 'Supplier purchase settlements are managed at Central HQ only.');
        }

        $request->validate([
            'date' => ['required', 'date'],
            'paid_status' => ['required', Rule::in([
                PaymentStatus::FULL_PAYMENT,
                PaymentStatus::PARTIAL_PAYMENT,
            ])],
            'paid_amount' => ['nullable', 'numeric', 'min:0.01'],
        ]);

        $payment = PurchasePayment::where('purchase_id', $purchase_id)->firstOrFail();
        $currentDue = round((float) $payment->due_amount, 2);

        if ($currentDue <= 0) {
            return redirect()->back()->with([
                'message' => 'This purchase is already fully settled.',
                'alert-type' => 'error',
            ]);
        }

        $requestedStatus = PaymentStatus::normalize($request->paid_status);
        $paymentReceived = 0;

        if ($requestedStatus === PaymentStatus::FULL_PAYMENT) {
            $paymentReceived = $currentDue;
        } elseif ($requestedStatus === PaymentStatus::PARTIAL_PAYMENT) {
            if (!is_numeric($request->paid_amount)) {
                return redirect()->back()->with([
                    'message' => 'Amount to pay is required for partial payment.',
                    'alert-type' => 'error',
                ]);
            }

            $paymentReceived = round((float) $request->paid_amount, 2);

            if ($paymentReceived > $currentDue) {
                return redirect()->back()->with([
                    'message' => 'Amount to pay cannot be more than the current due amount.',
                    'alert-type' => 'error',
                ]);
            }
        } else {
            throw ValidationException::withMessages([
                'paid_status' => 'Invalid paid status selected.',
            ]);
        }

        if ($paymentReceived <= 0 || $paymentReceived > $currentDue) {
            return redirect()->back()->with([
                'message' => 'Entered amount is not valid for the current due balance.',
                'alert-type' => 'error',
            ]);
        }

        DB::transaction(function () use ($payment, $purchase_id, $request, $paymentReceived) {
            $newPaidAmount = round(((float) $payment->paid_amount) + $paymentReceived, 2);
            $newDueAmount = round(max(0, ((float) $payment->total_amount) - $newPaidAmount), 2);

            $payment->paid_amount = $newPaidAmount;
            $payment->due_amount = $newDueAmount;
            $payment->paid_status = $newDueAmount <= 0
                ? PaymentStatus::FULL_PAYMENT
                : PaymentStatus::PARTIAL_PAYMENT;
            $payment->save();

            $paymentDetails = new PurchasePaymentDetail();
            $paymentDetails->purchase_id = $purchase_id;
            $paymentDetails->date = date('Y-m-d', strtotime($request->date));
            $paymentDetails->current_paid_amount = $paymentReceived;
            $paymentDetails->updated_by = Auth::id();
            $paymentDetails->save();

            app(AccountingIntegrationService::class)->postSupplierSettlement($payment, $paymentDetails, Auth::id());
        });

        return redirect()->route('credit.supplier')->with([
            'message' => 'Purchase payment updated successfully',
            'alert-type' => 'success',
        ]);
    }

    public function PaidSupplier(CashPurchasesDataTable $dataTable)
    {
        $payments = PurchasePayment::with(['supplier:id,name', 'purchase:id,purchase_no'])
            ->where('due_amount', '<=', 0)
            ->get();

        $purchase_invoices = $payments->pluck('purchase')->filter()->unique('id')->sortBy('purchase_no')->values();
        $suppliers = $payments->pluck('supplier')->filter()->unique('id')->sortBy('name')->values();

        return $dataTable->render('backend.supplier.customer_paid', compact('purchase_invoices', 'suppliers'));
    }

    
}
