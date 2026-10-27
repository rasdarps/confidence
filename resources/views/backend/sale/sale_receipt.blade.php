@extends('layout.main')
@section('title') {{'Sale Receipt'}} @endsection

@section('content')
 <script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>

<div class="page-content">
    <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Print Sale Receipt </h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript: void(0);"> </a></li>
                                <li class="breadcrumb-item active">Sale Receipt</li>
                            </ol>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->
        <div class="receipt-container" id="invoiceArea">

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="invoice-title" align="center">
                                         @php
                                                          $viewerIsSuperAdmin = auth()->check() && strtolower((string) (auth()->user()->user_type ?? '')) === 'super admin';
                                                          $cashierUser = $Sale->createdBy;
                                                          $maskCashier = !$viewerIsSuperAdmin && $cashierUser && strtolower((string) ($cashierUser->user_type ?? '')) === 'super admin';
                                                                             $cashierUsername = $maskCashier ? null : optional($cashierUser)->username;
                                                          $cashierDisplay = $cashierUsername ?: 'N/A';
                                            @endphp

                                        <h5 class="receipt-header-line">
                                            Sales Receipt No. # {{ $Sale->sale_no }}
                                        </h5>
                                        @if(($printMode ?? 'original') === 'reprint')
                                            <h5 class="receipt-header-line">MANAGER REPRINT</h5>
                                        @endif
                                        <h5 class="receipt-header-line">{{ $companyInfo->name }}
                                            <br>
                                        {{ $companyInfo->address }}
                                        <br>
                                        {{ $companyInfo->phone }} |  TIN: {{ $companyInfo->tin_number ?? 'N/A' }}</h5>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-12">
                                               <p>
                                                {{ $cashierDisplay }} | {{ optional($Sale->created_at)->format('d-m-Y h:i A') }}
                                               </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        @php
                        $payment = App\Models\Payment::where('sale_id',$Sale->id)->first();
                        $discountAmount = $payment->discount_amount ?? 0;
                        $taxableAmount = $payment->taxable_amount ?? 0;
                        $nhilAmount = $payment->nhil_amount ?? 0;
                        $getfundAmount = $payment->getfund_amount ?? 0;
                        $vatAmount = $payment->vat_amount ?? 0;
                        $paidAmount = $payment->paid_amount ?? 0;
                        $totalAmount = $payment->total_amount ?? 0;
                        $cashTendered = $payment->cash_tendered ?? 0;
                        $changeDue = $payment->change_due ?? 0;
                        @endphp

                <div class="row">
                    <div class="col-md-12">
                        <div>
                            <div class="">
                                <div class="receipt-lines">
                                    <div class="receipt-line receipt-line-header">
                                        <div class="receipt-line-sn text-center"><strong>#</strong></div>
                                        <div class="receipt-line-item-name"><strong>Item</strong></div>
                                        <div class="receipt-line-qty text-center"><strong>QTY</strong></div>
                                        <div class="receipt-line-price text-end"><strong>Price</strong></div>
                                        <div class="receipt-line-total text-end"><strong>Total</strong></div>
                                    </div>

                                    @php
                                    $total_sum = 0;
                                    @endphp

                                    @foreach($Sale['sale_details'] as $details)
                                        <div class="receipt-line">
                                            <div class="receipt-line-sn text-center">{{ $loop->iteration }}</div>
                                            <div class="receipt-line-item-name">
                                                <span class="item-name">{{ $details['product']['name'] }}</span>
                                            </div>
                                            <div class="receipt-line-qty text-center">{{ rtrim(rtrim(number_format($details->selling_qty, 2, '.', ''), '0'), '.') }}</div>
                                            <div class="receipt-line-price text-end">{{ rtrim(rtrim(number_format($details->unit_selling_price, 2, '.', ''), '0'), '.') }}</div>
                                            <div class="receipt-line-total text-end">{{ number_format($details->selling_price, 2) }}</div>
                                        </div>
                                        @php
                                        $total_sum += $details->selling_price;
                                        @endphp
                                    @endforeach
                                </div>

                                <div class="receipt-summary">
                                    <div class="receipt-summary-row receipt-summary-lined">
                                        <div class="receipt-summary-label">Subtotal</div>
                                        <div class="receipt-summary-value text-end">{{ number_format($total_sum, 2) }}</div>
                                    </div>
                                    <div class="receipt-summary-row receipt-summary-lined">
                                        <div class="receipt-summary-label">Discount Amount</div>
                                        <div class="receipt-summary-value text-end">{{ number_format($discountAmount, 2) }}</div>
                                    </div>
                                    <div class="receipt-summary-row receipt-summary-lined">
                                        <div class="receipt-summary-label">Taxable Subtotal</div>
                                        <div class="receipt-summary-value text-end">{{ number_format($taxableAmount, 2) }}</div>
                                    </div>
                                    <div class="receipt-summary-row receipt-summary-lined">
                                        <div class="receipt-summary-label">NHIL (2.5%)</div>
                                        <div class="receipt-summary-value text-end">{{ number_format($nhilAmount, 2) }}</div>
                                    </div>
                                    <div class="receipt-summary-row receipt-summary-lined">
                                        <div class="receipt-summary-label">GETFund (2.5%)</div>
                                        <div class="receipt-summary-value text-end">{{ number_format($getfundAmount, 2) }}</div>
                                    </div>
                                    <div class="receipt-summary-row receipt-summary-lined">
                                        <div class="receipt-summary-label">VAT (15%)</div>
                                        <div class="receipt-summary-value text-end">{{ number_format($vatAmount, 2) }}</div>
                                    </div>
                                    <div class="receipt-summary-row receipt-summary-lined">
                                        <div class="receipt-summary-label">Paid Amt</div>
                                        <div class="receipt-summary-value text-end">{{ number_format($paidAmount, 2) }}</div>
                                    </div>
                                    <div class="receipt-summary-row receipt-summary-lined">
                                        <div class="receipt-summary-label">Grand Total</div>
                                        <div class="receipt-summary-value text-end">Gh¢ {{ number_format($totalAmount, 2) }}</div>
                                    </div>
                                    <div class="receipt-summary-row receipt-summary-lined">
                                        <div class="receipt-summary-label">Cash Tendered</div>
                                        <div class="receipt-summary-value text-end summary-last">{{ number_format($cashTendered, 2) }}</div>
                                    </div>
                                    <div class="receipt-summary-row">
                                        <div class="receipt-summary-label">Change Due</div>
                                        <div class="receipt-summary-value text-end summary-last">{{ number_format($changeDue, 2) }}</div>
                                    </div>
                                    <div class="receipt-summary-spacer">&nbsp;</div>
                                </div>

                                <div class="text-center mt-2">
                                    <div><strong>Delivery Verification Code</strong></div>
                                    <svg id="deliveryCodeBarcode" aria-label="Delivery verification barcode"></svg>
                                    <div>{{ $Sale->delivery_code }}</div>
                                </div>

                                <div class="d-print-none">
                                    <div class="alert alert-info py-2 mt-3">
                                        This records receipt authorization before the print dialog; it cannot confirm that paper printed.
                                    </div>
                                    <div class="float-end">
                                        @can('sale-create')
                                            <a href="{{ route('sales.create') }}" class="btn btn-info waves-effect waves-light ms-2">
                                                <i class="fa fa-plus"></i> New Sales
                                            </a>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div> <!-- end row -->
                    </div>
                </div> <!-- end col -->
            </div> <!-- end row -->
        </div> <!-- print area ends -->
    </div> <!-- container-fluid -->
</div> <!-- page content-->

@endsection

@section('styles')
    <style>
        .receipt-lines,
        .receipt-summary {
            width: 100%;
        }

        #deliveryCodeBarcode {
            max-width: 100%;
        }

        .receipt-line {
            display: grid;
            grid-template-columns: 30px minmax(0, 1fr) 42px 72px 80px;
            column-gap: 6px;
            align-items: start;
            width: 100%;
        }

        .receipt-line + .receipt-line {
            margin-top: 4px;
        }

        .receipt-lines .receipt-line {
            border-bottom: 1px solid #777;
            padding-bottom: 2px;
        }

        .receipt-line-header {
            margin-bottom: 6px;
            padding-bottom: 4px;
            border-bottom: 1px solid #777;
        }

        .receipt-line-item-name,
        .receipt-line-sn,
        .receipt-line-qty,
        .receipt-line-price,
        .receipt-line-total,
        .receipt-summary-label,
        .receipt-summary-value {
            min-width: 0;
        }

        .receipt-summary {
            margin-top: 8px;
        }

        .receipt-summary-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 110px;
            column-gap: 8px;
            align-items: center;
            width: 100%;
        }

        .receipt-summary-row + .receipt-summary-row {
            margin-top: 2px;
        }

        .receipt-summary-lined {
            border-bottom: 1px solid #777;
            padding-bottom: 2px;
        }

        .receipt-summary-spacer {
            line-height: 6px;
            font-size: 6px;
            margin-top: 2px;
        }

        .item-name {
            display: block;
            width: 100%;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        /* Thermal receipt print profile for XP80C 80mm roll. */
        @media print {
            html,
            body {
                width: 80mm !important;
                height: auto !important;
                overflow: visible !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            body {
                margin: 0;
                padding: 0;
                font-family: Arial, Helvetica, sans-serif;
                font-size: 19px;
                line-height: 1.35;
                color: #000;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print, .btn, .modal_btn,
            .topbar, .vertical-menu, .main-footer, .page-title-box, .breadcrumb {
                display: none !important;
            }

            .main-content,
            .page-content,
            .container-fluid,
            .row,
            .col-12,
            .col-md-12,
            .card,
            .card-body,
            .receipt-container {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                max-width: none !important;
                border: 0 !important;
                box-shadow: none !important;
                margin-left: 2px !important;
            }

            .receipt-container {
                width: 100% !important;
                min-width: 0 !important;
            }

            /* Print only the receipt block to avoid blank pages from hidden layout wrappers. */
            body * {
                visibility: hidden;
            }

            #invoiceArea,
            #invoiceArea * {
                visibility: visible;
            }

            #invoiceArea {
                position: static !important;
                left: auto !important;
                top: auto !important;
                display: block !important;
                width: 79mm !important;
                max-width: 79mm !important;
                margin: 0 !important;
                padding: 0 !important;
                box-sizing: border-box;
                z-index: auto !important;
                overflow: visible !important;
                height: auto !important;
            }

            .receipt-lines,
            .receipt-summary {
                width: 79mm !important;
                max-width: 79mm !important;
            }

            h1, h2, h3, h4, h5 {
                margin: 0;
                font-size: 14px;
                text-align: center;
            }

            .invoice-title {
                width: 100% !important;
                margin: 0 auto 2mm auto !important;
                padding: 0 !important;
                text-align: center !important;
                margin-left: 30px !important;
            }

            .invoice-title h5 {
                margin: 0 0 1mm 0 !important;
                font-size: 16px !important;
                line-height: 1.25;
                text-align: center !important;
            }

            .invoice-title .receipt-header-line {
                display: block !important;
                width: 100% !important;
                max-width: 100% !important;
                box-sizing: border-box;
                white-space: normal !important;
                overflow-wrap: anywhere;
            }

            .invoice-title p {
                margin: 0 !important;
                font-size: 19px !important;
                line-height: 1.3;
                text-align: center !important;
            }

            .invoice-title p strong {
                font-size: 19px !important;
            }

            p, span, strong, div {
                font-size: 19px !important;
            }

            .receipt-line {
                display: flex !important;
                align-items: flex-start;
                gap: 0 !important;
            }

            .receipt-line-header {
                margin-bottom: 4px;
                padding-bottom: 2px;
            }

            .receipt-line-sn,
            .receipt-line-item-name,
            .receipt-line-qty,
            .receipt-line-price,
            .receipt-line-total {
                box-sizing: border-box;
            }

            .receipt-line-sn {
                flex: 0 0 8%;
                max-width: 8%;
                text-align: center;
                white-space: nowrap;
                font-size: 19px !important;
            }

            .receipt-line-item-name {
                flex: 0 0 42%;
                max-width: 42%;
                line-height: 1.25;
                padding-right: 1mm;
                overflow: hidden;
                word-break: break-word;
                overflow-wrap: anywhere;
            }

            .receipt-line-qty {
                flex: 0 0 12%;
                max-width: 12%;
                text-align: center;
                padding-right: 1.2mm;
            }

            .receipt-line-price {
                flex: 0 0 18%;
                max-width: 18%;
                text-align: right;
                padding-left: 1.8mm;
                padding-right: 1.4mm;
            }

            .receipt-line-total {
                flex: 0 0 20%;
                max-width: 20%;
                text-align: right;
                padding-left: 1.8mm;
            }

            .receipt-line-header strong {
                display: inline-block;
                white-space: nowrap;
                letter-spacing: 0.35px;
            }

            .receipt-line-qty,
            .receipt-line-price,
            .receipt-line-total,
            .receipt-summary-value {
                white-space: nowrap;
                word-break: keep-all;
                font-size: 19px !important;
                letter-spacing: 0;
                overflow: visible !important;
            }

            .receipt-summary {
                margin-top: 6px;
            }

            .receipt-summary-row {
                grid-template-columns: minmax(0, calc(100% - 84px)) 84px;
                column-gap: 0;
            }

            .receipt-line-header strong {
                font-size: 18px !important;
            }

            .summary-last {
                font-size: 19px !important;
            }

            small {
                font-size: 19px !important;
            }

            .receipt-line,
            .receipt-summary-row {
                page-break-inside: avoid;
                break-inside: avoid;
            }

            @page {
                size: 80mm auto;
                margin: 0;
            }
        }
    </style>

@endsection

@section('scripts')
<script src="{{ asset('vendor/jsbarcode/JsBarcode.all-3.11.6.min.js') }}"></script>
<script>
    (function () {
        var originalTitle = document.title;
        var printRequested = false;
        var redirectStarted = false;
        var redirectUrl = '{{ route('sales.create') }}';

        function redirectToSaleCreate() {
            if (!printRequested || redirectStarted) return;
            redirectStarted = true;
            window.location.replace(redirectUrl);
        }

        function triggerPrint() {
            var invoiceArea = document.getElementById('invoiceArea');

            // Prevent sending an empty document to the print spooler.
            if (!invoiceArea || !invoiceArea.innerText || !invoiceArea.innerText.trim()) {
                return;
            }

            printRequested = true;
            window.setTimeout(redirectToSaleCreate, 3000);
            window.focus();
            document.title = '';
            window.print();
            document.title = originalTitle;
        }

        window.printInvoiceAndRedirect = triggerPrint;

        window.addEventListener('afterprint', redirectToSaleCreate);
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                printRequested = true;
                redirectToSaleCreate();
            }
        });

        window.addEventListener('load', function () {
            if (window.JsBarcode) {
                JsBarcode('#deliveryCodeBarcode', @json($Sale->delivery_code), {
                    format: 'CODE128',
                    displayValue: false,
                    width: 1,
                    height: 45,
                    margin: 0,
                });
            }
            // Give the browser a short render window before opening print dialog.
            setTimeout(triggerPrint, 250);
        });
    })();
</script>

@endsection
