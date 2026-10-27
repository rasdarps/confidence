@extends('layout.main')
@section('title') {{'Create Stock Transfer'}} @endsection

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <span class="card-title" style="font-size:20px;">Dispatch Stock Transfer</span>
                        <a href="{{ route('stock-transfers.index') }}" style="float:right">
                            <button type="button" class="btn btn-primary">View All Transfers</button>
                        </a>
                        <hr>

                        <div class="card-body">
                            <form action="{{ route('stock-transfers.store') }}" method="POST" id="transferForm">
                                @csrf

                                <div class="alert alert-danger alert-dismissible fade show" id="errorAlert" style="display: none;">
                                    <button type="button" class="close h-100" data-dismiss="alert" aria-label="Close">
                                        <span><i class="mdi mdi-close"></i></span>
                                    </button>
                                    <ul id="errors"></ul>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <div class="form-group mb-2">
                                            <label><strong>Transfer #</strong></label>
                                            <input type="text" class="form-control" value="{{ $transferNo }}" readonly style="background-color: #eee;">
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group mb-2">
                                            <label for="transfer_date"><strong>Date <span class="text-danger">*</span></strong></label>
                                            <input type="date" id="transfer_date" name="transfer_date" class="form-control" value="{{ $date }}" required>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group mb-2">
                                            <label for="from_outlet_id"><strong>Source Warehouse <span class="text-danger">*</span></strong></label>
                                            <select name="from_outlet_id" id="from_outlet_id" class="form-control select2" required>
                                                @foreach($fromOutlets as $outlet)
                                                    <option value="{{ $outlet->id }}" {{ $outlet->id == $defaultFromOutletId ? 'selected' : '' }}>
                                                        {{ $outlet->name }} {{ $outlet->is_central ? '(Central Warehouse)' : '' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group mb-2">
                                            <label for="to_outlet_id"><strong>Destination Outlet <span class="text-danger">*</span></strong></label>
                                            <select name="to_outlet_id" id="to_outlet_id" class="form-control select2" required>
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

                                <hr>
                                <h5 class="mb-3">Select Products to Transfer</h5>

                                <div class="row align-items-end mb-3">
                                    <div class="col-md-5">
                                        <label for="product_select"><strong>Product</strong></label>
                                        <select id="product_select" class="form-control select2">
                                            <option value="" disabled selected>-- Search & Select Product --</option>
                                            @foreach($products as $product)
                                                <option value="{{ $product->id }}"
                                                    data-name="{{ $product->name }}"
                                                    data-unit="{{ $product->unit->name ?? 'pcs' }}"
                                                    data-barcode="{{ $product->barcode }}"
                                                    data-stocks='@json($product->outletStocks->pluck("quantity", "outlet_id"))'>
                                                    {{ $product->name }} ({{ $product->unit->name ?? 'pcs' }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-2">
                                        <label><strong>Available at Source</strong></label>
                                        <input type="text" id="available_stock_display" class="form-control" readonly style="background:#eee;" placeholder="0">
                                    </div>

                                    <div class="col-md-2">
                                        <label for="transfer_qty_input"><strong>Transfer Qty</strong></label>
                                        <input type="number" id="transfer_qty_input" class="form-control" min="0.01" step="any" placeholder="Qty">
                                    </div>

                                    <div class="col-md-3">
                                        <button type="button" id="addItemBtn" class="btn btn-primary w-100">
                                            <i class="fas fa-plus-circle"></i> Add to Transfer List
                                        </button>
                                    </div>
                                </div>

                                <div class="table-responsive mb-4">
                                    <table class="table table-bordered table-striped" id="transferItemsTable">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>#</th>
                                                <th>Product</th>
                                                <th>Unit</th>
                                                <th class="text-center" style="width: 150px;">Quantity to Dispatch</th>
                                                <th class="text-center" style="width: 100px;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="transferItemsBody">
                                            <tr id="emptyRow">
                                                <td colspan="5" class="text-center text-muted">No items added yet. Select a product and click "Add to Transfer List".</td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="3" class="text-end">Total Items / Total Units:</th>
                                                <th class="text-center" id="totalQtyDisplay">0</th>
                                                <th></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <label for="notes"><strong>Transfer Notes / Dispatch Memo</strong></label>
                                        <textarea id="notes" name="notes" class="form-control" rows="2" placeholder="e.g. Dispatched with delivery van #3, driver Kwame"></textarea>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12 text-center">
                                        <button type="submit" class="btn btn-success btn-lg px-5">
                                            <i class="fas fa-truck"></i> Dispatch Transfer
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
</div>
@endsection

@section('scripts')
<script type="text/javascript">
    $(document).ready(function () {
        var itemsMap = {};

        function getSourceOutletId() {
            return $('#from_outlet_id').val();
        }

        function updateAvailableDisplay() {
            var selectedOption = $('#product_select').find(':selected');
            if (!selectedOption.val()) {
                $('#available_stock_display').val('0');
                return;
            }
            var stocks = selectedOption.data('stocks') || {};
            var sourceId = getSourceOutletId();
            var avail = stocks[sourceId] ? parseFloat(stocks[sourceId]) : 0;
            $('#available_stock_display').val(avail);
        }

        $('#product_select').on('change', updateAvailableDisplay);
        $('#from_outlet_id').on('change', function () {
            // Update destination options so destination cannot be source
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
        $('#addItemBtn').on('click', function () {
            var productOption = $('#product_select').find(':selected');
            var productId = productOption.val();
            if (!productId) {
                if (typeof Notiflix !== 'undefined') Notiflix.Notify.failure('Please select a product.');
                return;
            }

            var productName = productOption.data('name');
            var unit = productOption.data('unit');
            var available = parseFloat($('#available_stock_display').val()) || 0;
            var qty = parseFloat($('#transfer_qty_input').val()) || 0;

            if (qty <= 0) {
                if (typeof Notiflix !== 'undefined') Notiflix.Notify.failure('Please enter a transfer quantity greater than 0.');
                return;
            }

            if (qty > available) {
                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Notify.failure('Quantity exceeds available stock at source warehouse (' + available + ').');
                } else {
                    alert('Quantity exceeds available stock at source warehouse (' + available + ').');
                }
                return;
            }

            if (itemsMap[productId]) {
                itemsMap[productId].qty += qty;
            } else {
                itemsMap[productId] = {
                    productId: productId,
                    name: productName,
                    unit: unit,
                    qty: qty
                };
            }

            $('#transfer_qty_input').val('');
            $('#product_select').val('').trigger('change');
            renderTable();
        });

        function renderTable() {
            var tbody = $('#transferItemsBody');
            tbody.empty();

            var keys = Object.keys(itemsMap);
            if (keys.length === 0) {
                tbody.html('<tr id="emptyRow"><td colspan="5" class="text-center text-muted">No items added yet. Select a product and click "Add to Transfer List".</td></tr>');
                $('#totalQtyDisplay').text('0');
                return;
            }

            var totalQty = 0;
            var index = 1;
            keys.forEach(function (k) {
                var item = itemsMap[k];
                totalQty += item.qty;

                var row = '<tr>' +
                    '<td>' + index + '</td>' +
                    '<td>' + item.name + '<input type="hidden" name="product_id[]" value="' + item.productId + '"></td>' +
                    '<td>' + item.unit + '</td>' +
                    '<td class="text-center"><input type="number" step="any" min="0.01" class="form-control text-center item-qty" data-id="' + item.productId + '" name="quantity[]" value="' + item.qty + '"></td>' +
                    '<td class="text-center"><button type="button" class="btn btn-sm btn-danger btn-remove" data-id="' + item.productId + '"><i class="fas fa-trash"></i></button></td>' +
                    '</tr>';
                tbody.append(row);
                index++;
            });

            $('#totalQtyDisplay').text(totalQty.toFixed(2));
        }

        $(document).on('change', '.item-qty', function () {
            var pId = $(this).data('id');
            var newQty = parseFloat($(this).val()) || 0;
            if (newQty <= 0) {
                delete itemsMap[pId];
            } else {
                itemsMap[pId].qty = newQty;
            }
            renderTable();
        });

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
