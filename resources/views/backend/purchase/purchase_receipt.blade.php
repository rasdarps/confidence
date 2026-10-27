@extends('layout.main')
@section('title') {{'Purchase Receipt'}} @endsection

@section('content')
 <script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>

<div class="page-content">
    <div class="container-fluid">
        
            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Print Supplier Receipt </h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript: void(0);"> </a></li>
                                <li class="breadcrumb-item active">Supplier Receipt</li>
                            </ol>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->
        <div id="invoiceArea">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                    <div class="card-body">
            
                    <div class="row">
                        <div class="col-12">
                            <div class="purchase-title">
                                <h4 class="float-end font-size-16"><strong>Purchase No # {{ $purchase->purchase_no }}</strong></h4>

                                 @php
                                $purchase_payment = App\Models\PurchasePayment::where('purchase_id',$purchase->id)->first();
                                @endphp  
                                <h3>
                                    <img src="{{ asset('images/logo_cart.png') }}" alt="logo" height="24"/> {{ ucfirst($purchase_payment['supplier']['name']) }} - Purchase Sale
                                </h3>
                            </div>
                            <hr> 

                            <div class="row">
                                <div class="col-12">
                                        <div>
                                            <div class="col-6 mt-4">
                                                <h3 class="font-size-16"><strong>Supplier Details</strong></h3>
                                                <address>
                                                    <strong>{{ ucfirst($purchase_payment['supplier']['name']) }}</strong><br>
                                                    Mobile: {{ $purchase_payment['supplier']['mobile_no']  }}<br>
                                                    Address: {{ $purchase_payment['supplier']['address']  }}
                                                </address>
                                            </div>

                                        </div>

                                    </div>
                            </div> <!-- end row -->
                            
                            <div class="row">
                                <div class="col-6 mt-4">
                                    <h3 class="font-size-16"><strong>Company Details</strong></h3>
                                    <address>
                                        <strong>{{ $companyInfo->name }}</strong>
                                        <br>
                                        {{ $companyInfo->address }}
                                        <br>
                                        {{ $companyInfo->phone }} | {{ $companyInfo->email }}
                                    </address>
                                </div>
                                <div class="col-6 mt-4 text-end">
                                    <address>
                                        <strong>purchase Date:</strong><br>
                                        {{ date('d-m-Y',strtotime($purchase->date)) }} <br><br>
                                    </address>
                                </div>
                            </div>
                        </div>
                    </div>

                    
                <div class="row">
                    <div class="col-12">
                        <div>
                            <div class="">
                                <div class="table-responsive">
                                    <table class="table bodered">
                                        <thead>
                                        <tr>
                                            <td><strong>Sl </strong></td>
                                            <td class="text-center"><strong>Category</strong></td>
                                            <td class="text-center"><strong>Product Name</strong>
                                            </td>
                                        
                                            <td class="text-center"><strong>Quantity</strong>
                                            </td>
                                            <td class="text-center"><strong>Unit Price (Gh¢) </strong>
                                            </td>
                                            <td class="text-end"><strong>Total Price (Gh¢)</strong>
                                            </td>
                                            
                                        </tr>
                                        </thead>
                                        <tbody>
                                            <!-- foreach ($order->lineItems as $line) or some such thing here -->
                            
                                            @php
                                            $total_sum = '0';
                                            @endphp
                                            @foreach($purchase['purchase_details'] as $key => $details)
                                            <tr>
                                            <td class="text-center">{{ $key+1 }}</td>
                                                <td class="text-center">{{ $details['category']['name'] }}</td>
                                                <td class="text-center">{{ $details['product']['name'] }}</td>
                                                
                                                <td class="text-center">{{ $details->buying_qty }}</td>
                                                <td class="text-center">{{ $details->unit_cost_price }}</td>
                                                <td class="text-end">{{ number_format($details->buying_price, 2) }}</td>
                                                
                                            </tr>
                                            @php
                                            $total_sum += $details->buying_price;
                                            @endphp
                                            @endforeach
                                            <tr>
                                                
                                                <td class="thick-line"></td>
                                                <td class="thick-line"></td>
                                                <td class="thick-line"></td>
                                                <td class="thick-line"></td>
                                                <td class="thick-line text-center">
                                                    <strong>Subtotal</strong></td>
                                                <td class="thick-line text-end">{{ number_format($total_sum, 2) }}</td>
                                            </tr>
                                            <tr>
                                                
                                                <td class="no-line"></td>
                                                <td class="no-line"></td>
                                                <td class="no-line"></td>
                                                <td class="no-line"></td>
                                                <td class="no-line text-center">
                                                    <strong>Discount Amount</strong></td>
                                                <td class="no-line text-end">{{ number_format($purchase_payment->discount_amount, 2) }}</td>
                                            </tr>
                                            <tr>
                                                
                                                <td class="no-line"></td>
                                                <td class="no-line"></td>
                                                <td class="no-line"></td>
                                                <td class="no-line"></td>
                                                <td class="no-line text-center">
                                                    <strong>Paid Amount</strong></td>
                                                <td class="no-line text-end">{{ number_format($purchase_payment->paid_amount, 2) }}</td>
                                            </tr>

                                            <tr>
                                                
                                                <td class="no-line"></td>
                                                <td class="no-line"></td>
                                                <td class="no-line"></td>
                                                <td class="no-line"></td>
                                                <td class="no-line text-center">
                                                    <strong>Due Amount</strong></td>
                                                <td class="no-line text-end">{{ number_format($purchase_payment->due_amount, 2) }}</td>
                                            </tr>
                                            <tr>
                                                
                                                <td class="no-line"></td>
                                                <td class="no-line"></td>
                                                <td class="no-line"></td>
                                                <td class="no-line"></td>
                                                <td class="no-line text-center">
                                                    <strong>Grand Amount</strong></td>
                                                <td class="no-line text-end"><h4 class="m-0">Gh¢{{ number_format($purchase_payment->total_amount, 2) }}</h4></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    </div>
                                    <div class="d-print-none">
                                        <div class="float-end">
                                            @can('purchase-print')
                                                <a href="javascript:window.print()" class="btn btn-success waves-effect waves-light"><i class="fa fa-print"></i></a>
                                            @endcan
                                            @can('purchase-print')
                                                <a href="javascript:void(0)" onclick="downloadPDF()" class="btn btn-primary waves-effect waves-light ms-2">
                                                    <i class="fa fa-download"></i> Download
                                                </a>
                                            @endcan
                                            @can('purchase-create')
                                                <a href="{{ route('purchases.create') }}"  class="btn btn-info waves-effect waves-light ms-2">
                                                    <i class="fa fa-plus"></i> New Purchase
                                                </a>
                                            @endcan
                                        </div>
                                    </div>
                                    </div>
                                </div>
                    </div>
                    </div> <!-- end row -->

                        </div>
                    </div>
                </div> <!-- end col -->
            </div> <!-- end row -->
        </div> <!-- print area ends -->
    </div> <!-- container-fluid -->
</div> <!-- page content-->


@endsection
