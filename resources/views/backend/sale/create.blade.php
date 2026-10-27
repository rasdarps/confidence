@extends('layout.main')
@section('title') {{'Create Sales Sale'}} @endsection

@section('content')
 <script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>

    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card" style="margin-top: 5px;">
                        <div class="card-header with-border">
                            <span class="card-title mobile-page-title" style="font-size:20px;">Create | Sales Sale</span>
                            <a href="{{route('sales.index')}}" class="mobile-header-action" style="float:right">
                                <button type="button" class="btn btn-dark modal_btn mobile-header-btn">
                                    View Sales sales
                                </button>
                            </a>
                            <hr>
                            <div class="card-body">
                                {{-- backend error alerts --}}
                                <div class="alert alert-danger alert-dismissible fade show" id="errorAlert" style="display: none;">
                                    <button type="button" class="close h-100" data-dismiss="alert" aria-label="Close">
                                        <span><i class="mdi mdi-close"></i></span>
                                    </button>
                                    <ul id="errors"></ul>
                                </div>
                                <form method="post" action="{{ route('sales.store') }}" id="invoiceForm" novalidate>
                                    @csrf
                                    <div class="sale-section-panel">
                                        <div class="row mb-2 align-items-center">
                                            {{-- barcode field for product lookup --}}
                                            <div class="col-md-3 d-flex align-items-center mb-2 mb-md-0">
                                                <div class="form-group mb-1 w-100">
                                                    <input type="text" id="barcode" class="form-control" placeholder="Scan Barcode" autofocus>
                                                </div>
                                            </div>

                                            <div class="col-md-2 d-flex align-items-center mb-2 mb-md-0">
                                                <div class="form-group mb-1 w-100">
                                                    <div class="d-flex align-items-center camera-scan-actions">
                                                        <button type="button" id="startCameraScan" class="btn btn-outline-primary camera-scan-btn">
                                                            <i class="fas fa-camera mr-1"></i> Scan
                                                        </button>
                                                        <button type="button" id="stopCameraScan" class="btn btn-outline-secondary ml-2 w-100 camera-scan-btn" style="display:none;">Stop Camera</button>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-2 d-flex align-items-center mb-2 mb-md-0">
                                                <input class="form-control" name="sale_no" type="text" value="{{ $sale_no }}" id="sale_no" readonly style="background-color:#ddd;" placeholder="Sale #">
                                            </div>

                                            <div class="col-md-2 d-flex align-items-center mb-2 mb-md-0">
                                                <input class="form-control" name="current_stock_qty" type="text" id="current_stock_qty" readonly style="background-color:#ddd" placeholder="Current Stock">
                                            </div>

                                            <div class="col-md-3 d-flex align-items-center justify-content-end mb-2 mb-md-0">
                                                <input class="form-control" value="{{ $date }}" name="date" type="date" id="date" {{ !empty($preventBackdatedInvoices) ? 'min='.$date : '' }}>
                                            </div>
                                        </div>

                                        <div class="row mb-2">
                                            <div class="col-6">
                                                <div id="cameraScanWrap" class="mb-1" style="display:none;">
                                                    <video id="barcodeCameraPreview" autoplay playsinline muted style="width:100%;max-height:220px;border:1px solid #ddd;border-radius:4px;background:#111;"></video>
                                                    <small class="text-muted d-block mt-1">Point camera at barcode. Works best over HTTPS on phone and desktop browsers.</small>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row align-items-end mb-2">
                                            <div class="col-md-5">
                                                <div class="form-group mb-1 d-flex align-items-center">
                                                    <select name="product_id" id="product_id" class="form-control select2" aria-label="Select product">
                                                        <option disabled selected value="">Select Product</option>
                                                        @foreach($products as $product)
                                                            <option value="{{ $product->id }}"
                                                                data-category-id="{{ $product->category_id }}"
                                                                data-category-name="{{ $product->category->name ?? '' }}"
                                                                data-unit-id="{{ $product->unit_id }}"
                                                                data-unit-name="{{ $product->unit->name ?? '' }}"
                                                                data-price="{{ (float) ($product->selling_price ?? 0) }}"
                                                                data-qty="{{ (float) ($product->quantity ?? 0) }}"
                                                                data-barcode="{{ $product->barcode ?? '' }}">
                                                                {{ $product->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-group mb-1 d-flex align-items-center">
                                                    <select name="category_id" id="category_id" class="form-control select2" aria-label="Selected category" disabled>
                                                        <option disabled selected value="">Select Category</option>
                                                        @foreach($category as $cat)
                                                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group mb-1 d-flex align-items-center">
                                                    <select name="unit_id" id="unit_id" class="form-control select2" aria-label="Selected unit" disabled>
                                                        <option disabled selected value="">Select Unit</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row mb-2">
                                            <div class="col-12 text-center">
                                                <button type="button" class="btn btn-dark waves-effect waves-light fas fa-plus-circle addeventmore mobile-action-btn"> Add</button>
                                            </div>
                                        </div>
                                    {{-- <hr> --}}
                                    <div class="row">
                                        <div class="col-12">
                                                <div class="table-responsive mobile-item-table-wrap">
                                                <table class="table-sm table-bordered mobile-item-table" width="100%" style="border-color: #ddd;">
                                                <thead>
                                                    <tr style="background-color:#111827; text-align:center;" class="text-white">  
                                                        <th width="10%">Category</th>
                                                        <th width="12%">Barcode</th>
                                                        <th width="15%">Name</th>
                                                        <th width="10%">Qty</th>
                                                        <th width="15%">Selling Price</th>
                                                        <th width="10%">Per</th>
                                                        <th width="15%">Total (Ghc)</th>
                                                        <th width="7%">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="addRow" class="addRow"></tbody>
                                                <tbody>
                                                    <tr>
                                                        <td colspan="6">Discount</td>
                                                        <td>
                                                            <input type="text" name="discount_amount" id="discount_amount" class="form-control estimated_amount" placeholder="Discount Amount" value="0" {{ empty($allowManualDiscountEntryByStaff) ? 'readonly' : '' }}>
                                                            @if (empty($allowManualDiscountEntryByStaff))
                                                                <small class="text-muted">Manual discount entry is disabled by settings.</small>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="6">Taxable Subtotal</td>
                                                        <td>
                                                            <input style="text-align: right;" type="text" name="taxable_amount" value="0.00" id="taxable_amount" class="form-control" readonly style="background-color: #ddd;">
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="6">NHIL (2.5%)</td>
                                                        <td>
                                                            <input style="text-align: right;" type="text" name="nhil_amount" value="0.00" id="nhil_amount" class="form-control" readonly style="background-color: #ddd;">
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="6">GETFund (2.5%)</td>
                                                        <td>
                                                            <input style="text-align: right;" type="text" name="getfund_amount" value="0.00" id="getfund_amount" class="form-control" readonly style="background-color: #ddd;">
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="6">VAT (15%)</td>
                                                        <td>
                                                            <input style="text-align: right;" type="text" name="vat_amount" value="0.00" id="vat_amount" class="form-control" readonly style="background-color: #ddd;">
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="6">Grand Total</td>
                                                        <td>
                                                            <input style="text-align: right;" type="text" name="estimated_amount" value="0.00" id="estimated_amount" class="form-control estimated_amount" readonly style="background-color: #ddd;">
                                                        </td>
                                                        <td></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            </div>
                                        </div>
                                    </div>
                                    </div>
                                    <div class="payment-selection-panel mt-3">
                                        <div class="payment-selection-title">Payment and Customer Selection</div>
                                        <div class="payment-selection-subtitle">Choose payment type first, then assign the right account for this sale.</div>
                                        <div class="row mt-3">
                                            <div class="form-group col-md-4">
                                                <label><strong>Paid Status <span class="text-danger">*</span></strong></label>
                                                <select name="paid_status" id="paid_status" class="form-control payment-control">
                                                    <option value="" selected disabled>Select Payment Type</option>
                                                    <option value="partial_payment">Partial Payment</option>
                                                    <option value="full_payment">Full Payment</option>
                                                    <option value="full_credit">Full Credit</option>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-8">
                                                <label><strong>Account Name <span class="text-danger">*</span></strong></label>
                                                <select name="customer_id" id="customer_id" class="form-control payment-control">
                                                    <option value="" selected disabled>Select Customer</option>
                                                    @foreach($costomer as $cust)
                                                        <option value="{{ $cust->id }}" 
                                                            data-is-cash="{{ strtolower($cust->name) === 'cash_sales' ? '1' : '0' }}">
                                                            {{ $cust->name }}
                                                        </option>
                                                    @endforeach
                                                    <option value="0">Click to Create new customer</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div id="payment_amount_fields" class="payment-details-grid" style="display:none;">
                                            <div id="paid_amount_group" class="payment-detail-field" style="display:none;">
                                                <label for="paid_amount">Paid Amount <span class="text-danger">*</span></label>
                                                <input type="text" name="paid_amount" id="paid_amount" class="form-control paid_amount paid-amount-input payment-money-input" placeholder="Enter paid amount" inputmode="decimal" autocomplete="off">
                                            </div>
                                            <div id="cash_tendered_group" class="payment-detail-field">
                                                <label for="cash_tendered">Cash Tendered <span id="cash_tendered_required" class="text-danger" style="display:none;">*</span></label>
                                                <input type="number" step="0.01" min="0" name="cash_tendered" value="" id="cash_tendered" class="form-control payment-money-input" placeholder="Enter cash tendered">
                                            </div>
                                            <div id="change_due_group" class="payment-detail-field">
                                                <label for="change_due">Change Due</label>
                                                <input type="text" name="change_due" value="0.00" id="change_due" class="form-control payment-money-input payment-change-input" readonly>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Hidden Add Customer Form -->
                                    <div class="row new_customer mt-3" style="display:none;">
                                        <div class="form-group col-md-4">
                                            <input type="text" name="name" id="name" class="form-control" placeholder="Customer Name">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <input type="text" name="mobile_no" id="mobile_no" class="form-control" placeholder="Customer Mobile No" minlength="10" maxlength="10" onkeypress="return isNumberKey(event)">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <input type="text" name="email" id="email" class="form-control" placeholder="Customer Email">
                                        </div>
                                    </div>
                                    <div class="sale-section-panel sale-description-panel mt-3">
                                        <div class="form-group mb-0">
                                            <textarea name="description" class="form-control" id="description" placeholder="Write Description Here"></textarea>
                                        </div>
                                    </div>
                                    <div class="form-group mt-4">
                                        <center>
                                            <button type="submit" class="btn btn-success storeButton mobile-submit-btn" id="storeButton">Submit Sale</button>
                                        </center>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

@endsection

@section('styles')
    <style>
        .block_div {
            display: none;
           
        }
        .card {
            box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease-in-out;
            border-radius: 15px;
            overflow: hidden;
            transform: translateY(0);
        }
        .card:hover {
            box-shadow: 0 8px 25px 0 rgba(0, 0, 0, 0.3);
            transform: translateY(-5px);
            border-radius: 20px;
        }
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s;
            z-index: 1;
        }
        .card:hover::before {
            left: 100%;
        }
        .card-body {
            position: relative;
            z-index: 2;
        }
        .card {
            background: linear-gradient(145deg, #ffffff, #f0f0f0);
            border: none;
        }
        .card:hover {
            background: linear-gradient(145deg, #f8f9fa, #e9ecef);
        }
        .is-invalid {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
        .invalid-feedback {
            display: block;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 0.875em;
            color: #dc3545;
        }
        .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        .form-control.is-invalid:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
        .select2 {
            width: 100% !important;
        }

        .camera-scan-btn {
            border-color: #0f766e;
            color: #0f766e;
        }

        .sale-section-panel,
        .payment-selection-panel {
            border: 1px solid #cbd5e1;
            background: linear-gradient(135deg, #f8fafc, #eef2ff);
            border-radius: 12px;
            padding: 14px;
        }

        .sale-description-panel textarea {
            background-color: #fff;
        }

        .payment-selection-title {
            font-weight: 700;
            color: #0f172a;
            letter-spacing: 0.2px;
            font-size: 15px;
        }

        .payment-selection-subtitle {
            color: #475569;
            font-size: 12px;
            margin-top: 2px;
        }

        .payment-control {
            border: 1px solid #94a3b8;
            background-color: #ffffff;
        }

        .payment-details-grid {
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1px solid #cbd5e1;
        }

        .payment-detail-field label {
            display: block;
            margin-bottom: 6px;
            color: #334155;
            font-size: 13px;
            font-weight: 700;
        }

        #paid_amount_group {
            width: min(100%, 360px);
        }

        .payment-money-input {
            min-height: 44px;
            border: 1px solid #94a3b8;
            background: #fff;
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .payment-change-input {
            background: #e8f5f2 !important;
            color: #0f766e;
            font-weight: 700;
        }

        .camera-scan-btn:hover,
        .camera-scan-btn:focus,
        .camera-scan-btn:active {
            background-color: #0f766e !important;
            border-color: #0f766e !important;
            color: #ffffff !important;
        }

        @media (max-width: 767.98px) {
            .payment-details-grid {
                grid-template-columns: 1fr;
            }

            #paid_amount_group {
                width: 100%;
            }

            .mobile-page-title {
                display: block;
                margin-bottom: 10px;
            }

            .mobile-header-action {
                float: none !important;
                display: block;
                width: 100%;
            }

            .mobile-header-btn,
            .mobile-action-btn,
            .mobile-submit-btn {
                width: 100%;
            }

            .mobile-item-table-wrap {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .mobile-item-table {
                min-width: 860px;
            }

            .camera-scan-actions {
                flex-direction: column;
                align-items: stretch !important;
                gap: 6px;
            }

            .camera-scan-btn {
                width: 100%;
            }

            #stopCameraScan.camera-scan-btn {
                margin-left: 0 !important;
            }
        }

        @media (max-width: 575.98px) {
            .page-content .container-fluid {
                padding-left: 10px;
                padding-right: 10px;
            }

            .card-header.with-border {
                padding: 12px 12px 10px;
            }

            .card-body {
                padding: 10px;
            }

            .card-body .row.mb-2,
            .card-body .row.mt-3,
            .card-body .form-row.mt-3,
            .card-body .form-group.mt-4 {
                margin-top: 8px !important;
                margin-bottom: 8px !important;
            }

            .card-body .form-control,
            .card-body .btn,
            .card-body .select2-selection--single {
                min-height: 40px;
            }

            .mobile-item-table th,
            .mobile-item-table td {
                font-size: 12px;
                padding: 0.3rem 0.4rem;
            }

            #description {
                min-height: 88px;
            }
        }
    </style>
@endsection

@section('scripts')
    <script src="{{ asset('vendor/zxing/zxing-library-0.21.3.min.js') }}"></script>
    <script type="text/javascript">
        const ALLOW_NEGATIVE_STOCK = @json((bool) ($allowNegativeStock ?? false));
        const PREVENT_BACKDATED_INVOICES = @json((bool) ($preventBackdatedInvoices ?? false));
        const ALLOW_MANUAL_DISCOUNT_ENTRY_BY_STAFF = @json((bool) ($allowManualDiscountEntryByStaff ?? true));

        function parseMoney(value) {
            var amount = Number(String(value == null ? '' : value).replace(/,/g, '').trim());
            return Number.isFinite(amount) ? amount : 0;
        }

        $(document).ready(function (){
            // Handle form submission
            $('#invoiceForm').on('submit', function(e) {
                e.preventDefault();
                // Client-side validation (Notiflix style)
                function notify(msg) {
                    console.log('Validation Error:', msg);
                    if (typeof Notiflix !== 'undefined') {
                        Notiflix.Notify.failure(msg, {
                            timeout: 4000,
                            showOnlyTheLastOne: false,
                            position: 'right-top',
                        });
                    } else {
                        alert(msg);
                    }
                }

                if(!$('#date').val().trim()) {
                    notify('Please enter a date');
                    return;
                }

                if (PREVENT_BACKDATED_INVOICES) {
                    var selectedDate = new Date($('#date').val());
                    var today = new Date();
                    selectedDate.setHours(0, 0, 0, 0);
                    today.setHours(0, 0, 0, 0);
                    if (selectedDate < today) {
                        notify('Backdated invoices are disabled. Please use today\'s date.');
                        return;
                    }
                }
                var addedItemsCount = $('#addRow').children('tr').length;
                if (addedItemsCount === 0) {
                    if(!$('#product_id').val()) {
                        notify('Please select a product');
                        return;
                    }

                    notify('Please add at least one Sale item.');
                    return;
                }

                // Validate Sale item fields (selling_qty, unit_selling_price > 0)
                var validItems = true;
                var validationError = null;
                var firstInvalidField = null;
                
                $('#addRow tr').each(function(index) {
                    if (!validItems) return false;
                    
                    var $row = $(this);
                    var $qtyField = $row.find('input.selling_qty');
                    var qty = $qtyField.val();
                    var price = $row.find('input.unit_selling_price').val();
                    var availableQty = parseFloat($row.find('input.available_stock_qty').val()) || 0;
                    
                    if (!qty || isNaN(qty) || parseFloat(qty) <= 0) {
                        validationError = 'Each item must have a valid quantity greater than 0.';
                        firstInvalidField = $qtyField;
                        validItems = false;
                        return false;
                    }
                    
                    if (!ALLOW_NEGATIVE_STOCK && parseFloat(qty) > availableQty) {
                        validationError = 'One of the selected items has negative stock so remove from invoice';
                        firstInvalidField = $qtyField;
                        validItems = false;
                        return false;
                    }
                    
                    if (!price || isNaN(price) || parseFloat(price) <= 0) {
                        validationError = 'Each item must have a valid unit selling price greater than 0.';
                        firstInvalidField = $row.find('input.unit_selling_price');
                        validItems = false;
                        return false;
                    }
                });
                
                if (!validItems) {
                    if (validationError) {
                        console.error('Validation Error:', validationError);
                        notify(validationError);
                        // Scroll to the error field
                        if (firstInvalidField && firstInvalidField.length) {
                            $('html, body').animate({
                                scrollTop: firstInvalidField.offset().top - 100
                            }, 500);
                            firstInvalidField.focus().css('border-color', '#dc3545');
                            setTimeout(function() {
                                firstInvalidField.css('border-color', '');
                            }, 3000);
                        }
                    }
                    return;
                }

                if(!$('#paid_status').val()) {
                    notify('Please select a paid status');
                    return;
                }

                var totalAmount = parseMoney($('#estimated_amount').val());
                var paidStatus = $('#paid_status').val();
                if(paidStatus === 'partial_payment') {
                    $('#cash_tendered').val($('input.paid_amount').val());
                    $('#change_due').val('0.00');
                }
                var cashTenderedValue = $('#cash_tendered').val().trim();
                var cashTendered = cashTenderedValue === '' ? 0 : Number(cashTenderedValue);
                if(isNaN(cashTendered) || cashTendered < 0) {
                    notify('Please enter a valid cash tendered amount');
                    return;
                }

                if(paidStatus === 'full_payment' && cashTendered < totalAmount) {
                    notify('Cash tendered cannot be less than the grand total for full payment.');
                    return;
                }

                if(paidStatus === 'full_credit' && cashTendered > 0) {
                    notify('Cash tendered must be 0 for full credit sales.');
                    return;
                }

                if(!$('#customer_id').val()) {
                    notify('Please select a customer name');
                    return;
                }

                // Validate paid amount if partial paid
                if(paidStatus === 'partial_payment') {
                    var paidAmount = parseMoney($('input.paid_amount').val());
                    if(paidAmount <= 0) {
                        notify('Please enter a valid paid amount greater than 0 for partial payment.');
                        return;
                    }

                    if(paidAmount >= totalAmount) {
                        notify('Partial payment amount must be less than the grand total.');
                        return;
                    }
                }

                // Validate new customer fields if "New Customer" is selected
                if($('#customer_id').val() === '0') {
                    var name = $('#name').val();
                    var mobile = $('#mobile_no').val();
                    var email = $('#email').val();
                    if(!name || !name.trim()) {
                        notify('Customer name is required.');
                        return;
                    }
                    if(!mobile || !/^\d{10}$/.test(mobile)) {
                        notify('Customer mobile number must be 10 digits.');
                        return;
                    }
                    if(!email || !/^\S+@\S+\.\S+$/.test(email)) {
                        notify('Please enter a valid customer email.');
                        return;
                    }
                }

                // Show loading
                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Loading.standard('Creating Sale, please wait...');
                } else {
                    console.log('Creating Sale...');
                }
                var form = this;
                $.ajax({
                    url: $(form).attr('action'),
                    type: $(form).attr('method'),
                    data: new FormData(form),
                    contentType: false,
                    processData: false,
                    success: function(data) {
                        if (typeof Notiflix !== 'undefined') {
                            Notiflix.Loading.remove();
                        }
                        if (data.status === 200) {
                            if (typeof Notiflix !== 'undefined') {
                                Notiflix.Notify.success(data.message);
                            } else {
                                alert(data.message);
                            }


                            // ðŸš€ Open the receipt page
                            if (data.print_url) {
                                window.location.href = data.print_url;
                            }
                            
                            // Clear form fields
                            $('#invoiceForm')[0].reset();
                            // Remove all dynamic Sale items
                            $('#addRow').empty();
                            // Hide and clear error alert
                            $('#errorAlert').hide();
                            $('#errors').empty();
                            // Optionally reset select2 fields
                            if ($('.select2').length) {
                                $('.select2').val('').trigger('change');
                            }
                            
                            // Directly set next purchase number from backend response
                        if (data.next_no) {
                            $('#sale_no').val(data.next_no);
                        }
                        } else {
                            if (typeof Notiflix !== 'undefined') {
                                Notiflix.Notify.failure(data.message);
                            } else {
                                alert(data.message);
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        if (typeof Notiflix !== 'undefined') {
                            Notiflix.Loading.remove();
                        }
                        $('#errors').empty();

                        if (xhr.status === 419 || xhr.status === 401) {
                            const sessionMessage = 'Your login session has expired due to inactivity.';
                            const sessionAction = 'Action: refresh the page and sign in again, then retry saving the Sale.';

                            $('#errorAlert').show();
                            $('#errors').append('<li>' + sessionMessage + '</li>');
                            $('#errors').append('<li>' + sessionAction + '</li>');

                            if (typeof Notiflix !== 'undefined') {
                                Notiflix.Notify.failure(sessionMessage + ' ' + sessionAction);
                            } else {
                                alert(sessionMessage + ' ' + sessionAction);
                            }

                            $('html, body').animate({
                                scrollTop: $('#errorAlert').offset().top - 100
                            }, 500);
                            return;
                        }

                        if (xhr.status === 409 && xhr.responseJSON && xhr.responseJSON.error_code === 'SALE_NO_CLASH') {
                            $('#errorAlert').show();

                            const clashMessage = xhr.responseJSON.message || 'Sale number clash detected.';
                            const clashAction = xhr.responseJSON.action || 'Action: click Save Sale again.';

                            $('#errors').append('<li>' + clashMessage + '</li>');
                            $('#errors').append('<li>' + clashAction + '</li>');

                            if (xhr.responseJSON.next_no) {
                                $('#sale_no').val(xhr.responseJSON.next_no);
                            }

                            if (typeof Notiflix !== 'undefined') {
                                Notiflix.Notify.failure(clashMessage + ' ' + clashAction);
                            } else {
                                alert(clashMessage + ' ' + clashAction);
                            }

                            $('html, body').animate({
                                scrollTop: $('#errorAlert').offset().top - 100
                            }, 500);
                            return;
                        }

                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            $('#errorAlert').show();
                            $.each(xhr.responseJSON.errors, function(field, messages) {
                                $('#errors').append('<li>' + messages[0] + '</li>');
                                if (typeof Notiflix !== 'undefined') {
                                    Notiflix.Notify.failure(messages[0]);
                                } else {
                                    alert('Validation Error: ' + messages[0]);
                                }
                            });
                            $('html, body').animate({
                                scrollTop: $('#errorAlert').offset().top - 100
                            }, 500);
                        } else {
                            let duplicate = false;
                            if (xhr.responseText && xhr.status === 500) {
                                // Check for SQL duplicate entry error
                                if (xhr.responseText.includes('1062') && xhr.responseText.includes('sale_no')) {
                                    duplicate = true;
                                }
                            }
                            $('#errorAlert').show();
                            if (duplicate) {
                                $('#errors').append('<li>Duplicate Sale number. Please refresh the page and try again.</li>');
                                if (typeof Notiflix !== 'undefined') {
                                    Notiflix.Notify.failure('Duplicate Sale number. Please refresh the page and try again.');
                                } else {
                                    alert('Duplicate Sale number. Please refresh the page and try again.');
                                }
                            } else {
                                $('#errors').append('<li>An error occurred. Please try again later.</li>');
                                if (typeof Notiflix !== 'undefined') {
                                    Notiflix.Notify.failure('An error occurred. Please try again later.');
                                } else {
                                    alert('An error occurred. Please try again later.');
                                }
                            }
                        }
                    }
                });
            });
        });
    </script>

    <script id="document-template" type="text/x-handlebars-template">
     
        <tr class="delete_add_more_item" id="delete_add_more_item">
            <input type="hidden" name="date" value="@{{date}}">
            <input type="hidden" name="sale_no" value="@{{sale_no}}">
            
    
            <td>
                <input type="hidden" name="category_id[]" value="@{{category_id}}">
                @{{ category_name }}
            </td>

            <td>
                <input type="hidden" name="barcode[]" value="@{{barcode}}">
                @{{ barcode }}
            </td>

            <td>
                <input type="hidden" name="product_id[]" value="@{{product_id}}">
                @{{ product_name }}
                <br>
                <small class="text-muted">Available Qty: @{{ available_qty }}</small>
                <input type="hidden" name="available_stock_qty[]" class="available_stock_qty" value="@{{ available_qty }}">
            </td>

            
            <td>
                <input type="number" min="1" class="form-control selling_qty text-right" id="selling_qty" name="selling_qty[]" value="{{ old('selling_qty') }}" onchange="checkQuantity()" oninput="$(this).css('border-color', '')"> 
            </td>

            <td>
                <!-- ðŸ”¹ Unit selling price auto-filled from products.selling_price -->
                <input type="number" 
                    class="form-control unit_selling_price text-right" 
                    name="unit_selling_price[]"
                    value="@{{unit_selling_price}}" 
                    readonly 
                    style="background-color:#ddd;">
            </td>

            <td>
                <input type="hidden" name="unit_id[]" value="@{{unit_id}}">
                @{{ unit_name }}
            </td>


            <td>
                <input type="number" class="form-control selling_price text-right" name="selling_price[]" value="0" readonly> 
                
            </td>

            <td>
                <i class="btn btn-danger btn-sm fas fa-window-close removeeventmore"></i>
            </td>

        </tr>

    </script>

    {{-- handles add more item in purchase --}}
    <script type="text/javascript">
        $(document).ready(function(){

            function round2(num) {
                return (Math.round((num + Number.EPSILON) * 100) / 100).toFixed(2);
            }

            function formatMoney(value) {
                return new Intl.NumberFormat('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).format(value);
            }

            function getNumeric(selector) {
                return parseMoney($(selector).val());
            }

            function updateTenderSummary() {
                var total = getNumeric('#estimated_amount');
                var cashTendered = getNumeric('#cash_tendered');
                var paidStatus = $('#paid_status').val();
                var amountToCollect = paidStatus === 'partial_payment'
                    ? getNumeric('.paid-amount-input')
                    : (paidStatus === 'full_payment' ? total : 0);
                var changeDue = Math.max(0, cashTendered - amountToCollect);

                $('#change_due').val(round2(changeDue));
            }

            var cameraStream = null;
            var detector = null;
            var scanRafId = null;
            var zxingReader = null;
            var zxingControls = null;
            var scanLocked = false;

            function notifyScan(message, type) {
                if (typeof Notiflix !== 'undefined') {
                    if (type === 'success') {
                        Notiflix.Notify.success(message);
                    } else if (type === 'warning') {
                        Notiflix.Notify.warning(message);
                    } else {
                        Notiflix.Notify.failure(message);
                    }
                } else {
                    alert(message);
                }
            }

            function triggerBarcodeLookup(scannedBarcode) {
                if (!scannedBarcode) return;
                $('#barcode').val(scannedBarcode).trigger('change');
            }

            function stopCameraScan() {
                if (scanRafId) {
                    cancelAnimationFrame(scanRafId);
                    scanRafId = null;
                }

                if (zxingControls && typeof zxingControls.stop === 'function') {
                    zxingControls.stop();
                }
                zxingControls = null;

                if (zxingReader && typeof zxingReader.reset === 'function') {
                    zxingReader.reset();
                }
                zxingReader = null;

                if (cameraStream) {
                    cameraStream.getTracks().forEach(function(track){ track.stop(); });
                    cameraStream = null;
                }

                var video = document.getElementById('barcodeCameraPreview');
                if (video) {
                    video.pause();
                    video.srcObject = null;
                }

                $('#cameraScanWrap').hide();
                $('#startCameraScan').show();
                $('#stopCameraScan').hide();
                scanLocked = false;
            }

            function onBarcodeScanned(codeText) {
                var cleanCode = $.trim(codeText || '');
                if (!cleanCode || scanLocked) return;

                scanLocked = true;
                triggerBarcodeLookup(cleanCode);
                notifyScan('Barcode scanned from camera', 'success');
                stopCameraScan();
            }

            function startNativeDetector(videoEl) {
                if (!detector) {
                    detector = new BarcodeDetector({
                        formats: ['code_128', 'ean_13', 'ean_8', 'upc_a', 'upc_e', 'code_39', 'qr_code']
                    });
                }

                var detectFrame = function() {
                    if (!cameraStream) return;

                    detector.detect(videoEl)
                        .then(function(codes) {
                            if (codes && codes.length > 0) {
                                onBarcodeScanned(codes[0].rawValue);
                                return;
                            }
                            scanRafId = requestAnimationFrame(detectFrame);
                        })
                        .catch(function() {
                            scanRafId = requestAnimationFrame(detectFrame);
                        });
                };

                detectFrame();
            }

            function startZxingFallback(videoEl) {
                if (!window.ZXing || !window.ZXing.BrowserMultiFormatReader) {
                    notifyScan('Camera scanner fallback is not available in this browser.', 'error');
                    stopCameraScan();
                    return;
                }

                zxingReader = new window.ZXing.BrowserMultiFormatReader();
                zxingReader.decodeFromVideoDevice(null, videoEl, function(result, err, controls) {
                    if (controls) {
                        zxingControls = controls;
                    }

                    if (result) {
                        onBarcodeScanned(result.getText());
                    }
                });
            }

            async function startCameraScan() {
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    notifyScan('Camera is not supported on this device/browser.', 'error');
                    return;
                }

                if (!(window.isSecureContext || location.hostname === 'localhost' || location.hostname === '127.0.0.1')) {
                    notifyScan('Camera scanning requires HTTPS in production.', 'warning');
                    return;
                }

                stopCameraScan();

                try {
                    cameraStream = await navigator.mediaDevices.getUserMedia({
                        video: { facingMode: { ideal: 'environment' } },
                        audio: false
                    });

                    var video = document.getElementById('barcodeCameraPreview');
                    video.srcObject = cameraStream;
                    await video.play();

                    $('#cameraScanWrap').show();
                    $('#startCameraScan').hide();
                    $('#stopCameraScan').show();

                    if ('BarcodeDetector' in window) {
                        startNativeDetector(video);
                    } else {
                        startZxingFallback(video);
                    }
                } catch (error) {
                    notifyScan('Unable to start camera. Please allow camera permission and retry.', 'error');
                    stopCameraScan();
                }
            }

            $(document).on('click', '#startCameraScan', function() {
                startCameraScan();
            });

            $(document).on('click', '#stopCameraScan', function() {
                stopCameraScan();
            });

            $(window).on('beforeunload', function() {
                stopCameraScan();
            });

            $(document).on('keydown', '#barcode', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    $(this).trigger('change');
                }
            });

            function resetProductSelectionFields() {
                $('#product_id').val(null).trigger('change');
                $('#category_id').val(null).trigger('change');
                $('#unit_id').html('<option value="">Select Unit</option>').val(null).trigger('change');
                $('#current_stock_qty').val('');
            }

            var barcodeLookupsInFlight = Object.create(null);
            var recentBarcodeScans = Object.create(null);

            // ðŸ”¹ Handle barcode scan/entry
            $(document).on("change", "#barcode", function(){
                var barcode = $.trim($(this).val());
                if(!barcode) return;

                if (barcodeLookupsInFlight[barcode] || recentBarcodeScans[barcode]) return;
                barcodeLookupsInFlight[barcode] = true;
                recentBarcodeScans[barcode] = true;
                window.setTimeout(function() {
                    delete recentBarcodeScans[barcode];
                }, 1500);

                $.ajax({
                    url: "{{ route('products.findByBarcode') }}", // lookup route
                    type: "GET",
                    data: { barcode: barcode },
                    success: function(product){
                        if(product && product.id){
                            var availableQty = parseFloat(product.quantity);
                            if (isNaN(availableQty) || availableQty <= 0) {
                                Notiflix.Notify.failure('Cannot add this item. Available stock is 0. Please restock before adding to invoice.');
                                $('#barcode').val('');
                                $('#barcode').focus();
                                return;
                            }

                            var date = $('#date').val();
                            var sale_no = $('#sale_no').val();

                            // Keep product list intact and drive selection from product dropdown.
                            $('#product_id').val(String(product.id)).trigger('change');

                            // Prepare data for Handlebars row
                            var source = $("#document-template").html();
                            var template = Handlebars.compile(source);
                            var data = {
                                date: date,
                                sale_no: sale_no,
                                barcode: product.barcode,          // ðŸ”¹ added
                                category_id: product.category_id,
                                category_name: product.category.name,
                                product_id: product.id,
                                product_name: product.name,
                                available_qty: availableQty,
                                unit_id: product.unit_id,
                                unit_name: product.unit.name,
                                unit_selling_price: product.selling_price // ðŸ”¹ add this
                            };
                            var html = template(data);
                            $("#addRow").append(html);

                            // Clear barcode field after scan
                            $('#barcode').val('');
                            resetProductSelectionFields();

                            // ðŸ”¹ Optional: show stock quantity
                            // $('#current_stock_qty').val(product.quantity);

                            Notiflix.Notify.success('Product added via barcode');
                            $('#barcode').focus();
                        } else {
                            var backendMessage = product && product.message ? product.message : 'Product not found for this barcode.';
                            Notiflix.Notify.failure(backendMessage);
                            $('#barcode').val('');
                            $('#barcode').focus();
                        }
                    },
                    error: function(xhr){
                        var backendMessage = (xhr && xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error looking up barcode. Try again.';
                        Notiflix.Notify.failure(backendMessage);
                        $('#barcode').val('');
                        $('#barcode').focus();
                    },
                    complete: function() {
                        delete barcodeLookupsInFlight[barcode];
                    }
                });
            });

            // Existing add button flow (manual selection)
            $(document).on("click", ".addeventmore", function(){
                var date = $('#date').val();
                var sale_no = $('#sale_no').val(); 
                var category_id  = $('#category_id').val();
                var category_name = $('#category_id').find('option:selected').text();
                var product_id = $('#product_id').val();
                var product_name = $('#product_id').find('option:selected').text();
                var selling_price = $('#product_id').find('option:selected').data('price'); // ðŸ”¹ new
                var available_qty = $('#product_id').find('option:selected').data('qty');
                var available_qty_num = parseFloat(available_qty);
                var unit_id  = $('#unit_id').val();
                var unit_name = $('#unit_id').find('option:selected').text();

                function notifyPop(msg) {
                    if (typeof Notiflix !== 'undefined') {
                        Notiflix.Notify.failure(msg);
                    } else {
                        alert(msg);
                    }
                }

                if(date == ''){
                    notifyPop("Date is Required");
                    return false;
                }
                if(product_id == '' || product_name.trim() === 'Select Product'){
                    notifyPop("Product Field is Required");
                    return false;
                }
                if(category_id == '' || unit_id == ''){
                    notifyPop("Selected product is missing category or unit setup");
                    return false;
                }
                if(isNaN(available_qty_num) || available_qty_num <= 0){
                    notifyPop("Cannot add this item. Available stock is 0. Please restock before adding to invoice.");
                    return false;
                }

                var source = $("#document-template").html();
                var tamplate = Handlebars.compile(source);
                var data = {
                    date:date,
                    sale_no:sale_no, 
                    category_id:category_id,
                    category_name:category_name,
                    product_id:product_id,
                    product_name:product_name,
                    available_qty: available_qty,
                    unit_id:unit_id,
                    unit_name:unit_name,
                    barcode: '', // ðŸ”¹ empty if added manually
                    unit_selling_price: selling_price // ðŸ”¹ add this
                };
                var html = tamplate(data);
                $("#addRow").append(html);
                resetProductSelectionFields();
                $('#barcode').val('').focus();
            });

            $(document).on("click",".removeeventmore",function(event){
                $(this).closest(".delete_add_more_item").remove();
                totalAmountPrice();
            });

            // 'input' also covers mouse wheel changes on number inputs
            $(document).on('input change keyup click','.unit_selling_price,.selling_qty', function(){
                var $row = $(this).closest("tr");
                var unit_selling_price = parseFloat($row.find("input.unit_selling_price").val()) || 0;
                var qty = parseFloat($row.find("input.selling_qty").val()) || 0;
                $row.find("input.selling_price").val(unit_selling_price * qty);
                totalAmountPrice();
            });

            $(document).on('keyup','#discount_amount',function(){
                totalAmountPrice();
            });

            $(document).on('keyup change','#cash_tendered', function(){
                updateTenderSummary();
            });

            // Calculate sum of amout in Sale 

            function totalAmountPrice(){
                var grossTotal = 0;
                $(".selling_price").each(function(){
                    var value = $(this).val();
                    if(!isNaN(value) && value.length != 0){
                        grossTotal += parseFloat(value);
                    }
                });

                var discountAmount = parseFloat($('#discount_amount').val()) || 0;
                if (!ALLOW_MANUAL_DISCOUNT_ENTRY_BY_STAFF) {
                    discountAmount = 0;
                    $('#discount_amount').val('0');
                }
                if (discountAmount < 0) {
                    discountAmount = 0;
                }

                var netTotal = grossTotal - discountAmount;
                if (netTotal < 0) {
                    netTotal = 0;
                }

                // Receipt-style tax-inclusive breakdown:
                // total = taxable + NHIL(2.5%) + GETFund(2.5%) + VAT(15%) = taxable * 1.20
                var taxableAmount = netTotal / 1.20;
                var nhilAmount = taxableAmount * 0.025;
                var getfundAmount = taxableAmount * 0.025;
                var vatAmount = taxableAmount * 0.15;

                $('#taxable_amount').val(round2(taxableAmount));
                $('#nhil_amount').val(round2(nhilAmount));
                $('#getfund_amount').val(round2(getfundAmount));
                $('#vat_amount').val(round2(vatAmount));

                $('#estimated_amount').val(formatMoney(netTotal));
                updateTenderSummary();

            }  

        });


    </script>
 

    <script type="text/javascript">
        $(function(){
            $(document).on('change','#product_id',function(){
                var $selected = $(this).find('option:selected');
                var categoryId = $selected.data('category-id');
                var unitId = $selected.data('unit-id');
                var unitName = $selected.data('unit-name');
                var currentQty = $selected.data('qty');

                if (!$(this).val()) {
                    $('#category_id').val(null).trigger('change');
                    $('#unit_id').html('<option value="">Select Unit</option>').val(null).trigger('change');
                    $('#current_stock_qty').val('');
                    return;
                }

                $('#category_id').val(String(categoryId)).trigger('change');
                $('#unit_id').html('<option value="' + unitId + '" selected>' + unitName + '</option>').val(String(unitId)).trigger('change');
                $('#current_stock_qty').val(currentQty);
            });
        });
    </script>

    {{-- check quantity script --}}
    <script>
        function checkQuantity() {
            if (ALLOW_NEGATIVE_STOCK) {
                return;
            }

            var hasNegativeStock = false;

            $('#addRow tr').each(function() {
                var qtyField = $(this).find('input.selling_qty');
                var qty = parseFloat(qtyField.val()) || 0;
                var availableQty = parseFloat($(this).find('input.available_stock_qty').val()) || 0;

                if (qty > availableQty && qty > 0) {
                    hasNegativeStock = true;
                    qtyField.css('border-color', '#dc3545');
                } else {
                    // Clear red border if quantity is now valid
                    qtyField.css('border-color', '');
                }
            });

            if (hasNegativeStock) {
                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Notify.warning('Quantity exceeds available stock. Please adjust before submitting.', {
                        timeout: 4000,
                        position: 'right-top',
                    });
                } else {
                    alert('Quantity exceeds available stock. Please adjust before submitting.');
                }
            }
        }
    </script>
    

    <!-- Removed old .storeButton click validation handler. All validation now uses Notiflix in the main form submission handler. -->


    <script type="text/javascript">
    //payment status and customer show or hide
        var previousSalePaymentStatus = null;
        var cashTenderedAutoFilled = false;

        function syncSalePaymentUI() {
            var paid_status = $('#paid_status').val();
            var $customerSelect = $('#customer_id');
            var $cashOption = $customerSelect.find('option[data-is-cash="1"]');
            var selectedCustomer = $customerSelect.val();
            var paymentStatusChanged = paid_status !== previousSalePaymentStatus;

            $('#payment_amount_fields').css('display', paid_status ? 'grid' : 'none');

            if (paid_status === 'partial_payment') {
                $('#paid_amount_group').show();
                $('#cash_tendered_group, #change_due_group').hide();
                $('.paid_amount').prop('required', true);
                $('#cash_tendered').prop('readonly', true).prop('required', false);
                $('#cash_tendered_required').hide();
                if (paymentStatusChanged) {
                    $('#cash_tendered').val('');
                    cashTenderedAutoFilled = true;
                }
            } else {
                $('#paid_amount_group').hide();
                $('#cash_tendered_group, #change_due_group').show();
                $('.paid_amount').prop('required', false).val('');
                $('#cash_tendered_required').toggle(paid_status === 'full_payment');
                cashTenderedAutoFilled = false;
            }

            if (paid_status === 'full_credit') {
                $('#cash_tendered').val('0').prop('readonly', true).prop('required', false);
            } else if (paid_status === 'full_payment') {
                $('#cash_tendered').prop('readonly', false).prop('required', true);
                if (paymentStatusChanged) {
                    $('#cash_tendered').val('');
                }
            } else if (paid_status !== 'partial_payment') {
                $('#cash_tendered').val('').prop('readonly', false).prop('required', false);
            }

            if ($cashOption.length) {
                if (paid_status === 'full_payment') {
                    $cashOption.prop('disabled', false);
                    if (!selectedCustomer) {
                        $customerSelect.val($cashOption.val());
                    }
                } else if (paid_status === 'partial_payment' || paid_status === 'full_credit') {
                    if (selectedCustomer === $cashOption.val()) {
                        $customerSelect.val('');
                    }
                    $cashOption.prop('disabled', true);
                } else {
                    $cashOption.prop('disabled', false);
                }
            }

            $('#cash_tendered').trigger('change');
            $customerSelect.trigger('change');
            previousSalePaymentStatus = paid_status;
        }

        $(document).on('change','#paid_status', function(){
            syncSalePaymentUI();
        });

        $(document).on('keydown', '.paid-amount-input', function(e) {
            const allowed = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Home', 'End'];
            const ctrlCmd = e.ctrlKey || e.metaKey;

            if (ctrlCmd && ['a', 'c', 'v', 'x'].includes(e.key.toLowerCase())) {
                return;
            }

            const isDecimalKey = ['.', ',', 'Decimal', 'NumpadDecimal'].includes(e.key);
            if (isDecimalKey) {
                if ($(this).val().includes('.')) {
                    e.preventDefault();
                    return;
                }

                // Force decimal separator at current cursor position for consistent UX.
                const input = this;
                const start = input.selectionStart ?? input.value.length;
                const end = input.selectionEnd ?? input.value.length;
                const before = input.value.slice(0, start);
                const after = input.value.slice(end);
                input.value = before + '.' + after;
                const pos = start + 1;
                input.setSelectionRange(pos, pos);
                e.preventDefault();
                return;
            }

            if (allowed.includes(e.key) || /^[0-9]$/.test(e.key)) {
                return;
            }

            // Block e/E/+/- and any text character.
            e.preventDefault();
        });

        $(document).on('input', '.paid-amount-input', function() {
            const input = this;
            const oldValue = input.value || '';
            const oldPos = input.selectionStart ?? oldValue.length;

            let clean = oldValue.replace(',', '.').replace(/[^0-9.]/g, '');
            const firstDot = clean.indexOf('.');
            if (firstDot !== -1) {
                clean = clean.substring(0, firstDot + 1) + clean.substring(firstDot + 1).replace(/\./g, '');
            }

            const parts = clean.split('.');
            if (parts.length === 2) {
                parts[1] = parts[1].substring(0, 2);
                clean = parts[0] + '.' + parts[1];
            }

            if (clean !== oldValue) {
                const removed = oldValue.length - clean.length;
                input.value = clean;
                const newPos = Math.max(0, oldPos - Math.max(0, removed));
                input.setSelectionRange(newPos, newPos);
            }

            if ($('#paid_status').val() === 'partial_payment') {
                $('#cash_tendered').val(clean);
                $('#change_due').val('0.00');
            }
            $('#cash_tendered').trigger('change');
        });

        $(document).on('input', '#cash_tendered', function(event) {
            if (event.originalEvent) {
                cashTenderedAutoFilled = false;
            }
        });

        $(document).on('change','#customer_id', function(){
            var customer_id = $(this).val();
            if (customer_id == '0') {
                $('.new_customer').show();
            }else{
                $('.new_customer').hide();
            }
        });

        syncSalePaymentUI();


    </script>
@endsection

