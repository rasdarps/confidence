@extends('layout.main')
@section('title') {{'Create Stock Transfer'}} @endsection

@section('content')
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
</style>
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0" style="border-radius: 14px; overflow: hidden;">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <span class="card-title mb-0" style="font-size:20px; font-weight: 700; color: #0f172a;">
                            <i class="fas fa-truck-moving text-primary me-2 mr-2"></i>Dispatch Stock Transfer
                        </span>
                        <a href="{{ route('stock-transfers.index') }}" class="btn btn-modern-header">
                            <i class="fas fa-list me-1 mr-1"></i> View All Transfers
                        </a>
                    </div>

                    <div class="card-body p-4">
                        <form action="{{ route('stock-transfers.store') }}" method="POST" id="transferForm">
                            @csrf

                            <div class="alert alert-danger alert-dismissible fade show" id="errorAlert" style="display: none; border-radius: 10px;">
                                <button type="button" class="close h-100" data-dismiss="alert" aria-label="Close">
                                    <span><i class="mdi mdi-close"></i></span>
                                </button>
                                <ul id="errors" class="mb-0"></ul>
                            </div>

                            <!-- Route & Header Card -->
                            <div class="p-3 mb-4 rounded-3 border bg-light-subtle shadow-xs">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label text-muted small fw-bold text-uppercase">Transfer #</label>
                                        <input type="text" class="form-control fw-bold bg-white" value="{{ $transferNo }}" readonly style="height: 42px; border: 1.5px solid #cbd5e1; border-radius: 8px;">
                                    </div>

                                    <div class="col-md-3">
                                        <label for="transfer_date" class="form-label text-muted small fw-bold text-uppercase">Date <span class="text-danger">*</span></label>
                                        <input type="date" id="transfer_date" name="transfer_date" class="form-control fw-semibold" value="{{ $date }}" required style="height: 42px; border: 1.5px solid #cbd5e1; border-radius: 8px;">
                                    </div>

                                    <div class="col-md-3">
                                        <label for="from_outlet_id" class="form-label text-muted small fw-bold text-uppercase">Source Warehouse <span class="text-danger">*</span></label>
                                        <select name="from_outlet_id" id="from_outlet_id" class="form-control select2" required style="height: 42px;">
                                            @foreach($fromOutlets as $outlet)
                                                <option value="{{ $outlet->id }}" {{ $outlet->id == $defaultFromOutletId ? 'selected' : '' }}>
                                                    {{ $outlet->name }} {{ $outlet->is_central ? '(Central Warehouse)' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label for="to_outlet_id" class="form-label text-muted small fw-bold text-uppercase">Destination Outlet <span class="text-danger">*</span></label>
                                        <select name="to_outlet_id" id="to_outlet_id" class="form-control select2" required style="height: 42px;">
                                            <option value="" disabled selected>-- Select Destination --</option>
                                            @foreach($toOutlets as $outlet)
                                                @if($outlet->id != $defaultFromOutletId)
                                                    <option value="{{ $outlet->id }}">{{ $outlet->name }} ({{ $outlet->code }})</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Modern Product Item Dispatcher Card -->
                            <div class="card border rounded-3 mb-4 shadow-sm" style="border-color: #cbd5e1; background: #ffffff;">
                                <div class="card-header bg-light py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-dark" style="font-size: 13.5px;">
                                        <i class="fas fa-boxes text-primary me-2 mr-1"></i>Select Products to Transfer
                                    </span>
                                    <span class="text-muted small">Choose packaging boxes and loose pieces</span>
                                </div>
                                <div class="card-body p-3">
                                    <div class="row g-3 align-items-end">
                                        <div class="col-lg-4 col-md-12">
                                            <label for="product_select" class="form-label fw-bold text-muted small text-uppercase">Product</label>
                                            <select id="product_select" class="form-control select2">
                                                <option value="" disabled selected>-- Search & Select Product --</option>
                                                @foreach($products as $product)
                                                    @php
                                                        $outletStocksMap = $product->outletStocks->pluck("quantity", "outlet_id");
                                                    @endphp
                                                    <option value="{{ $product->id }}"
                                                        data-name="{{ $product->name }}"
                                                        data-unit="{{ $product->unit->name ?? 'pcs' }}"
                                                        data-sub-unit="{{ $product->subUnit->name ?? 'pcs' }}"
                                                        data-has-sub="{{ $product->has_sub_unit ? 1 : 0 }}"
                                                        data-pieces-per-pack="{{ $product->pieces_per_pack ?: 1 }}"
                                                        data-barcode="{{ $product->barcode }}"
                                                        data-stocks='@json($outletStocksMap)'>
                                                        {{ $product->name }} ({{ $product->unit->name ?? 'pcs' }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-lg-3 col-md-4">
                                            <label class="form-label fw-bold text-muted small text-uppercase">Stock at Source</label>
                                            <div class="p-2 px-3 rounded-2 bg-light border text-center font-weight-bold d-flex align-items-center justify-content-center" style="height: 42px; border-color: #cbd5e1 !important;" id="available_stock_display_box">
                                                <span id="available_stock_display_text" class="text-truncate fw-bold text-dark" style="font-size: 13.5px;">Select product</span>
                                            </div>
                                            <input type="hidden" id="available_stock_raw" value="0">
                                        </div>

                                        <div class="col-lg-5 col-md-8">
                                            <div id="single_unit_container">
                                                <label for="transfer_qty_input" class="form-label fw-bold text-muted small text-uppercase" id="single_qty_label">Transfer Quantity</label>
                                                <input type="number" id="transfer_qty_input" class="form-control form-control-lg text-center fw-bold" style="height: 42px; font-size: 15px; border: 1.5px solid #cbd5e1; border-radius: 8px;" min="0.01" step="any" placeholder="0">
                                            </div>

                                            <div id="multi_unit_container" style="display: none;">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <label class="form-label fw-bold text-muted small text-uppercase mb-0">Transfer Quantity</label>
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold" id="multi_pieces_badge" style="font-size: 11.5px; padding: 4px 8px;">= 0 pieces</span>
                                                </div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div style="flex: 1; position: relative;">
                                                        <input type="number" id="transfer_pack_input" class="form-control text-center fw-bold" style="height: 42px; font-size: 15px; font-weight: 700; border: 1.5px solid #cbd5e1; border-radius: 8px; padding-right: 48px;" min="0" step="1" placeholder="0">
                                                        <span class="position-absolute end-0 top-50 translate-middle-y me-2 text-muted fw-semibold small" id="pack_label" style="pointer-events: none; font-size: 11px;">Pack</span>
                                                    </div>
                                                    <span class="fw-bold text-muted fs-5 px-1">+</span>
                                                    <div style="flex: 1; position: relative;">
                                                        <input type="number" id="transfer_piece_input" class="form-control text-center fw-bold" style="height: 42px; font-size: 15px; font-weight: 700; border: 1.5px solid #cbd5e1; border-radius: 8px; padding-right: 48px;" min="0" step="1" placeholder="0">
                                                        <span class="position-absolute end-0 top-50 translate-middle-y me-2 text-muted fw-semibold small" id="piece_label" style="pointer-events: none; font-size: 11px;">Pcs</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Action Button Row Pushed Down -->
                                    <div class="row mt-3 pt-3 border-top">
                                        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                            <span class="text-muted small">
                                                <i class="fas fa-info-circle text-primary me-1 mr-1"></i> Specify dispatch quantity in bags/boxes and loose pieces, then click <strong>Add Item</strong> to add to manifest.
                                            </span>
                                            <button type="button" class="btn btn-modern-primary add-item-trigger px-4" style="height: 42px; font-weight: 700;">
                                                <i class="fas fa-plus-circle me-1 mr-1"></i> Add Item
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Transfer Manifest Items Table -->
                            <div class="table-responsive mb-4 shadow-xs" style="border: 1px solid #cbd5e1; border-radius: 10px; background: #fff;">
                                <table class="table table-bordered table-striped mb-0" id="transferItemsTable" style="vertical-align: middle;">
                                    <thead class="table-dark" style="background-color: #0f172a;">
                                        <tr>
                                            <th style="width: 5%; text-align: center;">#</th>
                                            <th style="width: 40%;" class="ps-3 pl-3">Product Name</th>
                                            <th style="width: 20%;">Packaging Unit</th>
                                            <th class="text-center" style="width: 25%; min-width: 180px;">Quantity to Dispatch</th>
                                            <th class="text-center" style="width: 10%;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="transferItemsBody">
                                        <tr id="emptyRow">
                                            <td colspan="5" class="text-center py-4 text-muted">
                                                <i class="fas fa-inbox fs-3 d-block mb-2 text-secondary"></i>
                                                No items added yet. Select a product and click <strong>"Add Item"</strong> above.
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-light">
                                            <th colspan="3" class="text-end text-right fw-bold align-middle fs-6">Total Items / Total Units:</th>
                                            <th class="text-center fw-bold align-middle fs-6 text-primary" id="totalQtyDisplay">0 units</th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="notes" class="form-label fw-bold text-muted small text-uppercase">Transfer Notes / Dispatch Memo</label>
                                    <textarea id="notes" name="notes" class="form-control" rows="2" placeholder="e.g. Dispatched with delivery van #3, driver Kwame" style="border: 1.5px solid #cbd5e1; border-radius: 8px;"></textarea>
                                </div>
                            </div>

                            <div class="row mt-4 pt-3 border-top">
                                <div class="col-12 text-center">
                                    <a href="{{ route('stock-transfers.index') }}" class="btn btn-modern-cancel mr-2 me-2">
                                        <i class="fas fa-times me-1 mr-1"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-modern-primary px-4">
                                        <i class="fas fa-truck me-1 mr-1"></i> Dispatch Transfer
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script type="text/javascript">
    $(document).ready(function () {
        var itemsMap = {};

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

        function getSourceOutletId() {
            return $('#from_outlet_id').val();
        }

        function updateAvailableDisplay() {
            var selectedOption = $('#product_select').find(':selected');
            if (!selectedOption.val()) {
                $('#available_stock_display_text').text('Select product');
                $('#available_stock_raw').val('0');
                $('#single_unit_container').show();
                $('#multi_unit_container').hide();
                return;
            }
            var stocks = selectedOption.data('stocks') || {};
            var sourceId = getSourceOutletId();
            var avail = stocks[sourceId] ? parseFloat(stocks[sourceId]) : 0;
            var hasSub = parseInt(selectedOption.data('has-sub')) === 1;
            var piecesPerPack = parseInt(selectedOption.data('pieces-per-pack')) || 1;
            var unit = selectedOption.data('unit') || 'units';
            var subUnit = selectedOption.data('sub-unit') || 'pcs';

            $('#available_stock_raw').val(avail);

            if (hasSub && piecesPerPack > 1) {
                var formatted = formatPackaging(avail, piecesPerPack, unit, subUnit);
                $('#available_stock_display_text').html(formatted + ' <small class="text-muted">(' + (avail % 1 === 0 ? avail : avail.toFixed(2)) + ' ' + subUnit + ')</small>');
                $('#single_unit_container').hide();
                $('#multi_unit_container').show();
                $('#pack_label').text(unit);
                $('#piece_label').text(subUnit);
                $('#transfer_pack_input').val('');
                $('#transfer_piece_input').val('');
                $('#multi_pieces_badge').text('(= 0 ' + subUnit + ')');
            } else {
                $('#available_stock_display_text').text(formatPackaging(avail, 1, unit, subUnit));
                $('#single_unit_container').show();
                $('#multi_unit_container').hide();
                $('#single_qty_label').text('Transfer Qty (' + unit + ')');
                $('#transfer_qty_input').val('');
            }
        }

        function updateMultiPreview() {
            var selectedOption = $('#product_select').find(':selected');
            var piecesPerPack = parseInt(selectedOption.data('pieces-per-pack')) || 1;
            var subUnit = selectedOption.data('sub-unit') || 'pcs';
            var packs = parseFloat($('#transfer_pack_input').val()) || 0;
            var pieces = parseFloat($('#transfer_piece_input').val()) || 0;
            var total = Math.round((packs * piecesPerPack) + pieces);
            $('#multi_pieces_badge').text('(= ' + total + ' ' + subUnit + ')');
        }

        $('#transfer_pack_input, #transfer_piece_input').on('input change', updateMultiPreview);
        $('#product_select').on('change', updateAvailableDisplay);
        $('#from_outlet_id').on('change', function () {
            var sourceId = $(this).val();
            $('#to_outlet_id option').each(function () {
                if ($(this).val() == sourceId) {
                    $(this).prop('disabled', true);
                } else {
                    $(this).prop('disabled', false);
                }
            });
            if ($('#to_outlet_id').val() == sourceId) {
                $('#to_outlet_id').val('');
            }
            updateAvailableDisplay();
        });

        // Add Item
        $('.add-item-trigger').on('click', function () {
            var productOption = $('#product_select').find(':selected');
            var productId = productOption.val();
            if (!productId) {
                if (typeof Notiflix !== 'undefined') Notiflix.Notify.failure('Please select a product.');
                return;
            }

            var productName = productOption.data('name');
            var unit = productOption.data('unit') || 'units';
            var subUnit = productOption.data('sub-unit') || 'pcs';
            var hasSub = parseInt(productOption.data('has-sub')) === 1;
            var piecesPerPack = parseInt(productOption.data('pieces-per-pack')) || 1;
            var available = parseFloat($('#available_stock_raw').val()) || 0;

            var totalQty = 0;
            if (hasSub && piecesPerPack > 1) {
                var packQty = parseFloat($('#transfer_pack_input').val()) || 0;
                var pieceQty = parseFloat($('#transfer_piece_input').val()) || 0;
                totalQty = Math.round((packQty * piecesPerPack) + pieceQty);
            } else {
                totalQty = parseFloat($('#transfer_qty_input').val()) || 0;
            }

            if (totalQty <= 0) {
                if (typeof Notiflix !== 'undefined') Notiflix.Notify.failure('Please enter a transfer quantity greater than 0.');
                return;
            }

            if (totalQty > available) {
                var msg = 'Quantity exceeds available stock at source warehouse (' + available + ' total ' + (hasSub ? subUnit : unit) + ').';
                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Notify.failure(msg);
                } else {
                    alert(msg);
                }
                return;
            }

            if (itemsMap[productId]) {
                itemsMap[productId].qty += totalQty;
            } else {
                itemsMap[productId] = {
                    productId: productId,
                    name: productName,
                    unit: unit,
                    subUnit: subUnit,
                    hasSub: hasSub,
                    piecesPerPack: piecesPerPack,
                    qty: totalQty
                };
            }

            $('#transfer_qty_input, #transfer_pack_input, #transfer_piece_input').val('');
            $('#product_select').val('').trigger('change');
            renderTable();
        });

        function renderTable() {
            var tbody = $('#transferItemsBody');
            tbody.empty();

            var keys = Object.keys(itemsMap);
            if (keys.length === 0) {
                tbody.html('<tr id="emptyRow"><td colspan="5" class="text-center py-4 text-muted"><i class="fas fa-inbox fs-3 d-block mb-2 text-secondary"></i>No items added yet. Select a product and click <strong>"Add Item"</strong> above.</td></tr>');
                $('#totalQtyDisplay').text('0 units');
                return;
            }

            var totalUnits = 0;
            var index = 1;
            keys.forEach(function (k) {
                var item = itemsMap[k];
                totalUnits += item.qty;

                var qtyDisplay = '';
                if (item.hasSub && item.piecesPerPack > 1) {
                    var formatted = formatPackaging(item.qty, item.piecesPerPack, item.unit, item.subUnit);
                    qtyDisplay = '<strong class="text-primary fs-6">' + formatted + '</strong>' +
                                 '<br><small class="text-muted fw-bold">(' + item.qty + ' total ' + item.subUnit + ')</small>';
                } else {
                    qtyDisplay = '<strong class="text-primary fs-6">' + (item.qty % 1 === 0 ? item.qty : item.qty.toFixed(2)) + ' ' + item.unit + '</strong>';
                }

                var row = '<tr>' +
                    '<td class="text-center align-middle">' + index + '</td>' +
                    '<td class="align-middle ps-3 pl-3"><strong>' + item.name + '</strong><input type="hidden" name="product_id[]" value="' + item.productId + '"></td>' +
                    '<td class="align-middle">' + (item.hasSub ? '<span class="badge bg-secondary-subtle text-secondary border">' + item.unit + ' (' + item.piecesPerPack + ' ' + item.subUnit + ')</span>' : '<span class="badge bg-light text-dark border">' + item.unit + '</span>') + '</td>' +
                    '<td class="text-center align-middle py-2">' +
                        qtyDisplay +
                        '<input type="hidden" name="quantity[]" value="' + item.qty + '">' +
                    '</td>' +
                    '<td class="text-center align-middle"><button type="button" class="btn btn-sm btn-outline-danger btn-remove" data-id="' + item.productId + '"><i class="fas fa-trash-alt"></i></button></td>' +
                    '</tr>';
                tbody.append(row);
                index++;
            });

            $('#totalQtyDisplay').text(totalUnits + ' units');
        }

        $(document).on('click', '.btn-remove', function () {
            var pId = $(this).data('id');
            delete itemsMap[pId];
            renderTable();
        });

        // Submit form
        $('#transferForm').on('submit', function (e) {
            e.preventDefault();

            if (Object.keys(itemsMap).length === 0) {
                if (typeof Notiflix !== 'undefined') Notiflix.Notify.failure('Please add at least one product to transfer.');
                return;
            }

            if (!$('#to_outlet_id').val()) {
                if (typeof Notiflix !== 'undefined') Notiflix.Notify.failure('Please select a destination outlet.');
                return;
            }

            if (typeof Notiflix !== 'undefined') {
                Notiflix.Loading.standard('Dispatching transfer, please wait...');
            }

            $.ajax({
                url: $(this).attr('action'),
                type: $(this).attr('method'),
                data: $(this).serialize(),
                success: function (data) {
                    if (typeof Notiflix !== 'undefined') Notiflix.Loading.remove();
                    if (data.status === 200) {
                        if (typeof Notiflix !== 'undefined') Notiflix.Notify.success(data.message);
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        }
                    } else {
                        if (typeof Notiflix !== 'undefined') Notiflix.Notify.failure(data.message);
                    }
                },
                error: function (xhr) {
                    if (typeof Notiflix !== 'undefined') Notiflix.Loading.remove();
                    $('#errors').empty();
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        $('#errorAlert').show();
                        $.each(xhr.responseJSON.errors, function (field, messages) {
                            $.each(messages, function (index, message) {
                                $('#errors').append('<li>' + message + '</li>');
                            });
                        });
                    } else {
                        var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error dispatching transfer';
                        if (typeof Notiflix !== 'undefined') Notiflix.Notify.failure(msg);
                    }
                }
            });
        });
    });
</script>
@endsection
