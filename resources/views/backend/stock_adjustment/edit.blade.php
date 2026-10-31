@php use Illuminate\Support\Facades\Crypt; @endphp
@extends('layout.main')
@section('title') {{ 'Edit Stock Adjustment' }} @endsection

@section('content')
<script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>

<style>
    .btn-modern-header {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #ffffff;
        color: #0F766E;
        border: 1.5px solid #cbd5e1;
        font-weight: 600;
        font-size: 13.5px;
        border-radius: 8px;
        padding: 8px 18px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none !important;
    }
    .btn-modern-header:hover {
        background: #f0fdfa;
        color: #0F766E;
        border-color: #0F766E;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(15, 118, 110, 0.15);
    }
    .btn-modern-cancel {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #ffffff;
        color: #475569;
        border: 1.5px solid #cbd5e1;
        font-weight: 600;
        font-size: 14px;
        border-radius: 10px;
        padding: 9px 26px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none !important;
    }
    .btn-modern-cancel:hover {
        background: #f8fafc;
        color: #1e293b;
        border-color: #94a3b8;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    }
    .btn-modern-primary {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: linear-gradient(135deg, #0F766E 0%, #0d9488 100%);
        color: #ffffff;
        border: none;
        font-weight: 600;
        font-size: 14.5px;
        border-radius: 10px;
        padding: 10px 36px;
        letter-spacing: 0.2px;
        box-shadow: 0 4px 14px rgba(15, 118, 110, 0.35);
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .btn-modern-primary:hover {
        background: linear-gradient(135deg, #115e59 0%, #0F766E 100%);
        color: #ffffff;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(15, 118, 110, 0.45);
    }
    .btn-modern-primary:active {
        transform: translateY(0);
        box-shadow: 0 2px 8px rgba(15, 118, 110, 0.25);
    }
    .btn-modern-primary:disabled {
        opacity: 0.65;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }
    #adjustment-lines-table .select2-container--default .select2-selection--single {
        border: 1.5px solid #cbd5e1 !important;
        border-radius: 8px !important;
        height: 38px !important;
        display: flex;
        align-items: center;
    }
    #adjustment-lines-table .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px !important;
        padding-left: 10px;
        color: #0f172a;
        font-weight: 600;
    }
    #adjustment-lines-table .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
        right: 8px;
    }
</style>

<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0" style="border-radius: 14px; overflow: hidden;">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <span class="card-title mb-0" style="font-size:20px; font-weight: 700; color: #0f172a;">
                            <i class="fas fa-sliders-h text-primary me-2 mr-2"></i>Edit Stock Adjustment
                        </span>
                        <a href="{{ route('stock-adjustments.index') }}" class="btn btn-modern-header">
                            <i class="fas fa-list me-1 mr-1"></i> View Adjustments
                        </a>
                    </div>

                    <div class="card-body p-4">
                        <form action="{{ route('stock-adjustments.update', Crypt::encrypt($adjustment->id)) }}" method="POST" id="myForm" novalidate>
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="physical_count_mode" value="1">

                            <div class="alert alert-danger alert-dismissible fade show" id="errorAlert" style="display: none; border-radius: 10px;">
                                <button type="button" class="close h-100" data-dismiss="alert" aria-label="Close">
                                    <span><i class="mdi mdi-close"></i></span>
                                </button>
                                <ul id="errors" class="mb-0"></ul>
                            </div>

                            <!-- Header Settings Card -->
                            <div class="p-3 mb-4 rounded-3 border bg-light-subtle shadow-xs">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label text-muted small fw-bold text-uppercase">Branch / Outlet</label>
                                        <div class="p-2 px-3 rounded-2 bg-white border fw-bold text-dark d-flex align-items-center" style="height: 42px; border: 1.5px solid #cbd5e1 !important;">
                                            <i class="fas fa-store text-primary me-2 mr-2"></i> {{ optional($adjustment->outlet)->name ?: 'Central Warehouse' }}
                                        </div>
                                        <input type="hidden" id="outlet_id" name="outlet_id" value="{{ $adjustment->outlet_id }}">
                                    </div>

                                    <div class="col-md-3">
                                        <label for="adjustment_date" class="form-label text-muted small fw-bold text-uppercase">Date <span class="text-danger">*</span></label>
                                        <input type="date" id="adjustment_date" name="adjustment_date" class="form-control fw-semibold" value="{{ old('adjustment_date', optional($adjustment->adjustment_date)->format('Y-m-d')) }}" style="height: 42px; border: 1.5px solid #cbd5e1; border-radius: 8px;">
                                    </div>

                                    <div class="col-md-2">
                                        <label for="reference_no" class="form-label text-muted small fw-bold text-uppercase">Reference No</label>
                                        <input type="text" id="reference_no" name="reference_no" class="form-control" value="{{ old('reference_no', $adjustment->reference_no) }}" placeholder="optional" style="height: 42px; border: 1.5px solid #cbd5e1; border-radius: 8px;">
                                    </div>

                                    <div class="col-md-2">
                                        <label for="adjustment_type" class="form-label text-muted small fw-bold text-uppercase">Type <span class="text-danger">*</span></label>
                                        <input type="text" id="adjustment_type_label" class="form-control fw-bold bg-white" value="{{ ucfirst($adjustment->adjustment_type) }} (Auto)" readonly style="height: 42px; border: 1.5px solid #cbd5e1; border-radius: 8px; color: #0284c7;">
                                        <input type="hidden" id="adjustment_type" name="adjustment_type" value="{{ old('adjustment_type', $adjustment->adjustment_type) }}">
                                    </div>

                                    <div class="col-md-2">
                                        <label for="reason" class="form-label text-muted small fw-bold text-uppercase">Reason</label>
                                        <input type="text" id="reason" name="reason" class="form-control" value="{{ old('reason', $adjustment->reason) }}" placeholder="e.g. Audit" style="height: 42px; border: 1.5px solid #cbd5e1; border-radius: 8px;">
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <label for="notes" class="form-label text-muted small fw-bold text-uppercase">Adjustment Notes</label>
                                    <textarea id="notes" name="notes" class="form-control" rows="2" placeholder="Audit observations or stock variance explanations..." style="border: 1.5px solid #cbd5e1; border-radius: 8px;">{{ old('notes', $adjustment->notes) }}</textarea>
                                </div>
                            </div>

                            <!-- Spacious Modern Adjustment Lines Table -->
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="fw-bold mb-0 text-dark" style="font-size: 16px;">
                                    <i class="fas fa-list-check text-primary me-2 mr-1"></i>Adjustment Lines
                                </h5>
                                <span class="text-muted small">Enter physical count in boxes and loose pieces. Difference and valuations calculate live.</span>
                            </div>

                            <div class="table-responsive mb-3 shadow-xs" style="border: 1px solid #cbd5e1; border-radius: 10px; background: #fff;">
                                <table class="table table-bordered table-striped align-middle mb-0" id="adjustment-lines-table" style="min-width: 1200px;">
                                    <thead class="table-dark" style="background-color: #0f172a;">
                                        <tr>
                                            <th style="width: 25%; min-width: 230px;" class="ps-3 pl-3">Product Name</th>
                                            <th style="width: 15%; min-width: 150px;" class="text-center">Current Stock</th>
                                            <th style="width: 23%; min-width: 240px;" class="text-center physical-count-col">Physical Count</th>
                                            <th style="width: 10%; min-width: 100px;" class="text-center">Difference</th>
                                            <th style="width: 11%; min-width: 120px;" class="text-end text-right">Unit Cost</th>
                                            <th style="width: 11%; min-width: 120px;" class="text-end text-right">Amount</th>
                                            <th style="min-width: 140px;">Description</th>
                                            <th style="width: 5%; text-align: center;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($adjustment->items as $item)
                                            @php
                                                $p = $item->product;
                                                $hasSub = $p && $p->has_sub_unit && ($p->pieces_per_pack ?: 1) > 1;
                                                $ppp = $p?->pieces_per_pack ?: 1;
                                                $unitName = $p?->unit?->name ?? 'Pack';
                                                $subUnitName = $p?->subUnit?->name ?? 'Piece';
                                                $rawCurrentQty = $p ? (float) $p->quantityForOutlet($adjustment->outlet_id) : 0;
                                                $afterQty = (float) $item->after_qty;
                                                $initPacks = $hasSub ? (int) floor($afterQty / $ppp) : 0;
                                                $initPieces = $hasSub ? (int) round(fmod($afterQty, $ppp)) : 0;
                                                $currentDisplay = $hasSub 
                                                    ? $p->formatQuantity($rawCurrentQty) . ' (' . ((int)$rawCurrentQty == $rawCurrentQty ? (int)$rawCurrentQty : number_format($rawCurrentQty, 2)) . ' ' . $subUnitName . ')'
                                                    : ($p ? $p->formatQuantity($rawCurrentQty) : number_format($rawCurrentQty, 2));
                                            @endphp
                                            <tr class="line-row" data-raw-current-qty="{{ $rawCurrentQty }}">
                                                <td class="p-2 ps-3 pl-3">
                                                    <select name="product_id[]" class="form-control product-select" required style="height: 38px;">
                                                        <option value="">-- Select Product --</option>
                                                        @foreach($products as $product)
                                                            @php
                                                                $outletStocksMap = $product->outletStocks->pluck('quantity', 'outlet_id');
                                                                $lineOutletQty = $outletStocksMap[$adjustment->outlet_id] ?? 0;
                                                                $pHasSub = $product->has_sub_unit ? 1 : 0;
                                                                $pPpp = $product->pieces_per_pack ?: 1;
                                                                $subCost = ($pHasSub && $pPpp > 1) ? (float) ($product->sub_unit_cost_price ?: ($product->cost_price / $pPpp)) : (float) ($product->cost_price ?? 0);
                                                            @endphp
                                                            <option value="{{ $product->id }}"
                                                                data-qty="{{ $lineOutletQty }}"
                                                                data-cost="{{ $product->cost_price ?? 0 }}"
                                                                data-sub-cost="{{ $subCost }}"
                                                                data-has-sub="{{ $pHasSub }}"
                                                                data-pieces-per-pack="{{ $pPpp }}"
                                                                data-unit-name="{{ $product->unit->name ?? 'Pack' }}"
                                                                data-sub-unit-name="{{ $product->subUnit->name ?? 'Piece' }}"
                                                                data-stocks='@json($outletStocksMap)'
                                                                {{ (int) $item->product_id === (int) $product->id ? 'selected' : '' }}>
                                                                {{ $product->name }} ({{ $product->barcode ?? 'N/A' }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td class="p-2 text-center">
                                                    <input type="text" class="form-control current-qty font-weight-bold text-center bg-light border-0 text-dark" style="height: 38px; font-size: 13.5px;" value="{{ $currentDisplay }}" readonly placeholder="0">
                                                </td>
                                                <td class="p-2 physical-count-col">
                                                    <div class="single-count-box" style="{{ $hasSub ? 'display: none;' : '' }}">
                                                        <input type="number" step="any" min="0" class="form-control text-center fw-bold single-physical-count-input" style="height: 38px; font-size: 14px; border: 1.5px solid #cbd5e1; border-radius: 8px;" value="{{ !$hasSub ? $afterQty : '' }}" placeholder="0">
                                                    </div>
                                                    <div class="multi-count-box" style="{{ !$hasSub ? 'display: none;' : '' }}">
                                                        <div class="d-flex align-items-center gap-2 mb-1">
                                                            <div style="flex: 1; position: relative;">
                                                                <input type="number" min="0" step="1" class="form-control text-center fw-bold pack-physical-input" style="height: 38px; font-size: 14px; font-weight: 700; border: 1.5px solid #cbd5e1; border-radius: 8px; padding-right: 44px;" value="{{ $hasSub ? $initPacks : '' }}" placeholder="0">
                                                                <span class="position-absolute end-0 top-50 translate-middle-y me-2 text-muted fw-semibold small pack-unit-badge" style="pointer-events: none; font-size: 11px;">{{ $unitName }}</span>
                                                            </div>
                                                            <span class="fw-bold text-muted px-1">+</span>
                                                            <div style="flex: 1; position: relative;">
                                                                <input type="number" min="0" step="1" class="form-control text-center fw-bold piece-physical-input" style="height: 38px; font-size: 14px; font-weight: 700; border: 1.5px solid #cbd5e1; border-radius: 8px; padding-right: 44px;" value="{{ $hasSub ? $initPieces : '' }}" placeholder="0">
                                                                <span class="position-absolute end-0 top-50 translate-middle-y me-2 text-muted fw-semibold small piece-unit-badge" style="pointer-events: none; font-size: 11px;">{{ $subUnitName }}</span>
                                                            </div>
                                                        </div>
                                                        <div class="text-center">
                                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-semibold physical-count-preview" style="font-size: 11px;">(= {{ $afterQty }} {{ $subUnitName }})</span>
                                                        </div>
                                                    </div>
                                                    <input type="hidden" name="physical_count[]" class="physical-count-input" value="{{ $afterQty }}" required>
                                                </td>
                                                <td class="p-2 text-center">
                                                    <input type="number" step="0.01" min="0.01" name="qty[]" class="form-control qty-input font-weight-bold text-center bg-light border-0" style="height: 38px; font-size: 14px; color: #0f172a;" value="{{ $item->qty }}" required readonly placeholder="0">
                                                </td>
                                                <td class="p-2">
                                                    <div class="d-flex align-items-center">
                                                        <input type="number" step="0.01" min="0" name="unit_cost[]" class="form-control unit-cost-input text-end fw-bold" style="height: 38px; font-size: 14px; border: 1.5px solid #cbd5e1; border-radius: 8px;" value="{{ $item->unit_cost }}" required placeholder="0.00">
                                                    </div>
                                                </td>
                                                <td class="p-2">
                                                    <div class="d-flex align-items-center">
                                                        <input type="number" step="0.01" min="0" name="amount[]" class="form-control amount-input text-end fw-bold bg-light border-0" style="height: 38px; font-size: 14px; color: #0f172a;" value="{{ $item->amount }}" required readonly placeholder="0.00">
                                                    </div>
                                                </td>
                                                <td class="p-2">
                                                    <input type="text" name="description[]" class="form-control form-control-sm" style="height: 38px; font-size: 13px; border: 1.5px solid #cbd5e1; border-radius: 8px;" value="{{ $item->description }}" placeholder="Reason/note...">
                                                    <div class="mt-1"><span class="badge bg-light text-secondary border computed-hint d-none" style="font-size: 11px;">Computed...</span></div>
                                                </td>
                                                <td class="p-2 text-center">
                                                    <button type="button" class="btn btn-sm btn-outline-danger remove-line" title="Remove line"><i class="fas fa-trash-alt"></i></button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr class="line-row" data-raw-current-qty="0">
                                                <td class="p-2 ps-3 pl-3">
                                                    <select name="product_id[]" class="form-control product-select" required style="height: 38px;">
                                                        <option value="">-- Select Product --</option>
                                                        @foreach($products as $product)
                                                            @php
                                                                $outletStocksMap = $product->outletStocks->pluck('quantity', 'outlet_id');
                                                                $lineOutletQty = $outletStocksMap[$adjustment->outlet_id] ?? 0;
                                                                $pHasSub = $product->has_sub_unit ? 1 : 0;
                                                                $pPpp = $product->pieces_per_pack ?: 1;
                                                                $subCost = ($pHasSub && $pPpp > 1) ? (float) ($product->sub_unit_cost_price ?: ($product->cost_price / $pPpp)) : (float) ($product->cost_price ?? 0);
                                                            @endphp
                                                            <option value="{{ $product->id }}"
                                                                data-qty="{{ $lineOutletQty }}"
                                                                data-cost="{{ $product->cost_price ?? 0 }}"
                                                                data-sub-cost="{{ $subCost }}"
                                                                data-has-sub="{{ $pHasSub }}"
                                                                data-pieces-per-pack="{{ $pPpp }}"
                                                                data-unit-name="{{ $product->unit->name ?? 'Pack' }}"
                                                                data-sub-unit-name="{{ $product->subUnit->name ?? 'Piece' }}"
                                                                data-stocks='@json($outletStocksMap)'>
                                                                {{ $product->name }} ({{ $product->barcode ?? 'N/A' }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td class="p-2 text-center">
                                                    <input type="text" class="form-control current-qty font-weight-bold text-center bg-light border-0 text-dark" style="height: 38px; font-size: 13.5px;" readonly placeholder="0">
                                                </td>
                                                <td class="p-2 physical-count-col">
                                                    <div class="single-count-box">
                                                        <input type="number" step="any" min="0" class="form-control text-center fw-bold single-physical-count-input" style="height: 38px; font-size: 14px; border: 1.5px solid #cbd5e1; border-radius: 8px;" placeholder="0">
                                                    </div>
                                                    <div class="multi-count-box" style="display: none;">
                                                        <div class="d-flex align-items-center gap-2 mb-1">
                                                            <div style="flex: 1; position: relative;">
                                                                <input type="number" min="0" step="1" class="form-control text-center fw-bold pack-physical-input" style="height: 38px; font-size: 14px; font-weight: 700; border: 1.5px solid #cbd5e1; border-radius: 8px; padding-right: 44px;" placeholder="0">
                                                                <span class="position-absolute end-0 top-50 translate-middle-y me-2 text-muted fw-semibold small pack-unit-badge" style="pointer-events: none; font-size: 11px;">Packs</span>
                                                            </div>
                                                            <span class="fw-bold text-muted px-1">+</span>
                                                            <div style="flex: 1; position: relative;">
                                                                <input type="number" min="0" step="1" class="form-control text-center fw-bold piece-physical-input" style="height: 38px; font-size: 14px; font-weight: 700; border: 1.5px solid #cbd5e1; border-radius: 8px; padding-right: 44px;" placeholder="0">
                                                                <span class="position-absolute end-0 top-50 translate-middle-y me-2 text-muted fw-semibold small piece-unit-badge" style="pointer-events: none; font-size: 11px;">Pcs</span>
                                                            </div>
                                                        </div>
                                                        <div class="text-center">
                                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-semibold physical-count-preview" style="font-size: 11px;">(= 0 pcs)</span>
                                                        </div>
                                                    </div>
                                                    <input type="hidden" name="physical_count[]" class="physical-count-input" required>
                                                </td>
                                                <td class="p-2 text-center">
                                                    <input type="number" step="0.01" min="0.01" name="qty[]" class="form-control qty-input font-weight-bold text-center bg-light border-0" style="height: 38px; font-size: 14px; color: #0f172a;" required readonly placeholder="0">
                                                </td>
                                                <td class="p-2">
                                                    <div class="d-flex align-items-center">
                                                        <input type="number" step="0.01" min="0" name="unit_cost[]" class="form-control unit-cost-input text-end fw-bold" style="height: 38px; font-size: 14px; border: 1.5px solid #cbd5e1; border-radius: 8px;" required placeholder="0.00">
                                                    </div>
                                                </td>
                                                <td class="p-2">
                                                    <div class="d-flex align-items-center">
                                                        <input type="number" step="0.01" min="0" name="amount[]" class="form-control amount-input text-end fw-bold bg-light border-0" style="height: 38px; font-size: 14px; color: #0f172a;" required readonly placeholder="0.00">
                                                    </div>
                                                </td>
                                                <td class="p-2">
                                                    <input type="text" name="description[]" class="form-control form-control-sm" style="height: 38px; font-size: 13px; border: 1.5px solid #cbd5e1; border-radius: 8px;" placeholder="Reason/note...">
                                                    <div class="mt-1"><span class="badge bg-light text-secondary border computed-hint d-none" style="font-size: 11px;">Computed...</span></div>
                                                </td>
                                                <td class="p-2 text-center">
                                                    <button type="button" class="btn btn-sm btn-outline-danger remove-line" disabled title="Remove line"><i class="fas fa-trash-alt"></i></button>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-light">
                                            <th colspan="3" class="text-end text-right fw-bold align-middle fs-6">Totals:</th>
                                            <th class="text-center align-middle fw-bold text-primary fs-6"><span id="total-qty">0.00</span></th>
                                            <th></th>
                                            <th class="text-end text-right align-middle fw-bold text-dark fs-6"><span id="total-value">0.00</span></th>
                                            <th colspan="2"></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <div class="mb-4">
                                <button type="button" id="add-line" class="btn btn-modern-primary">
                                    <i class="fas fa-plus-circle me-1 mr-1"></i> Add Line
                                </button>
                            </div>

                            <div class="row mt-4 pt-3 border-top">
                                <div class="col-12 text-center">
                                    <a href="{{ route('stock-adjustments.index') }}" class="btn btn-modern-cancel mr-2 me-2">
                                        <i class="fas fa-times me-1 mr-1"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-modern-primary px-4">
                                        <i class="fas fa-check-circle me-1 mr-1"></i> Update Adjustment
                                    </button>
                                </div>
                            </div>

                            <template id="adjustment-row-template">
                                <tr class="line-row" data-raw-current-qty="0">
                                    <td class="p-2 ps-3 pl-3">
                                        <select name="product_id[]" class="form-control product-select" required style="height: 38px;">
                                            <option value="">-- Select Product --</option>
                                            @foreach($products as $product)
                                                @php
                                                    $outletStocksMap = $product->outletStocks->pluck('quantity', 'outlet_id');
                                                    $lineOutletQty = $outletStocksMap[$adjustment->outlet_id] ?? 0;
                                                    $pHasSub = $product->has_sub_unit ? 1 : 0;
                                                    $pPpp = $product->pieces_per_pack ?: 1;
                                                    $subCost = ($pHasSub && $pPpp > 1) ? (float) ($product->sub_unit_cost_price ?: ($product->cost_price / $pPpp)) : (float) ($product->cost_price ?? 0);
                                                @endphp
                                                <option value="{{ $product->id }}"
                                                    data-qty="{{ $lineOutletQty }}"
                                                    data-cost="{{ $product->cost_price ?? 0 }}"
                                                    data-sub-cost="{{ $subCost }}"
                                                    data-has-sub="{{ $pHasSub }}"
                                                    data-pieces-per-pack="{{ $pPpp }}"
                                                    data-unit-name="{{ $product->unit->name ?? 'Pack' }}"
                                                    data-sub-unit-name="{{ $product->subUnit->name ?? 'Piece' }}"
                                                    data-stocks='@json($outletStocksMap)'>
                                                    {{ $product->name }} ({{ $product->barcode ?? 'N/A' }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="p-2 text-center">
                                        <input type="text" class="form-control current-qty font-weight-bold text-center bg-light border-0 text-dark" style="height: 38px; font-size: 13.5px;" readonly placeholder="0">
                                    </td>
                                    <td class="p-2 physical-count-col">
                                        <div class="single-count-box">
                                            <input type="number" step="any" min="0" class="form-control text-center fw-bold single-physical-count-input" style="height: 38px; font-size: 14px; border: 1.5px solid #cbd5e1; border-radius: 8px;" placeholder="0">
                                        </div>
                                        <div class="multi-count-box" style="display: none;">
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <div style="flex: 1; position: relative;">
                                                    <input type="number" min="0" step="1" class="form-control text-center fw-bold pack-physical-input" style="height: 38px; font-size: 14px; font-weight: 700; border: 1.5px solid #cbd5e1; border-radius: 8px; padding-right: 44px;" placeholder="0">
                                                    <span class="position-absolute end-0 top-50 translate-middle-y me-2 text-muted fw-semibold small pack-unit-badge" style="pointer-events: none; font-size: 11px;">Packs</span>
                                                </div>
                                                <span class="fw-bold text-muted px-1">+</span>
                                                <div style="flex: 1; position: relative;">
                                                    <input type="number" min="0" step="1" class="form-control text-center fw-bold piece-physical-input" style="height: 38px; font-size: 14px; font-weight: 700; border: 1.5px solid #cbd5e1; border-radius: 8px; padding-right: 44px;" placeholder="0">
                                                    <span class="position-absolute end-0 top-50 translate-middle-y me-2 text-muted fw-semibold small piece-unit-badge" style="pointer-events: none; font-size: 11px;">Pcs</span>
                                                </div>
                                            </div>
                                            <div class="text-center">
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-semibold physical-count-preview" style="font-size: 11px;">(= 0 pcs)</span>
                                            </div>
                                        </div>
                                        <input type="hidden" name="physical_count[]" class="physical-count-input" required>
                                    </td>
                                    <td class="p-2 text-center">
                                        <input type="number" step="0.01" min="0.01" name="qty[]" class="form-control qty-input font-weight-bold text-center bg-light border-0" style="height: 38px; font-size: 14px; color: #0f172a;" required readonly placeholder="0">
                                    </td>
                                    <td class="p-2">
                                        <div class="d-flex align-items-center">
                                            <input type="number" step="0.01" min="0" name="unit_cost[]" class="form-control unit-cost-input text-end fw-bold" style="height: 38px; font-size: 14px; border: 1.5px solid #cbd5e1; border-radius: 8px;" required placeholder="0.00">
                                        </div>
                                    </td>
                                    <td class="p-2">
                                        <div class="d-flex align-items-center">
                                            <input type="number" step="0.01" min="0" name="amount[]" class="form-control amount-input text-end fw-bold bg-light border-0" style="height: 38px; font-size: 14px; color: #0f172a;" required readonly placeholder="0.00">
                                        </div>
                                    </td>
                                    <td class="p-2">
                                        <input type="text" name="description[]" class="form-control form-control-sm" style="height: 38px; font-size: 13px; border: 1.5px solid #cbd5e1; border-radius: 8px;" placeholder="Reason/note...">
                                        <div class="mt-1"><span class="badge bg-light text-secondary border computed-hint d-none" style="font-size: 11px;">Computed...</span></div>
                                    </td>
                                    <td class="p-2 text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-line" title="Remove line"><i class="fas fa-trash-alt"></i></button>
                                    </td>
                                </tr>
                            </template>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function () {
    function formatPackaging(baseQty, piecesPerPack, packName, subUnitName) {
        var qty = Math.abs(parseFloat(baseQty)) || 0;
        if (!piecesPerPack || piecesPerPack <= 1) {
            return (qty % 1 === 0 ? qty : qty.toFixed(2)) + ' ' + (packName || 'units');
        }
        var packs = Math.floor(qty / piecesPerPack);
        var loose = Math.round(qty % piecesPerPack);

        var formatWord = function(count, name) {
            var cleaned = (name || '').trim();
            var lower = cleaned.toLowerCase();
            var singular = cleaned;
            if (lower.endsWith('boxes')) singular = 'box';
            else if (lower.endsWith('pieces')) singular = 'piece';
            else if (lower.endsWith('bundles')) singular = 'bundle';
            else if (lower.endsWith('liters') || lower.endsWith('litres')) singular = 'liter';
            else if (lower.endsWith('kilograms')) singular = 'kg';
            else if (lower.endsWith('meters') || lower.endsWith('metres')) singular = 'meter';
            else if (lower.endsWith('bags')) singular = 'bag';
            else if (lower.endsWith('packs')) singular = 'pack';
            else if (lower.endsWith('cartons')) singular = 'carton';
            else if (lower.endsWith('rolls')) singular = 'roll';
            else if (lower.endsWith('bottles')) singular = 'bottle';
            else if (lower.endsWith('s') && lower.length > 3) singular = cleaned.slice(0, -1);

            var singLower = singular.toLowerCase();
            var plural = singLower + 's';
            if (singLower === 'box') plural = 'boxes';
            else if (singLower === 'piece') plural = 'pieces';
            else if (singLower === 'bag') plural = 'bags';
            else if (singLower === 'pack') plural = 'packs';
            else if (singLower === 'carton') plural = 'cartons';
            else if (singLower === 'roll') plural = 'rolls';
            else if (singLower === 'bottle') plural = 'bottles';
            else if (singLower.endsWith('ch') || singLower.endsWith('sh') || singLower.endsWith('x')) plural = singLower + 'es';

            return count === 1 ? '1 ' + singLower : count + ' ' + plural;
        };

        if (packs > 0 && loose > 0) {
            return formatWord(packs, packName) + ', ' + formatWord(loose, subUnitName);
        } else if (packs > 0) {
            return formatWord(packs, packName);
        } else if (loose > 0) {
            return formatWord(loose, subUnitName);
        } else {
            return '0 ' + formatWord(0, subUnitName).split(' ').slice(1).join(' ');
        }
    }

    function updateTotals() {
        let totalQty = 0;
        let totalValue = 0;

        $('.line-row').each(function () {
            const qty = parseFloat($(this).find('.qty-input').val()) || 0;
            const value = parseFloat($(this).find('.amount-input').val()) || 0;
            totalQty += qty;
            totalValue += value;
        });

        $('#total-qty').text(totalQty.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        $('#total-value').text(totalValue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    }

    function bindRemoveButtons() {
        const rowCount = $('#adjustment-lines-table tbody tr').length;
        $('.remove-line').prop('disabled', rowCount <= 1);
    }

    function recalcRow($row) {
        const qty = parseFloat($row.find('.qty-input').val()) || 0;
        const unitCost = parseFloat($row.find('.unit-cost-input').val()) || 0;
        $row.find('.amount-input').val((qty * unitCost).toFixed(2));
        updateTotals();
    }

    function recalcFromPhysicalCount($row) {
        const currentQty = parseFloat($row.attr('data-raw-current-qty')) || 0;
        const physicalCount = parseFloat($row.find('.physical-count-input').val());
        const $hint = $row.find('.computed-hint');
        const $selected = $row.find('.product-select option:selected');
        const hasSub = parseInt($selected.data('has-sub')) === 1;
        const piecesPerPack = parseInt($selected.data('pieces-per-pack')) || 1;
        const unitName = $selected.data('unit-name') || 'units';
        const subUnitName = $selected.data('sub-unit-name') || 'pcs';

        if (isNaN(physicalCount)) {
            $row.find('.qty-input').val('');
            $hint.addClass('d-none').text('Computed from physical count.');
            recalcRow($row);
            return;
        }

        const diff = Math.abs(physicalCount - currentQty);
        $row.find('.qty-input').val(diff.toFixed(2));

        if (diff === 0) {
            $hint.removeClass('d-none').text('No change: physical count matches current stock.');
        } else {
            var diffDisplay = (hasSub && piecesPerPack > 1) 
                ? formatPackaging(diff, piecesPerPack, unitName, subUnitName) + ' (' + diff + ' ' + subUnitName + ')'
                : diff.toFixed(2) + ' ' + unitName;

            if (physicalCount > currentQty) {
                $hint.removeClass('d-none').text('Computed: Increase by ' + diffDisplay);
            } else {
                $hint.removeClass('d-none').text('Computed: Decrease by ' + diffDisplay);
            }
        }

        recalcRow($row);
    }

    function getDirectionState() {
        let direction = null;
        let hasValue = false;

        $('.line-row').each(function () {
            const currentQty = parseFloat($(this).attr('data-raw-current-qty'));
            const physicalCount = parseFloat($(this).find('.physical-count-input').val());

            if (isNaN(currentQty) || isNaN(physicalCount)) {
                return;
            }

            if (physicalCount === currentQty) {
                return;
            }

            hasValue = true;
            const lineDirection = physicalCount > currentQty ? 'increase' : 'decrease';

            if (direction === null) {
                direction = lineDirection;
            } else if (direction !== lineDirection) {
                direction = 'mixed';
            }
        });

        if (!hasValue) {
            return 'none';
        }

        return direction;
    }

    function setComputedAdjustmentType() {
        const direction = getDirectionState();

        if (direction === 'none') {
            $('#adjustment_type').val('');
            $('#adjustment_type_label').val('Auto (from physical count)');
            return;
        }

        if (direction === 'increase' || direction === 'decrease') {
            $('#adjustment_type').val(direction);
            $('#adjustment_type_label').val(direction.charAt(0).toUpperCase() + direction.slice(1));
        } else {
            $('#adjustment_type').val('mixed');
            $('#adjustment_type_label').val('Mixed');
        }
    }

    function getCurrentOutletId() {
        return $('#outlet_id').val();
    }

    function getProductQtyForOutlet(option, outletId) {
        const stocks = $(option).data('stocks') || {};
        if (stocks[outletId] !== undefined) {
            return parseFloat(stocks[outletId]) || 0;
        }
        return parseFloat($(option).data('qty')) || 0;
    }

    $(document).on('change', '.product-select', function () {
        const $selected = $(this).find('option:selected');
        const outletId = getCurrentOutletId();
        const qty = $selected.val() ? getProductQtyForOutlet($selected, outletId) : 0;
        const hasSub = parseInt($selected.data('has-sub')) === 1;
        const piecesPerPack = parseInt($selected.data('pieces-per-pack')) || 1;
        const unitName = $selected.data('unit-name') || 'units';
        const subUnitName = $selected.data('sub-unit-name') || 'pcs';
        const cost = hasSub ? ($selected.data('sub-cost') ?? 0) : ($selected.data('cost') ?? 0);

        const $row = $(this).closest('tr');
        $row.attr('data-raw-current-qty', qty);

        if ($selected.val()) {
            if (hasSub && piecesPerPack > 1) {
                var formatted = formatPackaging(qty, piecesPerPack, unitName, subUnitName);
                $row.find('.current-qty').val(formatted + ' (' + (qty % 1 === 0 ? qty : qty.toFixed(2)) + ' ' + subUnitName + ')');
                $row.find('.single-count-box').hide();
                $row.find('.multi-count-box').show();
                $row.find('.pack-unit-badge').text(unitName);
                $row.find('.piece-unit-badge').text(subUnitName);
                $row.find('.pack-physical-input').val('');
                $row.find('.piece-physical-input').val('');
                $row.find('.physical-count-preview').text('(= 0 ' + subUnitName + ')');
                $row.find('.physical-count-input').val('');
            } else {
                $row.find('.current-qty').val(formatPackaging(qty, 1, unitName, subUnitName));
                $row.find('.single-count-box').show();
                $row.find('.multi-count-box').hide();
                $row.find('.single-physical-count-input').val('');
                $row.find('.physical-count-input').val('');
            }
            $row.find('.unit-cost-input').val(cost);
        } else {
            $row.find('.current-qty').val('');
            $row.find('.single-count-box').show();
            $row.find('.multi-count-box').hide();
            $row.find('.unit-cost-input').val('');
            $row.find('.physical-count-input').val('');
        }

        recalcFromPhysicalCount($row);
        setComputedAdjustmentType();
    });

    $(document).on('input change', '.pack-physical-input, .piece-physical-input', function () {
        const $row = $(this).closest('tr');
        const $selected = $row.find('.product-select option:selected');
        const piecesPerPack = parseInt($selected.data('pieces-per-pack')) || 1;
        const subUnitName = $selected.data('sub-unit-name') || 'pcs';
        const packs = parseFloat($row.find('.pack-physical-input').val()) || 0;
        const pieces = parseFloat($row.find('.piece-physical-input').val()) || 0;
        const total = Math.round((packs * piecesPerPack) + pieces);

        $row.find('.physical-count-input').val(total);
        $row.find('.physical-count-preview').text('(= ' + total + ' ' + subUnitName + ')');
        recalcFromPhysicalCount($row);
        setComputedAdjustmentType();
    });

    $(document).on('input change', '.single-physical-count-input', function () {
        const $row = $(this).closest('tr');
        $row.find('.physical-count-input').val($(this).val());
        recalcFromPhysicalCount($row);
        setComputedAdjustmentType();
    });

    $(document).on('input', '.qty-input, .unit-cost-input', function () {
        if ($(this).hasClass('qty-input')) {
            return;
        }
        recalcRow($(this).closest('tr'));
    });

    function initProductSelect($context) {
        if ($.fn.select2) {
            $context.find('.product-select').each(function () {
                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2('destroy');
                }
                $(this).select2({
                    width: '100%',
                    placeholder: '-- Select Product --'
                });
            });
        }
    }

    $('#add-line').on('click', function () {
        const templateHtml = $('#adjustment-row-template').html();
        const $newRow = $(templateHtml);
        $('#adjustment-lines-table tbody').append($newRow);
        initProductSelect($newRow);
        bindRemoveButtons();
        updateTotals();
    });

    $(document).on('click', '.remove-line', function () {
        if ($('#adjustment-lines-table tbody tr').length > 1) {
            const $row = $(this).closest('tr');
            if ($.fn.select2) {
                $row.find('.product-select').select2('destroy');
            }
            $row.remove();
            bindRemoveButtons();
            updateTotals();
            setComputedAdjustmentType();
        }
    });

    $('#myForm').on('submit', function (e) {
        e.preventDefault();

        if (!$('#adjustment_date').val()) {
            Notiflix.Notify.failure('Please select adjustment date');
            return;
        }

        let missingPhysical = false;
        $('.line-row').each(function () {
            const product = $(this).find('.product-select').val();
            const physical = $(this).find('.physical-count-input').val();
            if (product && (physical === '' || physical === null)) {
                missingPhysical = true;
                return false;
            }
        });

        if (missingPhysical) {
            Notiflix.Notify.failure('Please enter physical count for all selected products');
            return;
        }

        const directionState = getDirectionState();
        if (directionState === 'none') {
            Notiflix.Notify.failure('No stock difference detected. Please review physical counts.');
            return;
        }

        $('#adjustment_type').val(directionState === 'mixed' ? 'mixed' : directionState);

        var form = $(this);
        var url = form.attr('action');

        $.ajax({
            type: "POST",
            url: url,
            data: form.serialize(),
            success: function (response) {
                Notiflix.Notify.success('Stock adjustment was successfully updated.');
                setTimeout(function () {
                    window.location.href = "{{ route('stock-adjustments.index') }}";
                }, 1000);
            },
            error: function (xhr) {
                $('#errors').empty();
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    $('#errorAlert').show();
                    $.each(xhr.responseJSON.errors, function (key, value) {
                        $('#errors').append('<li>' + value[0] + '</li>');
                    });
                } else {
                    var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Failed to update stock adjustment';
                    Notiflix.Notify.failure(msg);
                }
            }
        });
    });

    // Initialize existing lines calculation
    $('.line-row').each(function () {
        recalcFromPhysicalCount($(this));
    });
    initProductSelect($('#adjustment-lines-table tbody'));
    bindRemoveButtons();
    updateTotals();
});
</script>
@endsection
