@extends('layout.main')
@section('title') {{'New Sale | POS'}} @endsection

@section('content')
 <script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>

    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card" style="margin-top: 5px;">
                        <div class="card-header with-border">
                            <span class="card-title mobile-page-title" style="font-size:20px; font-weight: 700; color: #0f172a;"><i class="fas fa-cash-register text-primary me-2 mr-2"></i>New Sale</span>
                            <a href="{{route('sales.index')}}" class="btn btn-modern-header mobile-header-action float-end" style="float:right">
                                <i class="fas fa-list me-1 mr-1"></i> View Sales
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
                                    <div class="sale-section-panel mb-3">
                                        {{-- Row 1: Outlet Selection (Positioned above scanner and metadata) --}}
                                        <div class="row align-items-center mb-2 pb-2 border-bottom">
                                            <div class="col-md-6 col-12 mb-2 mb-md-0">
                                                @if(!empty($canChangeOutlet) && isset($outlets) && $outlets->count() > 1)
                                                    <div class="d-flex align-items-center w-100">
                                                        <label for="outlet_id" class="mr-2 mb-0 text-nowrap fw-bold text-dark small flex-shrink-0" style="min-width: 55px;">
                                                            <i class="fas fa-store text-primary mr-1"></i> Outlet:
                                                        </label>
                                                        <div class="flex-grow-1 select2-wrapper" style="min-width: 0;">
                                                            <select name="outlet_id" id="outlet_id" class="form-control select2" required style="width: 100%;">
                                                                @foreach($outlets as $outlet)
                                                                    <option value="{{ $outlet->id }}" {{ (int) $outlet->id === (int) $currentOutletId ? 'selected' : '' }}>
                                                                        {{ $outlet->name }} {{ $outlet->is_central ? '(Central Warehouse)' : '(Retail Branch)' }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                @else
                                                    <input type="hidden" name="outlet_id" id="outlet_id" value="{{ $currentOutletId }}">
                                                    <div class="d-inline-flex align-items-center py-1 px-3 bg-white rounded border" style="min-height: 38px;">
                                                        <i class="fas fa-store text-primary mr-2 flex-shrink-0"></i>
                                                        <span class="text-muted small mr-1 flex-shrink-0">Outlet:</span>
                                                        <strong class="text-dark mr-2 text-truncate" style="min-width: 0;" title="{{ $currentOutlet->name ?? 'Retail Branch' }}">{{ $currentOutlet->name ?? 'Retail Branch' }}</strong>
                                                        @if(isset($currentOutlet) && $currentOutlet->is_central)
                                                            <span class="badge badge-secondary bg-secondary flex-shrink-0 ml-auto">Central Warehouse</span>
                                                        @else
                                                            <span class="badge badge-success bg-success flex-shrink-0 ml-auto">Retail Branch</span>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Row 2: Barcode Scanner, Sale #, Stock, Date --}}
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

                                            <div class="col-md-2 col-6 mb-2 mb-md-0">
                                                <div class="input-group input-group-sm">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text bg-light fw-bold text-muted" style="font-size: 12px;">Sale #</span>
                                                    </div>
                                                    <input class="form-control fw-bold text-dark bg-light" name="sale_no" type="text" value="{{ $sale_no }}" id="sale_no" readonly>
                                                </div>
                                            </div>

                                            <div class="col-md-3 col-6 mb-2 mb-md-0 text-md-right">
                                                <div class="input-group input-group-sm justify-content-md-end">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text bg-light text-muted fw-bold" style="font-size: 12px;">Stock</span>
                                                    </div>
                                                    <input class="form-control bg-light fw-bold text-primary text-center" name="current_stock_qty" type="text" id="current_stock_qty" readonly placeholder="--" style="max-width: 140px;">
                                                </div>
                                            </div>

                                            <div class="col-md-3 col-12 mb-2 mb-md-0">
                                                <div class="input-group input-group-sm">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text bg-light text-muted" style="font-size: 12px;"><i class="fas fa-calendar-alt"></i></span>
                                                    </div>
                                                    <input class="form-control" value="{{ $date }}" name="date" type="date" id="date" {{ !empty($preventBackdatedInvoices) ? 'min='.$date : '' }}>
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
                                                            $outletQty = (float) (isset($currentOutletId) && $currentOutletId ? $product->quantityForOutlet($currentOutletId) : ($product->quantity ?? 0));
                                                        @endphp
                                                        <option value="{{ $product->id }}"
                                                            data-base-name="{{ $product->name }}"
                                                            data-category-id="{{ $product->category_id }}"
                                                            data-category-name="{{ $product->category->name ?? '' }}"
                                                            data-unit-id="{{ $product->unit_id }}"
                                                            data-unit-name="{{ $product->unit->name ?? '' }}"
                                                            data-price="{{ (float) ($product->selling_price ?? 0) }}"
                                                            data-stocks="{{ json_encode($product->outletStocks->pluck('quantity', 'outlet_id')) }}"
                                                            data-qty="{{ $outletQty }}"
                                                            data-formatted-qty="{{ $product->has_sub_unit ? $product->formatQuantity($outletQty) : $outletQty }}"
                                                            data-barcode="{{ $product->barcode ?? '' }}"
                                                            data-has-sub-unit="{{ $product->has_sub_unit ? 1 : 0 }}"
                                                            data-sub-unit-id="{{ $product->sub_unit_id ?? '' }}"
                                                            data-sub-unit-name="{{ $product->subUnit->name ?? '' }}"
                                                            data-pieces-per-pack="{{ (int) ($product->pieces_per_pack ?? 1) }}"
                                                            data-sub-unit-selling-price="{{ (float) ($product->sub_unit_selling_price ?? ($product->pieces_per_pack > 0 ? round($product->selling_price / $product->pieces_per_pack, 2) : 0)) }}">
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
                                                        <th style="width: 30%;" class="text-start ps-3 pl-3">Item Description</th>
                                                        <th style="width: 27%;">Quantity (Bag + Piece)</th>
                                                        <th style="width: 14%;" class="text-end text-right">Unit Price</th>
                                                        <th style="width: 22%;" class="text-end text-right">Total (Ghc)</th>
                                                        <th style="width: 7%;" class="text-center">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="addRow" class="addRow"></tbody>
                                                <tbody>
                                                    <tr>
                                                        <td colspan="3" class="text-end text-right fw-bold align-middle">Discount (Ghc)</td>
                                                        <td>
                                                            <input type="text" name="discount_amount" id="discount_amount" class="form-control estimated_amount text-end text-right" placeholder="0.00" value="" {{ empty($allowManualDiscountEntryByStaff) ? 'readonly' : '' }}>
                                                            @if (empty($allowManualDiscountEntryByStaff))
                                                                <small class="text-muted d-block text-end text-right" style="font-size: 11px;">Manual discount disabled.</small>
                                                            @endif
                                                        </td>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="3" class="text-end text-right fw-bold align-middle">Taxable Subtotal</td>
                                                        <td>
                                                            <input style="text-align: right; background-color: #f8fafc;" type="text" name="taxable_amount" value="" placeholder="0.00" id="taxable_amount" class="form-control" readonly>
                                                        </td>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="3" class="text-end text-right text-muted align-middle" style="font-size: 12px;">NHIL (2.5%)</td>
                                                        <td>
                                                            <input style="text-align: right; background-color: #f8fafc;" type="text" name="nhil_amount" value="" placeholder="0.00" id="nhil_amount" class="form-control" readonly>
                                                        </td>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="3" class="text-end text-right text-muted align-middle" style="font-size: 12px;">GETFund (2.5%)</td>
                                                        <td>
                                                            <input style="text-align: right; background-color: #f8fafc;" type="text" name="getfund_amount" value="" placeholder="0.00" id="getfund_amount" class="form-control" readonly>
                                                        </td>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="3" class="text-end text-right text-muted align-middle" style="font-size: 12px;">VAT (15%)</td>
                                                        <td>
                                                            <input style="text-align: right; background-color: #f8fafc;" type="text" name="vat_amount" value="" placeholder="0.00" id="vat_amount" class="form-control" readonly>
                                                        </td>
                                                        <td></td>
                                                    </tr>
                                                    <tr style="background-color: #f1f5f9;">
                                                        <td colspan="3" class="text-end text-right fw-bold align-middle" style="font-size: 15px; color: #0f172a;">Grand Total (Ghc)</td>
                                                        <td>
                                                            <input style="text-align: right; font-size: 16px; font-weight: 800; color: #0f172a; background-color: #e2e8f0;" type="text" name="estimated_amount" value="" placeholder="0.00" id="estimated_amount" class="form-control estimated_amount" readonly>
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
                                            <div class="payment-selection-title"><i class="fas fa-credit-card text-primary me-1 mr-1"></i> Payment & Customer</div>
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
                                                <label class="small fw-bold mb-1"><strong>Customer Account <span class="text-danger">*</span></strong></label>
                                                <select name="customer_id" id="customer_id" class="form-control payment-control">
                                                    <option value="" selected disabled>Select Customer</option>
                                                    @foreach($costomer as $cust)
                                                        @php
                                                            $normName = strtolower(trim($cust->name));
                                                            $isCash = in_array($normName, ['cash_sales', 'cash sales', 'cash'], true) ? '1' : '0';
                                                        @endphp
                                                        <option value="{{ $cust->id }}" data-is-cash="{{ $isCash }}">
                                                            {{ $cust->name }}
                                                        </option>
                                                    @endforeach
                                                    <option value="0">+ Create New Customer</option>
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
                                                <input type="text" name="change_due" value="" placeholder="0.00" id="change_due" class="form-control payment-money-input payment-change-input" readonly>
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
                                    <div class="form-group mt-4 pt-3 border-top text-center">
                                        <a href="{{ route('sales.index') }}" class="btn btn-modern-cancel mr-2 me-2">
                                            <i class="fas fa-times me-1 mr-1"></i> Cancel
                                        </a>
                                        <button type="submit" class="btn btn-modern-primary storeButton mobile-submit-btn" id="storeButton">
                                            <i class="fas fa-check-circle me-1 mr-1"></i> Submit Sale
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

        .select2-wrapper {
            flex: 1 1 0% !important;
            min-width: 0 !important;
        }

        .select2-wrapper .select2-container {
            width: 100% !important;
            max-width: 100% !important;
        }

        .select2-wrapper .select2-selection--single {
            height: 38px !important;
            display: flex !important;
            align-items: center !important;
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
        const ALLOW_NEGATIVE_STOCK = @json((bool) ($allowNegativeStock ?? false));
        const PREVENT_BACKDATED_INVOICES = @json((bool) ($preventBackdatedInvoices ?? false));
        const ALLOW_MANUAL_DISCOUNT_ENTRY_BY_STAFF = @json((bool) ($allowManualDiscountEntryByStaff ?? true));

        function parseMoney(value) {
            var amount = Number(String(value == null ? '' : value).replace(/,/g, '').trim());
            return Number.isFinite(amount) ? amount : 0;
        }

        function round2(num) {
            return (Math.round((num + Number.EPSILON) * 100) / 100).toFixed(2);
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

            var rawCashTendered = ($('#cash_tendered').val() || '').trim();

            if (rawCashTendered !== '' && !isNaN(cashTendered)) {
                var changeDue = Math.max(0, cashTendered - amountToCollect);
                $('#change_due').val(round2(changeDue));
            } else {
                $('#change_due').val('');
            }
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
            <input type="hidden" name="category_id[]" value="@{{category_id}}">
            <input type="hidden" name="barcode[]" value="@{{barcode}}">
            <input type="hidden" name="unit_id[]" value="@{{unit_id}}">
            <input type="hidden" name="product_id[]" value="@{{product_id}}">
            <input type="hidden" name="available_stock_qty[]" class="available_stock_qty" value="@{{ available_qty }}">
            
            <td class="align-middle text-start ps-3 pl-3" style="width: 30%;">
                <div class="fw-bold text-dark font-weight-bold" style="font-size: 14px; color: #0f172a;">@{{ product_name }}</div>
                <div class="d-flex flex-wrap align-items-center gap-1 mt-1">
                    @{{#if category_name}}
                        <span class="badge bg-light text-secondary border py-1 px-2" style="font-size: 10px;">@{{ category_name }}</span>
                    @{{/if}}
                    @{{#if barcode}}
                        <span class="badge bg-light text-muted border font-monospace py-1 px-2" style="font-size: 10px;"><i class="fas fa-barcode me-1 mr-1"></i>@{{ barcode }}</span>
                    @{{/if}}
                    <span class="badge bg-light text-dark border py-1 px-2" style="font-size: 10px;">Per @{{ unit_name }}</span>
                </div>
            </td>

            <td class="align-middle" style="width: 27%;">
                @{{#if has_sub_unit}}
                    <div class="packaging-qty-inline-wrap">
                        <input type="number" min="0" step="1" class="form-control packaging-qty-input pack_qty" name="pack_qty[]" value="" placeholder="0" title="Pack Qty (@{{unit_name}})">
                        <span class="packaging-qty-plus">+</span>
                        <input type="number" min="0" step="1" class="form-control packaging-qty-input piece_qty" name="piece_qty[]" value="" placeholder="0" title="Piece Qty (@{{sub_unit_name}})">
                    </div>

                    <input type="hidden" class="selling_qty" name="selling_qty[]" value="">
                    <input type="hidden" class="pieces_per_pack" name="pieces_per_pack[]" value="@{{pieces_per_pack}}">
                    <input type="hidden" class="sub_unit_selling_price" name="sub_unit_selling_price[]" value="@{{sub_unit_selling_price}}">
                    <input type="hidden" class="sub_unit_name" value="@{{sub_unit_name}}">
                    <input type="hidden" class="has_sub_unit" value="1">
                @{{else}}
                    <input type="number" min="1" step="1" class="form-control standard-qty-input text-right text-end selling_qty" id="selling_qty" name="selling_qty[]" value="" placeholder="0" onchange="checkQuantity()" oninput="$(this).css('border-color', '')">
                    <input type="hidden" class="pack_qty" name="pack_qty[]" value="">
                    <input type="hidden" class="piece_qty" name="piece_qty[]" value="">
                    <input type="hidden" class="pieces_per_pack" name="pieces_per_pack[]" value="1">
                    <input type="hidden" class="sub_unit_selling_price" name="sub_unit_selling_price[]" value="0">
                    <input type="hidden" class="sub_unit_name" value="">
                    <input type="hidden" class="has_sub_unit" value="0">
                @{{/if}}
            </td>

            <td class="align-middle text-end text-right" style="width: 14%;">
                <input type="number" 
                    class="form-control unit_selling_price text-right text-end" 
                    name="unit_selling_price[]"
                    value="@{{unit_selling_price}}" 
                    placeholder="0.00"
                    readonly 
                    style="background-color:#f8fafc; height: 38px; font-size: 14px; font-weight: 700; padding: 4px 6px;">
            </td>

            <td class="align-middle text-end text-right" style="width: 22%;">
                <input type="number" class="form-control selling_price text-right text-end" name="selling_price[]" value="" placeholder="0.00" readonly style="background-color:#f1f5f9; height: 38px; font-size: 15px; font-weight: 800; color: #0f172a; padding: 4px 10px;"> 
            </td>

            <td class="align-middle text-center" style="width: 7%;">
                <button type="button" class="btn btn-outline-danger btn-sm removeeventmore" title="Remove Item" style="border-radius: 6px; padding: 6px 10px;">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>

        </tr>

    </script>

    {{-- handles add more item in purchase --}}
    <script type="text/javascript">
        $(document).ready(function(){
            $(document).on('change', '#outlet_id', function() {
                var selectedOutlet = $(this).val();
                if ($('#addRow tr').length > 0) {
                    if (confirm('Changing the outlet will clear current invoice items because available stock varies by outlet. Continue?')) {
                        $('#addRow').empty();
                        if (typeof totalAmountPrice === 'function') totalAmountPrice();
                    } else {
                        var prev = $(this).data('previous-outlet') || '{{ $currentOutletId }}';
                        $(this).val(prev);
                        if ($.fn.select2) {
                            $(this).trigger('change.select2');
                        }
                        return;
                    }
                }
                $(this).data('previous-outlet', selectedOutlet);

                $('#product_id option').each(function() {
                    var $opt = $(this);
                    var val = $opt.val();
                    if (!val) return;
                    var stocks = $opt.data('stocks') || {};
                    var qty = stocks[selectedOutlet] !== undefined ? parseFloat(stocks[selectedOutlet]) : 0;
                    $opt.attr('data-qty', qty);
                    $opt.data('qty', qty);
                    var baseName = $opt.data('base-name') || $opt.text().replace(/\s*\(Available:.*\)$/, '').trim();
                    $opt.data('base-name', baseName);
                    var hasSubUnit = $opt.data('has-sub-unit') == 1;
                    var piecesPerPack = parseInt($opt.data('pieces-per-pack')) || 1;
                    var unitName = $opt.data('unit-name') || '';
                    var subUnitName = $opt.data('sub-unit-name') || 'Piece';
                    var formattedDisplay = formatStockDisplay(qty, hasSubUnit, piecesPerPack, unitName, subUnitName);
                    $opt.data('formatted-qty', formattedDisplay);
                    $opt.text(baseName);
                });
                if ($.fn.select2) {
                    $('#product_id').trigger('change.select2');
                }
                var $selectedProd = $('#product_id').find('option:selected');
                if ($selectedProd.val()) {
                    $('#current_stock_qty').val($selectedProd.data('qty') || 0);
                }
            });

            function round2(num) {
                return (Math.round((num + Number.EPSILON) * 100) / 100).toFixed(2);
            }

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

                var rawCashTendered = ($('#cash_tendered').val() || '').trim();

                if (rawCashTendered !== '' && !isNaN(cashTendered)) {
                    var changeDue = Math.max(0, cashTendered - amountToCollect);
                    $('#change_due').val(round2(changeDue));
                } else {
                    $('#change_due').val('');
                }
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
                    data: { barcode: barcode, outlet_id: $('#outlet_id').val() },
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
                            var hasSubUnit = (product.has_sub_unit == 1 || product.has_sub_unit === true);
                            var piecesPerPack = parseInt(product.pieces_per_pack) || 1;
                            var subUnitName = (product.sub_unit && product.sub_unit.name) ? product.sub_unit.name : (product.sub_unit_name || 'Pc');
                            var subUnitPrice = parseFloat(product.sub_unit_selling_price) || (piecesPerPack > 0 ? Number((product.selling_price / piecesPerPack).toFixed(2)) : 0);
                            var availDisplay = formatStockDisplay(availableQty, hasSubUnit, piecesPerPack, product.unit ? product.unit.name : '', subUnitName);
                            var initialTotal = parseFloat(product.selling_price) || 0;

                            // Prepare data for Handlebars row
                            var source = $("#document-template").html();
                            var template = Handlebars.compile(source);
                            var data = {
                                date: date,
                                sale_no: sale_no,
                                barcode: product.barcode,
                                category_id: product.category_id,
                                category_name: product.category.name,
                                product_id: product.id,
                                product_name: product.name,
                                available_qty: availableQty,
                                available_display: availDisplay,
                                unit_id: product.unit_id,
                                unit_name: product.unit.name,
                                unit_selling_price: (product.selling_price > 0 ? product.selling_price : ''),
                                has_sub_unit: hasSubUnit,
                                pieces_per_pack: piecesPerPack,
                                sub_unit_name: subUnitName,
                                sub_unit_selling_price: (subUnitPrice > 0 ? subUnitPrice : ''),
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
                var sale_no = $('#sale_no').val();
                var product_id = $('#product_id').val();
                var selectedProductOption = $('#product_id').find('option:selected');
                var category_id  = selectedProductOption.data('category-id');
                var category_name = selectedProductOption.data('category-name') || '';
                var product_name = selectedProductOption.data('base-name') || selectedProductOption.text().trim();
                var selling_price = selectedProductOption.data('price') || 0;
                var available_qty = selectedProductOption.data('qty');
                var available_qty_num = parseFloat(available_qty);
                var unit_id  = selectedProductOption.data('unit-id');
                var unit_name = selectedProductOption.data('unit-name') || '';
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

                var selectedProductOption = $('#product_id').find('option:selected');
                var hasSubUnit = selectedProductOption.data('has-sub-unit') == 1;
                var piecesPerPack = parseInt(selectedProductOption.data('pieces-per-pack')) || 1;
                var subUnitName = selectedProductOption.data('sub-unit-name') || 'Pc';
                var subUnitPrice = parseFloat(selectedProductOption.data('sub-unit-selling-price')) || (piecesPerPack > 0 ? Number((selling_price / piecesPerPack).toFixed(2)) : 0);
                var availDisplay = formatStockDisplay(available_qty_num, hasSubUnit, piecesPerPack, unit_name, subUnitName);
                var initialTotal = parseFloat(selling_price) || 0;

                var source = $("#document-template").html();
                var template = Handlebars.compile(source);
                var data = {
                    date: date,
                    sale_no: sale_no, 
                    category_id: category_id,
                    category_name: category_name,
                    product_id: product_id,
                    product_name: product_name,
                    available_qty: available_qty_num,
                    available_display: availDisplay,
                    unit_id: unit_id,
                    unit_name: unit_name,
                    barcode: selectedProductOption.data('barcode') || '',
                    unit_selling_price: (selling_price > 0 ? selling_price : ''),
                    has_sub_unit: hasSubUnit,
                    pieces_per_pack: piecesPerPack,
                    sub_unit_name: subUnitName,
                    sub_unit_selling_price: (subUnitPrice > 0 ? subUnitPrice : ''),
                    initial_line_total: ''
                };
                var html = template(data);
                $("#addRow").append(html);
                totalAmountPrice();
                resetProductSelectionFields();
                $('#barcode').val('').focus();
            });

            $(document).on("click",".removeeventmore",function(event){
                $(this).closest(".delete_add_more_item").remove();
                totalAmountPrice();
            });

            // 'input' also covers mouse wheel changes on number inputs
            $(document).on('input change keyup click','.unit_selling_price,.selling_qty,.pack_qty,.piece_qty', function(){
                var $row = $(this).closest("tr");
                var hasSubUnit = $row.find('.has_sub_unit').val() === '1';
                
                if (hasSubUnit) {
                    var packQtyRaw = $row.find('.pack_qty').val();
                    var pieceQtyRaw = $row.find('.piece_qty').val();
                    var packPrice = parseFloat($row.find('.unit_selling_price').val()) || 0;
                    var subUnitPrice = parseFloat($row.find('.sub_unit_selling_price').val()) || 0;
                    var piecesPerPack = parseInt($row.find('.pieces_per_pack').val()) || 1;
                    
                    var packQty = parseFloat(packQtyRaw) || 0;
                    var pieceQty = parseFloat(pieceQtyRaw) || 0;
                    var totalBaseQty = (packQty * piecesPerPack) + pieceQty;
                    $row.find('.selling_qty').val(totalBaseQty > 0 ? totalBaseQty : '');
                    
                    var lineTotal = (packQty * packPrice) + (pieceQty * subUnitPrice);
                    $row.find('.selling_price').val(lineTotal > 0 ? lineTotal.toFixed(2) : '');
                } else {
                    var unit_selling_price = parseFloat($row.find("input.unit_selling_price").val()) || 0;
                    var qty = parseFloat($row.find("input.selling_qty").val()) || 0;
                    var lineTotal = unit_selling_price * qty;
                    $row.find("input.selling_price").val(lineTotal > 0 ? lineTotal.toFixed(2) : '');
                }
                
                checkQuantity();
                totalAmountPrice();
            });

            $(document).on('keyup','#discount_amount',function(){
                totalAmountPrice();
            });

            $(document).on('keyup change input','#cash_tendered', function(){
                updateTenderSummary();
            });

            // Calculate sum of amout in Sale 

            function totalAmountPrice(){
                var grossTotal = 0;
                var hasAnyPrice = false;
                $(".selling_price").each(function(){
                    var value = $(this).val();
                    if(!isNaN(value) && value.length != 0 && parseFloat(value) > 0){
                        grossTotal += parseFloat(value);
                        hasAnyPrice = true;
                    }
                });

                var discountAmount = parseFloat($('#discount_amount').val()) || 0;
                if (!ALLOW_MANUAL_DISCOUNT_ENTRY_BY_STAFF) {
                    discountAmount = 0;
                    $('#discount_amount').val('');
                }
                if (discountAmount < 0) {
                    discountAmount = 0;
                }

                var netTotal = grossTotal - discountAmount;
                if (netTotal < 0) {
                    netTotal = 0;
                }

                if (hasAnyPrice || grossTotal > 0) {
                    var taxableAmount = netTotal / 1.20;
                    var nhilAmount = taxableAmount * 0.025;
                    var getfundAmount = taxableAmount * 0.025;
                    var vatAmount = taxableAmount * 0.15;

                    $('#taxable_amount').val(round2(taxableAmount));
                    $('#nhil_amount').val(round2(nhilAmount));
                    $('#getfund_amount').val(round2(getfundAmount));
                    $('#vat_amount').val(round2(vatAmount));
                    $('#estimated_amount').val(formatMoney(netTotal));
                } else {
                    $('#taxable_amount').val('');
                    $('#nhil_amount').val('');
                    $('#getfund_amount').val('');
                    $('#vat_amount').val('');
                    $('#estimated_amount').val('');
                }

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

    {{-- check quantity script --}}
    <script>
        function checkQuantity() {
            if (ALLOW_NEGATIVE_STOCK) {
                return;
            }

            var hasNegativeStock = false;

            $('#addRow tr').each(function() {
                var $row = $(this);
                var qtyField = $row.find('input.selling_qty');
                var qty = parseFloat(qtyField.val()) || 0;
                var availableQty = parseFloat($row.find('input.available_stock_qty').val()) || 0;

                if (qty > availableQty && qty > 0) {
                    hasNegativeStock = true;
                    if ($row.find('.has_sub_unit').val() === '1') {
                        $row.find('.pack_qty, .piece_qty').css('border-color', '#dc3545');
                    } else {
                        qtyField.css('border-color', '#dc3545');
                    }
                } else {
                    $row.find('.pack_qty, .piece_qty, input.selling_qty').css('border-color', '');
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

        function syncSalePaymentUI() {
            var paid_status = $('#paid_status').val();
            var $customerSelect = $('#customer_id');
            var $cashOption = $customerSelect.find('option[data-is-cash="1"]');
            var selectedCustomer = $customerSelect.val();
            var paymentStatusChanged = paid_status !== previousSalePaymentStatus;

            if (paid_status === 'partial_payment') {
                $('#payment_amount_fields').css('display', 'grid');
                $('#paid_amount_group').show();
                $('#cash_tendered_group, #change_due_group').hide();
                $('.paid_amount').prop('required', true);
                $('#cash_tendered').prop('readonly', true).prop('required', false);
                $('#cash_tendered_required').hide();
                if (paymentStatusChanged) {
                    $('#cash_tendered').val('');
                }
            } else if (paid_status === 'full_payment') {
                $('#payment_amount_fields').css('display', 'grid');
                $('#paid_amount_group').hide();
                $('#cash_tendered_group, #change_due_group').show();
                $('.paid_amount').prop('required', false).val('');
                $('#cash_tendered_required').show();
                $('#cash_tendered').prop('readonly', false).prop('required', true);
                if (paymentStatusChanged) {
                    $('#cash_tendered').val('');
                }
            } else if (paid_status === 'full_credit') {
                $('#payment_amount_fields').hide();
                $('#paid_amount_group, #cash_tendered_group, #change_due_group').hide();
                $('.paid_amount').prop('required', false).val('');
                $('#cash_tendered').val('0').prop('readonly', true).prop('required', false);
                $('#cash_tendered_required').hide();
                $('#change_due').val('');
            } else {
                $('#payment_amount_fields').hide();
                $('#paid_amount_group, #cash_tendered_group, #change_due_group').hide();
                $('.paid_amount').prop('required', false).val('');
                $('#cash_tendered').val('').prop('readonly', false).prop('required', false);
                $('#cash_tendered_required').hide();
                $('#change_due').val('');
            }

            if (!$cashOption.length) {
                $cashOption = $customerSelect.find('option').filter(function() {
                    var txt = $(this).text().toLowerCase().trim();
                    return txt === 'cash_sales' || txt === 'cash sales' || txt === 'cash';
                });
            }

            if ($cashOption.length) {
                if (paid_status === 'full_payment') {
                    $cashOption.prop('disabled', false);
                    if (!selectedCustomer || paymentStatusChanged) {
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
            updateTenderSummary();
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

