<?php

namespace App\Http\Controllers\Backend;

use App\DataTables\Admin\Report\Sales\CashSalesDataTable;
use App\DataTables\Admin\Report\Sales\CreditSalesDataTable;
use App\DataTables\Admin\Settings\CustomerDataTable;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\PaymentDetail;
use App\Services\Accounting\AccountingIntegrationService;
use App\Support\PaymentStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CustomerController extends Controller
{
    function __construct()
    {
                $this->middleware('permission:customer-list', ['only' => ['index','show']]);
         $this->middleware('permission:customer-create', ['only' => ['create','store']]);
                $this->middleware('permission:customer-edit', ['only' => ['edit','update', 'CustomerEditInvoice', 'CustomerUpdateInvoice']]);
         $this->middleware('permission:customer-delete', ['only' => ['destroy']]);

                $this->middleware('permission:payment-sales-credit-list', ['only' => ['CreditCustomer']]);
                $this->middleware('permission:payment-sales-cash-list', ['only' => ['PaidCustomer']]);
                $this->middleware('permission:payment-sales-credit-pay', ['only' => ['CustomerEditSale', 'CustomerUpdateSale']]);
    }

    // public function index(){
    //     $customer = Customer::orderBy('name', 'asc')->get();
    //     return view('backend.customer.index', compact('customer'));
    // } // End Method
    public function index(CustomerDataTable $dataTable)
    {
        //
        return $dataTable->render('backend.customer.index');
    }

    public function create(){
        return view('backend.customer.create');
    } // End Method

    public function store(Request $request){
        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('customers', 'name')->whereNull('deleted_at')],
            'mobile_no' => ['required', 'string', 'max:15', Rule::unique('customers', 'mobile_no')->whereNull('deleted_at')],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('customers', 'email')->whereNull('deleted_at')],
            'address' => 'nullable|string|max:500',
        ], [
            'name.required' => 'Please enter a customer name',
            'name.unique' => 'This customer name already exists',
            'mobile_no.required' => 'Please enter a mobile number',
            'mobile_no.unique' => 'This mobile number already exists',
            'email.email' => 'Please enter a valid email address',
            'email.unique' => 'This email address already exists',
        ]);

        try {
            // Use only validated data and add created_by
            $data = $validatedData;
            $data['created_by'] = Auth::id();
            
            // Use a transaction to ensure data integrity
            DB::transaction(function () use ($data) {
                Customer::create($data);
            });

            return response()->json([
                'status' => 200,
                'message' => "Customer '{$data['name']}' was successfully created",
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
            $customer = Customer::find($id);
            
            if (!$customer) {
                return redirect()->route('customers.index')->with('error', 'Customer not found.');
            }
            
            return view('backend.customer.show', compact('customer'));
        } catch (\Exception $e) {
            return redirect()->route('customers.index')->with('error', 'Invalid customer ID.');
        }
    }

    public function edit($encryptedId){
        try {
            $id = Crypt::decrypt($encryptedId);
            $customer = Customer::find($id);
            
            if (!$customer) {
                return redirect()->route('customers.index')->with('error', 'Customer not found.');
            }
            
            return view('backend.customer.edit', compact('customer'));
        } catch (\Exception $e) {
            return redirect()->route('customers.index')->with('error', 'Invalid customer ID.');
        }
    }// End Method

    public function update(Request $request, $encryptedId){ 
        try {
            $id = Crypt::decrypt($encryptedId);
            $customer = Customer::find($id);
            
            if (!$customer) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Customer not found.',
                ]);
            }

            $validatedData = $request->validate([
                'name' => 'required|string|max:255|unique:customers,name,' . $id,
                'mobile_no' => 'required|string|max:15|unique:customers,mobile_no,' . $id,
                'email' => 'nullable|email|max:255|unique:customers,email,' . $id,
                'address' => 'nullable|string|max:500',
            ], [
                'name.required' => 'Please enter a customer name',
                'name.unique' => 'This customer name already exists',
                'mobile_no.required' => 'Please enter a mobile number',
                'mobile_no.unique' => 'This mobile number already exists',
                'email.email' => 'Please enter a valid email address',
                'email.unique' => 'This email address already exists',
            ]);

            try {
                DB::transaction(function () use ($validatedData, $customer) {
                    $updateData = [
                        'name' => $validatedData['name'],
                        'mobile_no' => $validatedData['mobile_no'],
                        'email' => $validatedData['email'],
                        'address' => $validatedData['address'],
                        'updated_by' => Auth::id(),
                    ];
                    
                    $customer->update($updateData);
                });

                return response()->json([
                    'status' => 200,
                    'message' => "Customer '{$validatedData['name']}' was successfully updated",
                    'redirect' => route('customers.index')
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
                'message' => 'Invalid customer ID.',
            ]);
        }
    }// End Method

    public function destroy($encryptedId){
        try {
            $id = Crypt::decrypt($encryptedId);
            $customer = Customer::find($id);

            if (!$customer) {
                $message = 'Customer not found.';
                if (request()->expectsJson() || request()->ajax()) {
                    return response()->json(['status' => 404, 'message' => $message], 404);
                }
                return redirect()->route('customers.index')->with(['message' => $message, 'alert-type' => 'error']);
            }

            if ($customer->payments()->count() > 0) {
                $message = 'Cannot delete customer with associated Sale or payment records.';
                if (request()->expectsJson() || request()->ajax()) {
                    return response()->json(['status' => 422, 'message' => $message], 422);
                }
                return redirect()->route('customers.index')->with(['message' => $message, 'alert-type' => 'error']);
            }

            $customerName = $customer->name;

            DB::transaction(function () use ($customer) {
                $customer->delete();
            });

            $message = "Customer '{$customerName}' was successfully deleted.";

            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'status' => 200,
                    'message' => $message,
                ]);
            }

            return redirect()->route('customers.index')->with([
                'message' => $message,
                'alert-type' => 'success',
            ]);

        } catch (\Exception $e) {
            $message = 'Failed to delete customer.';

            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'status' => 400,
                    'message' => $message,
                ], 400);
            }

            return redirect()->route('customers.index')->with([
                'message' => $message,
                'alert-type' => 'error',
            ]);
        }
    } // End Method


    public function CreditCustomer(CreditSalesDataTable $dataTable){
        $payments = Payment::with(['customer:id,name', 'sale:id,sale_no'])
            ->visibleToSalesViewer()
            ->where('due_amount', '>', 0)
            ->get();

        $sales = $payments->pluck('sale')->filter()->unique('id')->sortBy('sale_no')->values();
        $customers = $payments->pluck('customer')->filter()->unique('id')->sortBy('name')->values();

        return $dataTable->render('backend.customer.customer_credit', compact('sales', 'customers'));
    } // End Method

    public function CustomerEditSale($sale_id){
        $payment = Payment::with(['customer', 'sale.sale_details.product', 'sale.sale_details.category'])
            ->visibleToSalesViewer()
            ->where('sale_id',$sale_id)
            ->firstOrFail();
        return view('backend.customer.edit_customer_sale',compact('payment'));
    }// End Method

    public function CustomerUpdateSale(Request $request,$sale_id){
        $request->validate([
            'date' => ['required', 'date'],
            'paid_status' => ['required', Rule::in([
                PaymentStatus::FULL_PAYMENT,
                PaymentStatus::PARTIAL_PAYMENT,
            ])],
            'paid_amount' => ['nullable', 'numeric', 'min:0.01'],
        ]);

        $payment = Payment::visibleToSalesViewer()->where('sale_id',$sale_id)->firstOrFail();
        $currentDue = round((float) $payment->due_amount, 2);

        if ($currentDue <= 0) {
            return redirect()->back()->with([
                'message' => 'This sale is already fully paid.',
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

        DB::transaction(function () use ($payment, $sale_id, $request, $paymentReceived) {
            $newPaidAmount = round(((float) $payment->paid_amount) + $paymentReceived, 2);
            $newDueAmount = round(max(0, ((float) $payment->total_amount) - $newPaidAmount), 2);

            $payment->paid_amount = $newPaidAmount;
            $payment->due_amount = $newDueAmount;
            $payment->paid_status = $newDueAmount <= 0
                ? PaymentStatus::FULL_PAYMENT
                : PaymentStatus::PARTIAL_PAYMENT;
            $payment->save();

            $paymentDetails = new PaymentDetail();
            $paymentDetails->sale_id = $sale_id;
            $paymentDetails->date = date('Y-m-d',strtotime($request->date));
            $paymentDetails->current_paid_amount = $paymentReceived;
            $paymentDetails->updated_by = Auth::id();
            $paymentDetails->save();

            app(AccountingIntegrationService::class)->postCustomerReceipt($payment, $paymentDetails, Auth::id());
        });

        $notification = array(
            'message' => 'Sale Update Successfully',
            'alert-type' => 'success'
        );
        return redirect()->route('credit.customer')->with($notification);
    }// End Method

    public function PaidCustomer(CashSalesDataTable $dataTable){
        $payments = Payment::with(['customer:id,name', 'sale:id,sale_no'])
            ->visibleToSalesViewer()
            ->where('due_amount', '<=', 0)
            ->get();

        $sales = $payments->pluck('sale')->filter()->unique('id')->sortBy('sale_no')->values();
        $customers = $payments->pluck('customer')->filter()->unique('id')->sortBy('name')->values();

        return $dataTable->render('backend.customer.customer_paid', compact('sales', 'customers'));
    }// End Method

}

