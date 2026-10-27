@extends('layout.main')
@section('title') {{'Create Purchase Sale'}} @endsection

@section('content')
<script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>

    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card" style="margin-top: 5px;">
                        <div class="card-header with-border">
                            <span class="card-title mobile-page-title" style="font-size:20px;">Create | Purchase Sale</span>
                            <a href="{{route('purchases.index')}}" class="mobile-header-action" style="float:right">
                                <button type="button" class="btn btn-dark modal_btn mobile-header-btn">
                                    View Purchases Sale
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
                                <form method="post" action="{{ route('purchases.store') }}" id="purchaseForm" novalidate>
                                    @csrf
                                    <div class="purchase-section-panel">
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
                                            <input class="form-control" name="purchase_no" type="text" value="{{ $purchase_no }}" id="purchase_no" readonly style="background-color:#ddd;" placeholder="Purchase #">
                                        </div>

                                        <div class="col-md-2 d-flex align-items-center mb-2 mb-md-0">
                                            <input class="form-control" name="current_stock_qty" type="text" id="current_stock_qty" readonly style="background-color:#ddd" placeholder="Current Stock">
                                        </div>

                                        <div class="col-md-3 d-flex align-items-center justify-content-end mb-2 mb-md-0">
                                            <input class="form-control" value="{{ $date }}" name="date" type="date" id="date">
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
                                                            data-cost-price="{{ (float) ($product->cost_price ?? 0) }}"
                                                            data-selling-price="{{ (float) ($product->selling_price ?? 0) }}"
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
                                                {{-- <label for="category_id" class="mb-0 mr-2" style="min-width:90px;"><strong>Category <span class="text-danger">*</span></strong></label> --}}
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
                                                {{-- <label for="unit_id" class="mb-0 mr-2" style="min-width:60px;"><strong>Unit <span class="text-danger">*</span></strong></label> --}}
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
                                                        <th width="15%">Cost Price</th>
                                                        <th width="10%">Sell Price</th>
                                                        <th width="10%">Per</th>
                                                        <th width="15%">Total (Ghc)</th>
                                                        <th width="5%">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="addRow" class="addRow"></tbody>
                                                <tbody>
                                                    <tr>
                                                        <td colspan="7">Discount</td>
                                                        <td>
                                                            <input type="text" name="discount_amount" id="discount_amount" class="form-control estimated_amount" placeholder="amount">
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="7">Grand Total</td>
                                                        <td>
                                                            <input type="text" value="0.00" id="estimated_amount_display" class="form-control estimated_amount" aria-label="Grand Total" readonly style="background-color: #ddd;">
                                                            <input type="hidden" name="estimated_amount" value="0.00" id="estimated_amount">
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
                                        <div class="payment-selection-title">Payment and Supplier Selection</div>
                                        <div class="payment-selection-subtitle">Select payment type first, then assign the supplier account for this purchase.</div>
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
                                                <label><strong>Supplier Name <span class="text-danger">*</span></strong></label>
                                                <select name="supplier_id" id="supplier_id" class="form-control payment-control">
                                                    <option value="" selected disabled>Select Supplier</option>
                                                    @foreach($suppliers as $supplier)
                                                        <option value="{{ $supplier->id }}" 
                                                            data-is-cash="{{ strtolower($supplier->name) === 'cash_purchase' ? '1' : '0' }}">
                                                            {{ $supplier->name }}
                                                        </option>
                                                    @endforeach
                                                    <option value="0">Click to Create new supplier</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div id="purchase_paid_amount_fields" class="payment-details-grid" style="display:none;">
                                            <div id="purchase_paid_amount_group" class="payment-detail-field">
                                                <label for="paid_amount">Paid Amount <span class="text-danger">*</span></label>
                                                <input type="text" name="paid_amount" id="paid_amount" class="form-control paid_amount paid-amount-input payment-money-input" placeholder="Enter paid amount" inputmode="decimal" autocomplete="off">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Hidden Add Supplier Form -->
                                    <div class="row new_supplier mt-3" style="display:none;">
                                        <div class="form-group col-md-4">
                                            <input type="text" name="name" id="name" class="form-control" placeholder="Supplier Name">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <input type="text" name="mobile_no" id="mobile_no" class="form-control" placeholder="Supplier Mobile No" minlength="10" maxlength="10" onkeypress="return isNumberKey(event)">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <input type="text" name="email" id="email" class="form-control" placeholder="Supplier Email">
                                        </div>
                                    </div>
                                    <div class="purchase-section-panel purchase-description-panel mt-3">
                                        <div class="form-group mb-0">
                                            <textarea name="description" class="form-control" id="description" placeholder="Write Description Here"></textarea>
                                        </div>
                                    </div>
                                    <div class="form-group mt-4">
                                        <center>
                                            <button type="submit" class="btn btn-success storeButton mobile-submit-btn" id="storeButton">Submit Purchase</button>
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

@endsection 

@section('styles')
    <style>
        .block_div {
            display: none;
        }
        /* Ensure main content is not hidden under sidebar */
        /* Remove custom margin, let theme handle sidebar spacing */
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

        .purchase-section-panel,
        .payment-selection-panel {
            border: 1px solid #cbd5e1;
            background: linear-gradient(135deg, #f8fafc, #eef2ff);
            border-radius: 12px;
            padding: 14px;
        }

        .purchase-description-panel textarea {
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
            grid-template-columns: minmax(0, 360px);
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

        .payment-money-input {
            min-height: 44px;
            border: 1px solid #94a3b8;
            background: #fff;
            text-align: right;
            font-variant-numeric: tabular-nums;
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
                min-width: 920px;
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
    $(document).ready(function (){
        // Handle form submission
        $('#purchaseForm').on('submit', function(e) {
            e.preventDefault();

            function notify(msg) {
                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Notify.failure(msg, {
                        timeout: 3000,
                        showOnlyTheLastOne: true,
                        position: 'right-top',
                    });
                } else {
                    alert(msg);
                }
            }

            // --- client-side validation (unchanged) ---
            if(!$('#date').val().trim()) { notify('Please enter a date'); return; }
            var addedItemsCount = $('#addRow').children('tr').length;
            if (addedItemsCount === 0) {
                if(!$('#product_id').val()) { notify('Please select a product'); return; }
                notify('Please add at least one purchase item.');
                return;
            }
            var validItems = true;
            $('#addRow tr').each(function() {
                var qty = $(this).find('input.buying_qty').val();
                var price = $(this).find('input.unit_cost_price').val();
                var sellingPrice = $(this).find('input.unit_selling_price').val();
                if (!qty || isNaN(qty) || parseFloat(qty) <= 0) { notify('Each item must have a valid quantity greater than 0.'); validItems = false; return false; }
                if (!price || isNaN(price) || parseFloat(price) <= 0) { notify('Each item must have a valid unit cost price greater than 0.'); validItems = false; return false; }
                if (!sellingPrice || isNaN(sellingPrice) || parseFloat(sellingPrice) <= 0) { notify('Each item must have a valid selling price greater than 0.'); validItems = false; return false; }
                if (parseFloat(sellingPrice) <= parseFloat(price)) { notify('Each item selling price must be greater than its cost price.'); validItems = false; return false; }
            });
            if (!validItems) return;
            if(!$('#paid_status').val()) { notify('Please select a paid status'); return; }
            if(!$('#supplier_id').val()) { notify('Please select a supplier'); return; }
            if($('#paid_status').val() === 'partial_payment') {
                var paidAmount = $('input.paid_amount').val();
                if(!paidAmount || isNaN(paidAmount) || parseFloat(paidAmount) <= 0) {
                    notify('Please enter a valid paid amount greater than 0 for partial payment.');
                    return;
                }

                var totalAmount = parseFloat($('#estimated_amount').val()) || 0;
                if(parseFloat(paidAmount) >= totalAmount) {
                    notify('Partial payment amount must be less than the grand total.');
                    return;
                }
            }
            if($('#supplier_id').val() === '0') {
                var name = $('#name').val();
                var mobile = $('#mobile_no').val();
                var email = $('#email').val();
                if(!name || !name.trim()) { notify('Supplier name is required.'); return; }
                if(!mobile || !/^\d{10}$/.test(mobile)) { notify('Supplier mobile number must be 10 digits.'); return; }
                if(!email || !/^\S+@\S+\.\S+$/.test(email)) { notify('Please enter a valid supplier email.'); return; }
            }

            // Show loading
            if (typeof Notiflix !== 'undefined') {
                Notiflix.Loading.standard('Creating purchase, please wait...');
            } else {
                console.log('Creating purchase...');
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
                        // Clear form fields
                        $('#purchaseForm')[0].reset();
                        $('#addRow').empty();
                        $('#errorAlert').hide();
                        $('#errors').empty();
                        if ($('.select2').length) {
                            $('.select2').val('').trigger('change');
                        }
                        // Directly set next purchase number from backend response
                        if (data.next_no) {
                            $('#purchase_no').val(data.next_no);
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
                            if (xhr.responseText.includes('1062') && xhr.responseText.includes('purchase_no')) {
                                duplicate = true;
                            }
                        }
                        $('#errorAlert').show();
                        if (duplicate) {
                            $('#errors').append('<li>Duplicate purchase number. Please refresh the page and try again.</li>');
                            if (typeof Notiflix !== 'undefined') {
                                Notiflix.Notify.failure('Duplicate purchase number. Please refresh the page and try again.');
                            } else {
                                alert('Duplicate purchase number. Please refresh the page and try again.');
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

    {{-- Handles delete and add item --}}
       <script id="document-template" type="text/x-handlebars-template">
     
        <tr class="delete_add_more_item">
            <input type="hidden" name="date" value="@{{date}}">
            <input type="hidden" name="purchase_no" value="@{{purchase_no}}">
    
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
            </td>


            <td>
                <input type="number" min="1" class="form-control buying_qty text-right" name="buying_qty[]" value=""> 
            </td>

            <td>
                <input type="number" class="form-control unit_cost_price text-right" name="unit_cost_price[]" value=""> 
            </td>

            <td>
                <input type="number" class="form-control unit_selling_price text-right" name="unit_selling_price[]" value=""> 
            </td>

            <td>
                <input type="hidden" name="unit_id[]" value="@{{unit_id}}">
                @{{ unit_name }}
            </td>

            <td>
                <input type="number" class="form-control buying_price text-right" name="buying_price[]" value="" readonly> 
            </td>

            <td>
                <i class="btn btn-danger btn-sm fas fa-window-close removeeventmore"></i>
            </td>

        </tr>

    </script>

     {{-- handles add more item in purchase --}}
    <script type="text/javascript">
        $(document).ready(function(){
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
                            var date = $('#date').val();
                            var purchase_no = $('#purchase_no').val();

                            // Keep product list intact and drive selection from product dropdown.
                            $('#product_id').val(String(product.id)).trigger('change');

                            // Prepare data for Handlebars row
                            var source = $("#document-template").html();
                            var template = Handlebars.compile(source);
                            var data = {
                                date: date,
                                purchase_no: purchase_no,
                                barcode: product.barcode,          // ðŸ”¹ added
                                category_id: product.category_id,
                                category_name: product.category.name,
                                product_id: product.id,
                                product_name: product.name,
                                unit_id: product.unit_id,
                                unit_name: product.unit.name,
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
                var purchase_no = $('#purchase_no').val(); 
                var category_id  = $('#category_id').val();
                var category_name = $('#category_id').find('option:selected').text();
                var product_id = $('#product_id').val();
                var product_name = $('#product_id').find('option:selected').text();
                var selectedProductOption = $('#product_id').find('option:selected');
                var unit_id  = $('#unit_id').val();
                var unit_name = $('#unit_id').find('option:selected').text();

                function notifyPop(msg) {
                    if (typeof Notiflix !== 'undefined') {
                        Notiflix.Notify.failure(msg);
                    } else if (typeof $.notify !== 'undefined') {
                        $.notify(msg, {globalPosition: 'top right', className:'error'});
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

                var source = $("#document-template").html();
                var template = Handlebars.compile(source);
                var data = {
                    date: date,
                    purchase_no: purchase_no, 
                    category_id: category_id,
                    category_name: category_name,
                    product_id: product_id,
                    product_name: product_name,
                    unit_id: unit_id,
                    unit_name: unit_name,
                    barcode: selectedProductOption.data('barcode') || '' // include barcode for manual selection
                };
                var html = template(data);
                $("#addRow").append(html);
                resetProductSelectionFields();
                $('#barcode').val('').focus();
            });

            // Remove row
            $(document).on("click",".removeeventmore",function(event){
                $(this).closest(".delete_add_more_item").remove();
                totalAmountPrice();
            });

            // Calculate row total ('input' also covers mouse wheel changes on number inputs)
            $(document).on('input change keyup click','.unit_cost_price,.buying_qty', function(){
                var $row = $(this).closest("tr");
                var unit_cost_price = parseFloat($row.find("input.unit_cost_price").val()) || 0;
                var qty = parseFloat($row.find("input.buying_qty").val()) || 0;
                $row.find("input.buying_price").val(unit_cost_price * qty);
                totalAmountPrice();
            });

            // Discount recalculation
            $(document).on('keyup','#discount_amount',function(){
                totalAmountPrice();
            });

            // Calculate sum of all items
            function totalAmountPrice(){
                var sum = 0;
                $(".buying_price").each(function(){
                    var value = $(this).val();
                    if(!isNaN(value) && value.length != 0){
                        sum += parseFloat(value);
                    }
                });

                var discount_amount = parseFloat($('#discount_amount').val());
                if(!isNaN(discount_amount) && discount_amount.length != 0){
                    sum -= parseFloat(discount_amount);
                }

                var normalizedTotal = Number(sum.toFixed(2));
                $('#estimated_amount').val(normalizedTotal.toFixed(2));
                $('#estimated_amount_display').val(normalizedTotal.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }));
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

    {{-- //payment status and customer show or hide --}}
    <script type="text/javascript">
        function syncPurchasePaymentUI() {
            var paid_status = $('#paid_status').val();
            var $supplierSelect = $('#supplier_id');
            var $cashOption = $supplierSelect.find('option[data-is-cash="1"]');
            var selectedSupplier = $supplierSelect.val();

            if (paid_status === 'partial_payment') {
                $('#purchase_paid_amount_fields').css('display', 'grid');
                $('.paid_amount').prop('required', true);
            } else {
                $('#purchase_paid_amount_fields').hide();
                $('.paid_amount').prop('required', false).val('');
            }

            if ($cashOption.length) {
                if (paid_status === 'full_payment') {
                    $cashOption.prop('disabled', false);
                    if (!selectedSupplier || selectedSupplier === '0') {
                        $supplierSelect.val($cashOption.val());
                    }
                } else if (paid_status === 'partial_payment' || paid_status === 'full_credit') {
                    if (selectedSupplier === $cashOption.val()) {
                        $supplierSelect.val('');
                    }
                    $cashOption.prop('disabled', true);
                } else {
                    $cashOption.prop('disabled', false);
                }
            }

            $supplierSelect.trigger('change');
        }

        $(document).on('change','#paid_status', function(){
            syncPurchasePaymentUI();
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
        });

        $(document).on('change','#supplier_id', function(){
            var supplier_id = $(this).val();
            if (supplier_id == '0') {
                $('.new_supplier').show();
            }else{
                $('.new_supplier').hide();
            }
        });

        syncPurchasePaymentUI();


    </script>  
@endsection

