@extends('layout.main')
@section('title') {{'New Purchase | Inventory Intake'}} @endsection

@section('content')
<script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>

    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card" style="margin-top: 5px;">
                        <div class="card-header with-border">
                            <span class="card-title mobile-page-title" style="font-size:20px; font-weight: 700; color: #0f172a;"><i class="fas fa-dolly-flatbed text-primary me-2 mr-2"></i>New Purchase</span>
                            <span class="badge bg-primary ms-2">Central Warehouse</span>
                            <a href="{{route('purchases.index')}}" class="btn btn-modern-header mobile-header-action float-end" style="float:right">
                                <i class="fas fa-list me-1 mr-1"></i> View Purchases
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
                                    <div class="purchase-section-panel mb-3">
                                        {{-- Top Row: Barcode Scanner, Purchase #, Central Stock, Date --}}
                                        <div class="row align-items-center mb-3 pb-3 border-bottom">
                                            <div class="col-md-4 col-12 mb-2 mb-md-0">
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text bg-white border-right-0 text-muted"><i class="fas fa-barcode"></i></span>
                                                    </div>
                                                    <input type="text" id="barcode" class="form-control border-left-0" placeholder="Scan Barcode..." autofocus>
                                                    <div class="input-group-append">
                                                        <button type="button" id="startCameraScan" class="btn btn-outline-secondary" title="Scan with Camera">
                                                            <i class="fas fa-camera"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-2 col-6">
                                                <div class="input-group input-group-sm">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text bg-light fw-bold text-muted" style="font-size: 12px;">Purchase #</span>
                                                    </div>
                                                    <input class="form-control fw-bold text-dark bg-light" name="purchase_no" type="text" value="{{ $purchase_no }}" id="purchase_no" readonly>
                                                </div>
                                            </div>

                                            <div class="col-md-3 col-6 text-md-right">
                                                <div class="input-group input-group-sm justify-content-md-end">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text bg-light text-muted fw-bold" style="font-size: 12px;">Central Stock</span>
                                                    </div>
                                                    <input class="form-control bg-light fw-bold text-primary text-center" name="current_stock_qty" type="text" id="current_stock_qty" readonly placeholder="--" style="max-width: 140px;">
                                                </div>
                                            </div>

                                            <div class="col-md-3 col-12">
                                                <div class="input-group input-group-sm">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text bg-light text-muted" style="font-size: 12px;"><i class="fas fa-calendar-alt"></i></span>
                                                    </div>
                                                    <input class="form-control" value="{{ $date }}" name="date" type="date" id="date">
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Camera Preview (toggled by scan button) --}}
                                        <div id="cameraScanWrap" class="mb-3" style="display:none;">
                                            <div class="d-flex align-items-center justify-content-between p-2 bg-dark rounded-top text-white">
                                                <span class="small"><i class="fas fa-camera me-1 mr-1"></i> Point camera at barcode</span>
                                                <button type="button" id="stopCameraScan" class="btn btn-danger btn-sm py-0 px-2">Close</button>
                                            </div>
                                            <video id="barcodeCameraPreview" autoplay playsinline muted style="width:100%;max-height:200px;border-radius:0 0 4px 4px;background:#000;"></video>
                                        </div>

                                        {{-- Row 2: Product Selector, Category, Unit, Add Item --}}
                                        <div class="row align-items-center g-2 mb-2">
                                            <div class="col-md-5 col-12 mb-2 mb-md-0">
                                                <select name="product_id" id="product_id" class="form-control select2" aria-label="Select product">
                                                    <option disabled selected value="">Search or select product...</option>
                                                    @foreach($products as $product)
                                                        @php
                                                            $cQty = (float) ($product->quantityForOutlet($centralOutletId ?? null));
                                                        @endphp
                                                        <option value="{{ $product->id }}"
                                                            data-base-name="{{ $product->name }}"
                                                            data-category-id="{{ $product->category_id }}"
                                                            data-category-name="{{ $product->category->name ?? '' }}"
                                                            data-unit-id="{{ $product->unit_id }}"
                                                            data-unit-name="{{ $product->unit->name ?? '' }}"
                                                            data-cost-price="{{ (float) ($product->cost_price ?? 0) }}"
                                                            data-selling-price="{{ (float) ($product->selling_price ?? 0) }}"
                                                            data-has-sub-unit="{{ $product->has_sub_unit ? 1 : 0 }}"
                                                            data-sub-unit-id="{{ $product->sub_unit_id ?? '' }}"
                                                            data-sub-unit-name="{{ $product->subUnit->name ?? '' }}"
                                                            data-pieces-per-pack="{{ (int) ($product->pieces_per_pack ?? 1) }}"
                                                            data-sub-unit-cost-price="{{ (float) ($product->sub_unit_cost_price ?? ($product->pieces_per_pack > 0 ? round($product->cost_price / $product->pieces_per_pack, 2) : 0)) }}"
                                                            data-sub-unit-selling-price="{{ (float) ($product->sub_unit_selling_price ?? ($product->pieces_per_pack > 0 ? round($product->selling_price / $product->pieces_per_pack, 2) : 0)) }}"
                                                            data-qty="{{ $cQty }}"
                                                            data-formatted-qty="{{ $product->has_sub_unit ? $product->formatQuantity($cQty) : $cQty }}"
                                                            data-barcode="{{ $product->barcode ?? '' }}">
                                                            {{ $product->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-md-3 col-6 mb-2 mb-md-0">
                                                <select name="category_id" id="category_id" class="form-control select2" aria-label="Selected category" disabled>
                                                    <option disabled selected value="">Select Category</option>
                                                    @foreach($category as $cat)
                                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-md-2 col-6 mb-2 mb-md-0">
                                                <select name="unit_id" id="unit_id" class="form-control select2" aria-label="Selected unit" disabled>
                                                    <option disabled selected value="">Select Unit</option>
                                                </select>
                                            </div>

                                            <div class="col-md-2 col-12">
                                                <button type="button" class="btn btn-modern-primary addeventmore w-100 mobile-action-btn" style="height: 40px; font-weight: 700;">
                                                    <i class="fas fa-plus me-1 mr-1"></i> Add Item
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-12">
                                            <div class="table-responsive" style="margin-bottom: 14px; border: 1px solid #cbd5e1; border-radius: 8px; background: #fff;">
                                            <table class="table table-sm table-bordered mobile-item-table" style="border-color: #cbd5e1; width: 100% !important; margin-bottom: 0;">
                                                <thead>
                                                    <tr style="background-color:#0f172a; text-align:center;" class="text-white">
                                                        <th style="width: 27%;" class="text-start ps-3 pl-3">Item Description</th>
                                                        <th style="width: 26%;">Quantity (Bag + Piece)</th>
                                                        <th style="width: 11%;" class="text-end text-right">Cost Price</th>
                                                        <th style="width: 11%;" class="text-end text-right">Selling Price</th>
                                                        <th style="width: 20%;" class="text-end text-right">Total (Ghc)</th>
                                                        <th style="width: 5%;" class="text-center">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="addRow" class="addRow"></tbody>
                                                <tbody>
                                                    <tr>
                                                        <td colspan="4" class="text-end text-right fw-bold align-middle" style="font-size: 13.5px;">Discount (Ghc)</td>
                                                        <td>
                                                            <input type="text" name="discount_amount" id="discount_amount" class="form-control estimated_amount text-end text-right" placeholder="0.00" style="height: 35px; font-weight: 700; width: 100%;">
                                                        </td>
                                                        <td></td>
                                                    </tr>
                                                    <tr style="background-color: #f1f5f9;">
                                                        <td colspan="4" class="text-end text-right fw-bold align-middle" style="font-size: 14.5px; color: #0f172a;">Grand Total (Ghc)</td>
                                                        <td>
                                                            <input type="text" value="" placeholder="0.00" id="estimated_amount_display" class="form-control estimated_amount text-end text-right" aria-label="Grand Total" readonly style="background-color: #e2e8f0; font-size: 15.5px; font-weight: 800; color: #0f172a; height: 38px; width: 100%;">
                                                            <input type="hidden" name="estimated_amount" value="" id="estimated_amount">
                                                        </td>
                                                        <td></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="payment-selection-panel mt-3">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <div class="payment-selection-title"><i class="fas fa-file-invoice-dollar text-primary me-1 mr-1"></i> Payment & Supplier</div>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="form-group col-md-4 mb-2">
                                                <label class="small fw-bold mb-1"><strong>Payment Type <span class="text-danger">*</span></strong></label>
                                                <select name="paid_status" id="paid_status" class="form-control payment-control">
                                                    <option value="" selected disabled>Select Payment Type</option>
                                                    <option value="partial_payment">Partial Payment</option>
                                                    <option value="full_payment">Full Payment</option>
                                                    <option value="full_credit">Full Credit</option>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-8">
                                                <label class="small fw-bold mb-1"><strong>Supplier Account <span class="text-danger">*</span></strong></label>
                                                <select name="supplier_id" id="supplier_id" class="form-control payment-control">
                                                    <option value="" selected disabled>Select Supplier</option>
                                                    @foreach($suppliers as $supplier)
                                                        <option value="{{ $supplier->id }}" 
                                                            data-is-cash="{{ strtolower($supplier->name) === 'cash_purchase' ? '1' : '0' }}">
                                                            {{ $supplier->name }}
                                                        </option>
                                                    @endforeach
                                                    <option value="0">+ Create New Supplier</option>
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
                                    <div class="form-group mt-4 pt-3 border-top text-center">
                                        <a href="{{ route('purchases.index') }}" class="btn btn-modern-cancel mr-2 me-2">
                                            <i class="fas fa-times me-1 mr-1"></i> Cancel
                                        </a>
                                        <button type="submit" class="btn btn-modern-primary storeButton mobile-submit-btn" id="storeButton">
                                            <i class="fas fa-check-circle me-1 mr-1"></i> Submit Purchase
                                        </button>
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
        .page-content .container-fluid {
            max-width: 100% !important;
            padding-left: 20px;
            padding-right: 20px;
        }

        .mobile-item-table {
            width: 100% !important;
            max-width: 100%;
        }

        .mobile-item-table th {
            padding: 12px 10px !important;
            font-size: 13.5px !important;
            font-weight: 700 !important;
            letter-spacing: 0.2px;
            vertical-align: middle !important;
        }

        .mobile-item-table td {
            padding: 10px 10px !important;
            vertical-align: middle !important;
        }
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



            .mobile-item-table {
                width: 100% !important;
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
    
        /* Modern inline packaging qty styles */
        .packaging-qty-inline-wrap {
            display: flex;
            align-items: center;
            gap: 6px;
            width: 100%;
            box-sizing: border-box;
        }

        .packaging-qty-input {
            height: 35px !important;
            font-size: 14px !important;
            font-weight: 700 !important;
            text-align: right !important;
            border: 1.5px solid #94a3b8 !important;
            border-radius: 6px !important;
            padding: 4px 8px !important;
            color: #0f172a !important;
            background: #ffffff !important;
            flex: 1 1 0;
            width: 100% !important;
            min-width: 0 !important;
            box-sizing: border-box !important;
            transition: all 0.2s ease;
        }

        .packaging-qty-input:focus {
            border-color: #0f766e !important;
            box-shadow: 0 0 0 2px rgba(15, 118, 110, 0.2) !important;
            outline: none !important;
        }

        .packaging-qty-plus {
            font-size: 15px;
            font-weight: 800;
            color: #64748b;
            padding: 0 1px;
            user-select: none;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .standard-qty-input {
            height: 38px !important;
            font-size: 14px !important;
            font-weight: 700 !important;
            text-align: right !important;
            border: 1.5px solid #94a3b8 !important;
            border-radius: 6px !important;
            padding: 4px 10px !important;
            color: #0f172a !important;
            background: #ffffff !important;
            width: 100% !important;
        }

        .standard-qty-input:focus {
            border-color: #0f766e !important;
            box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.18) !important;
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
                var $row = $(this);
                var qty = parseFloat($row.find('input.buying_qty').val()) || 0;
                var price = parseFloat($row.find('input.unit_cost_price').val()) || 0;
                var sellingPrice = parseFloat($row.find('input.unit_selling_price').val()) || 0;
                if (qty <= 0) { notify('Each item must have a valid quantity greater than 0.'); validItems = false; return false; }
                if (price <= 0) { notify('Each item must have a valid unit cost price greater than 0.'); validItems = false; return false; }
                if (sellingPrice <= 0) { notify('Each item must have a valid selling price greater than 0.'); validItems = false; return false; }
                if (sellingPrice <= price) { notify('Each item selling price must be greater than its cost price.'); validItems = false; return false; }
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
            <input type="hidden" name="category_id[]" value="@{{category_id}}">
            <input type="hidden" name="barcode[]" value="@{{barcode}}">
            <input type="hidden" name="unit_id[]" value="@{{unit_id}}">
            <input type="hidden" name="product_id[]" value="@{{product_id}}">

            <td class="align-middle text-start ps-3 pl-3" style="width: 27%;">
                <div class="fw-bold text-dark font-weight-bold" style="font-size: 14px; color: #0f172a;">@{{ product_name }}</div>
                <div class="d-flex flex-wrap align-items-center gap-1 mt-1">
                    @{{#if category_name}}
                        <span class="badge bg-light text-secondary border py-1 px-2" style="font-size: 10.5px;">@{{ category_name }}</span>
                    @{{/if}}
                    @{{#if barcode}}
                        <span class="badge bg-light text-muted border font-monospace py-1 px-2" style="font-size: 10.5px;"><i class="fas fa-barcode me-1 mr-1"></i>@{{ barcode }}</span>
                    @{{/if}}
                    <span class="badge bg-light text-dark border py-1 px-2" style="font-size: 10.5px;">Per @{{ unit_name }}</span>
                </div>
            </td>

            <td class="align-middle" style="width: 26%;">
                @{{#if has_sub_unit}}
                    <div class="packaging-qty-inline-wrap">
                        <input type="number" min="0" step="1" class="form-control packaging-qty-input pack_qty" name="pack_qty[]" value="" placeholder="0" title="Pack Qty (@{{unit_name}})">
                        <span class="packaging-qty-plus">+</span>
                        <input type="number" min="0" step="1" class="form-control packaging-qty-input piece_qty" name="piece_qty[]" value="" placeholder="0" title="Piece Qty (@{{sub_unit_name}})">
                    </div>

                    <input type="hidden" class="buying_qty" name="buying_qty[]" value="">
                    <input type="hidden" class="pieces_per_pack" name="pieces_per_pack[]" value="@{{pieces_per_pack}}">
                    <input type="hidden" class="sub_unit_cost_price" name="sub_unit_cost_price[]" value="@{{sub_unit_cost_price}}">
                    <input type="hidden" class="sub_unit_name" value="@{{sub_unit_name}}">
                    <input type="hidden" class="has_sub_unit" value="1">
                @{{else}}
                    <input type="number" min="1" step="1" class="form-control standard-qty-input text-right text-end buying_qty" name="buying_qty[]" value="" placeholder="0">
                    <input type="hidden" class="pack_qty" name="pack_qty[]" value="">
                    <input type="hidden" class="piece_qty" name="piece_qty[]" value="">
                    <input type="hidden" class="pieces_per_pack" name="pieces_per_pack[]" value="1">
                    <input type="hidden" class="sub_unit_cost_price" name="sub_unit_cost_price[]" value="0">
                    <input type="hidden" class="sub_unit_name" value="">
                    <input type="hidden" class="has_sub_unit" value="0">
                @{{/if}}
            </td>

            <td class="align-middle text-end text-right" style="width: 11%;">
                <input type="number" step="0.01" class="form-control unit_cost_price text-right text-end" name="unit_cost_price[]" value="@{{unit_cost_price}}" placeholder="0.00" style="height: 35px; font-size: 13.5px; font-weight: 700; width: 100%; padding: 4px 6px;">
            </td>

            <td class="align-middle text-end text-right" style="width: 11%;">
                <input type="number" step="0.01" class="form-control unit_selling_price text-right text-end" name="unit_selling_price[]" value="@{{unit_selling_price}}" placeholder="0.00" style="height: 35px; font-size: 13.5px; font-weight: 700; width: 100%; padding: 4px 6px;">
            </td>

            <td class="align-middle text-end text-right" style="width: 20%;">
                <input type="number" class="form-control buying_price text-right text-end" name="buying_price[]" value="" placeholder="0.00" readonly style="background-color:#f1f5f9; height: 35px; font-size: 15px; font-weight: 800; color: #0f172a; width: 100%; padding: 4px 10px;"> 
            </td>

            <td class="align-middle text-center" style="width: 5%;">
                <button type="button" class="btn btn-outline-danger btn-sm removeeventmore" title="Remove Item" style="border-radius: 6px; padding: 4px 8px;">
                    <i class="fas fa-trash-alt"></i>
                </button>
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
                            var hasSubUnit = (product.has_sub_unit == 1 || product.has_sub_unit === true);
                            var piecesPerPack = parseInt(product.pieces_per_pack) || 1;
                            var subUnitName = (product.sub_unit && product.sub_unit.name) ? product.sub_unit.name : (product.sub_unit_name || 'Pc');
                            var costPrice = parseFloat(product.cost_price) || 0;
                            var sellingPrice = parseFloat(product.selling_price) || 0;
                            var subUnitCost = parseFloat(product.sub_unit_cost_price) || (piecesPerPack > 0 ? Number((costPrice / piecesPerPack).toFixed(2)) : 0);
                            var subUnitSelling = parseFloat(product.sub_unit_selling_price) || (piecesPerPack > 0 ? Number((sellingPrice / piecesPerPack).toFixed(2)) : 0);
                            var initialTotal = costPrice;

                            // Prepare data for Handlebars row
                            var source = $("#document-template").html();
                            var template = Handlebars.compile(source);
                            var data = {
                                date: date,
                                purchase_no: purchase_no,
                                barcode: product.barcode,
                                category_id: product.category_id,
                                category_name: product.category.name,
                                product_id: product.id,
                                product_name: product.name,
                                unit_id: product.unit_id,
                                unit_name: product.unit.name,
                                unit_cost_price: (costPrice > 0 ? costPrice : ''),
                                unit_selling_price: (sellingPrice > 0 ? sellingPrice : ''),
                                has_sub_unit: hasSubUnit,
                                pieces_per_pack: piecesPerPack,
                                sub_unit_name: subUnitName,
                                sub_unit_cost_price: (subUnitCost > 0 ? subUnitCost : ''),
                                sub_unit_selling_price: (subUnitSelling > 0 ? subUnitSelling : ''),
                                initial_line_total: ''
                            };
                            var html = template(data);
                            $("#addRow").append(html);
                            totalAmountPrice();

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
                var product_id = $('#product_id').val();
                var selectedProductOption = $('#product_id').find('option:selected');
                var category_id  = selectedProductOption.data('category-id');
                var category_name = selectedProductOption.data('category-name') || '';
                var product_name = selectedProductOption.data('base-name') || selectedProductOption.text().trim();
                var unit_id  = selectedProductOption.data('unit-id');
                var unit_name = selectedProductOption.data('unit-name') || '';
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

                var selectedProductOption = $('#product_id').find('option:selected');
                var hasSubUnit = selectedProductOption.data('has-sub-unit') == 1;
                var piecesPerPack = parseInt(selectedProductOption.data('pieces-per-pack')) || 1;
                var subUnitName = selectedProductOption.data('sub-unit-name') || 'Pc';
                var costPrice = parseFloat(selectedProductOption.data('cost-price')) || 0;
                var sellingPrice = parseFloat(selectedProductOption.data('selling-price')) || 0;
                var subUnitCost = parseFloat(selectedProductOption.data('sub-unit-cost-price')) || (piecesPerPack > 0 ? Number((costPrice / piecesPerPack).toFixed(2)) : 0);
                var subUnitSelling = parseFloat(selectedProductOption.data('sub-unit-selling-price')) || (piecesPerPack > 0 ? Number((sellingPrice / piecesPerPack).toFixed(2)) : 0);
                var initialTotal = costPrice;

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
                    barcode: selectedProductOption.data('barcode') || '',
                    unit_cost_price: (costPrice > 0 ? costPrice : ''),
                    unit_selling_price: (sellingPrice > 0 ? sellingPrice : ''),
                    has_sub_unit: hasSubUnit,
                    pieces_per_pack: piecesPerPack,
                    sub_unit_name: subUnitName,
                    sub_unit_cost_price: (subUnitCost > 0 ? subUnitCost : ''),
                    sub_unit_selling_price: (subUnitSelling > 0 ? subUnitSelling : ''),
                    initial_line_total: ''
                };
                var html = template(data);
                $("#addRow").append(html);
                totalAmountPrice();
                resetProductSelectionFields();
                $('#barcode').val('').focus();
            });

            // Remove row
            $(document).on("click",".removeeventmore",function(event){
                $(this).closest(".delete_add_more_item").remove();
                totalAmountPrice();
            });

            // Calculate row total ('input' also covers mouse wheel changes on number inputs)
            $(document).on('input change keyup click','.unit_cost_price,.unit_selling_price,.buying_qty,.pack_qty,.piece_qty', function(){
                var $row = $(this).closest("tr");
                var hasSubUnit = $row.find('.has_sub_unit').val() === '1';
                
                if (hasSubUnit) {
                    var packQtyRaw = $row.find('.pack_qty').val();
                    var pieceQtyRaw = $row.find('.piece_qty').val();
                    var packCostRaw = $row.find('.unit_cost_price').val();
                    var packSellRaw = $row.find('.unit_selling_price').val();

                    var packQty = parseFloat(packQtyRaw) || 0;
                    var pieceQty = parseFloat(pieceQtyRaw) || 0;
                    var packCost = parseFloat(packCostRaw) || 0;
                    var packSell = parseFloat(packSellRaw) || 0;
                    var piecesPerPack = parseInt($row.find('.pieces_per_pack').val()) || 1;
                    var subUnitCost = piecesPerPack > 0 ? (packCost / piecesPerPack) : 0;
                    var subUnitSell = piecesPerPack > 0 ? (packSell / piecesPerPack) : 0;
                    $row.find('.sub_unit_cost_price').val(subUnitCost > 0 ? subUnitCost.toFixed(2) : '');

                    var totalBaseQty = (packQty * piecesPerPack) + pieceQty;
                    $row.find('.buying_qty').val(totalBaseQty > 0 ? totalBaseQty : '');
                    
                    var lineTotal = (packQty * packCost) + (pieceQty * subUnitCost);
                    $row.find('.buying_price').val(lineTotal > 0 ? lineTotal.toFixed(2) : '');
                } else {
                    var unit_cost_price = parseFloat($row.find("input.unit_cost_price").val()) || 0;
                    var qty = parseFloat($row.find("input.buying_qty").val()) || 0;
                    var lineTotal = unit_cost_price * qty;
                    $row.find("input.buying_price").val(lineTotal > 0 ? lineTotal.toFixed(2) : '');
                }
                totalAmountPrice();
            });

            // Discount recalculation
            $(document).on('keyup','#discount_amount',function(){
                totalAmountPrice();
            });

            // Calculate sum of all items
                        function formatStockDisplay(qty, hasSubUnit, piecesPerPack, unitName, subUnitName) {
                var formatName = function(count, name) {
                    var cleaned = $.trim(name || '');
                    var lower = cleaned.toLowerCase();
                    if (lower.endsWith('s')) {
                        if (count === 1) {
                            if (lower.endsWith('boxes')) return '1 box';
                            if (lower.endsWith('pieces')) return '1 piece';
                            if (lower.endsWith('bundles')) return '1 bundle';
                            if (lower.endsWith('liters')) return '1 liter';
                            if (lower.endsWith('kilograms')) return '1 kilogram';
                            if (lower.endsWith('meters')) return '1 meter';
                            return '1 ' + cleaned.slice(0, -1);
                        }
                        return count + ' ' + cleaned;
                    }
                    return count === 1 ? '1 ' + cleaned : count + ' ' + cleaned + 's';
                };

                if (!hasSubUnit || piecesPerPack <= 1) {
                    return formatName(qty, unitName || 'piece');
                }
                var packs = Math.floor(qty / piecesPerPack);
                var pieces = Math.round(qty % piecesPerPack);
                if (packs > 0 && pieces > 0) {
                    return formatName(packs, unitName || 'box') + ' ' + formatName(pieces, subUnitName || 'piece');
                } else if (packs > 0) {
                    return formatName(packs, unitName || 'box');
                } else {
                    return formatName(pieces, subUnitName || 'piece');
                }
            }

            function totalAmountPrice(){
                var sum = 0;
                var hasAnyPrice = false;
                $(".buying_price").each(function(){
                    var value = $(this).val();
                    if(!isNaN(value) && value.length != 0 && parseFloat(value) > 0){
                        sum += parseFloat(value);
                        hasAnyPrice = true;
                    }
                });

                var discount_amount = parseFloat($('#discount_amount').val());
                if(!isNaN(discount_amount) && discount_amount.length != 0){
                    sum -= parseFloat(discount_amount);
                }

                if (hasAnyPrice || sum > 0) {
                    var normalizedTotal = Number(sum.toFixed(2));
                    $('#estimated_amount').val(normalizedTotal.toFixed(2));
                    $('#estimated_amount_display').val(normalizedTotal.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }));
                } else {
                    $('#estimated_amount').val('');
                    $('#estimated_amount_display').val('');
                }
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
                var formattedQty = $selected.data('formatted-qty');

                if (!$(this).val()) {
                    $('#category_id').val(null).trigger('change');
                    $('#unit_id').html('<option value="">Select Unit</option>').val(null).trigger('change');
                    $('#current_stock_qty').val('');
                    return;
                }

                $('#category_id').val(String(categoryId || '')).trigger('change');
                $('#unit_id').html('<option value="' + (unitId || '') + '" selected>' + (unitName || '') + '</option>').val(String(unitId || '')).trigger('change');
                $('#current_stock_qty').val(formattedQty || currentQty || '0');
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

