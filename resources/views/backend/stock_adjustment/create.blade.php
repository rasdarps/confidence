@extends('layout.main')
@section('title') {{ 'Create Stock Adjustment' }} @endsection

@section('content')
<script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>

<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header with-border">
                        <span class="card-title" style="font-size:20px;">Create | Stock Adjustment</span>

                        <a href="{{ route('stock-adjustments.index') }}" style="float:right">
                            <button type="button" class="btn btn-primary modal_btn">View Adjustments</button>
                        </a>

                        <hr>

                        <div class="card-body">
                            <form action="{{ route('stock-adjustments.store') }}" method="POST" id="myForm" novalidate>
                                @csrf
                                <input type="hidden" name="physical_count_mode" value="1">

                                <div class="alert alert-danger alert-dismissible fade show" id="errorAlert" style="display: none;">
                                    <button type="button" class="close h-100" data-dismiss="alert" aria-label="Close">
                                        <span><i class="mdi mdi-close"></i></span>
                                    </button>
                                    <ul id="errors"></ul>
                                </div>

                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="adjustment_date"><strong>Date <span class="text-danger">*</span></strong></label>
                                            <input type="date" id="adjustment_date" name="adjustment_date" class="form-control" value="{{ old('adjustment_date', now()->format('Y-m-d')) }}">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="reference_no"><strong>Reference No</strong></label>
                                            <input type="text" id="reference_no" name="reference_no" class="form-control" value="{{ old('reference_no') }}" placeholder="optional">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="adjustment_type"><strong>Type <span class="text-danger">*</span></strong></label>
                                            <input type="text" id="adjustment_type_label" class="form-control" value="Auto (from physical count)" readonly>
                                            <input type="hidden" id="adjustment_type" name="adjustment_type">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="reason"><strong>Reason</strong></label>
                                            <input type="text" id="reason" name="reason" class="form-control" placeholder="reason">
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <label for="notes"><strong>Notes</strong></label>
                                        <textarea id="notes" name="notes" class="form-control" rows="2"></textarea>
                                    </div>
                                </div>

                                <h5>Adjustment Lines</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="adjustment-lines-table">
                                        <thead>
                                            <tr>
                                                <th style="width: 20%;">Product</th>
                                                <th style="width: 12%;">Current Qty</th>
                                                <th style="width: 12%;" class="physical-count-col">Physical Count</th>
                                                <th style="width: 12%;">Qty</th>
                                                <th style="width: 12%;">Unit Cost</th>
                                                <th style="width: 12%;">Amount</th>
                                                <th>Description</th>
                                                <th style="width: 8%;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="line-row">
                                                <td>
                                                    <select name="product_id[]" class="form-control product-select" required>
                                                        <option value="">Select Product</option>
                                                        @foreach($products as $product)
                                                            <option value="{{ $product->id }}" data-qty="{{ $product->quantity }}" data-cost="{{ $product->cost_price ?? 0 }}">
                                                                {{ $product->name  ?? 'N/A' }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td><input type="text" class="form-control current-qty" readonly></td>
                                                <td class="physical-count-col"><input type="number" step="0.01" min="0" name="physical_count[]" class="form-control physical-count-input" required></td>
                                                <td><input type="number" step="0.01" min="0.01" name="qty[]" class="form-control qty-input" required readonly></td>
                                                <td><input type="number" step="0.01" min="0" name="unit_cost[]" class="form-control unit-cost-input" required></td>
                                                <td><input type="number" step="0.01" min="0" name="amount[]" class="form-control amount-input" required readonly></td>
                                                <td>
                                                    <input type="text" name="description[]" class="form-control" placeholder="line note">
                                                    <small class="text-muted computed-hint d-none">Computed from physical count.</small>
                                                </td>
                                                <td class="text-center"><button type="button" class="btn btn-danger btn-sm remove-line" disabled><i class="fas fa-trash"></i></button></td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="3" class="text-right">Totals</th>
                                                <th><span id="total-qty">0.00</span></th>
                                                <th></th>
                                                <th><span id="total-value">0.00</span></th>
                                                <th colspan="2"></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                                <div class="mb-3">
                                    <button type="button" id="add-line" class="btn btn-info btn-sm"><i class="fas fa-plus-circle"></i> Add Line</button>
                                </div>

                                <div class="row">
                                    <div class="col-12 text-center">
                                        <button type="submit" class="btn btn-success mt-2">Submit Adjustment</button>
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
<script>
$(function () {
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
        const currentQty = parseFloat($row.find('.current-qty').val()) || 0;
        const physicalCount = parseFloat($row.find('.physical-count-input').val());
        const $hint = $row.find('.computed-hint');

        if (isNaN(physicalCount)) {
            $row.find('.qty-input').val('');
            $hint.addClass('d-none').text('Computed from physical count.');
            recalcRow($row);
            return;
        }

        const diff = Math.abs(physicalCount - currentQty);
        $row.find('.qty-input').val(diff.toFixed(2));

        if (diff === 0) {
            $hint.removeClass('d-none').text('No change: physical count matches current qty.');
        } else if (physicalCount > currentQty) {
            $hint.removeClass('d-none').text('Computed: Increase by ' + diff.toFixed(2));
        } else {
            $hint.removeClass('d-none').text('Computed: Decrease by ' + diff.toFixed(2));
        }

        recalcRow($row);
    }

    function getDirectionState() {
        let direction = null;
        let hasValue = false;

        $('.line-row').each(function () {
            const currentQty = parseFloat($(this).find('.current-qty').val());
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

    $(document).on('change', '.product-select', function () {
        const $selected = $(this).find('option:selected');
        const qty = $selected.data('qty') ?? '';
        const cost = $selected.data('cost') ?? 0;
        const $row = $(this).closest('tr');
        $row.find('.current-qty').val(qty);
        $row.find('.unit-cost-input').val(cost);
        recalcFromPhysicalCount($row);
        setComputedAdjustmentType();
    });

    $(document).on('input', '.qty-input, .unit-cost-input', function () {
        if ($(this).hasClass('qty-input')) {
            return;
        }
        recalcRow($(this).closest('tr'));
    });

    $(document).on('input', '.physical-count-input', function () {
        const $row = $(this).closest('tr');
        recalcFromPhysicalCount($row);
        setComputedAdjustmentType();
    });

    $('#add-line').on('click', function () {
        const $row = $('#adjustment-lines-table tbody tr:last').clone();
        $row.find('select').val('');
        $row.find('input').val('');
        $('#adjustment-lines-table tbody').append($row);
        bindRemoveButtons();
        updateTotals();
    });

    $(document).on('click', '.remove-line', function () {
        if ($('#adjustment-lines-table tbody tr').length > 1) {
            $(this).closest('tr').remove();
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

        let invalidLine = false;
        $('.line-row').each(function () {
            const product = $(this).find('.product-select').val();
            const qty = parseFloat($(this).find('.qty-input').val());
            if (!product || !qty || qty <= 0) {
                invalidLine = true;
                return false;
            }
        });

        if (invalidLine) {
            Notiflix.Notify.failure('Please complete all line items correctly');
            return;
        }

        Notiflix.Loading.standard('Saving adjustment, please wait...');

        $.ajax({
            url: $(this).attr('action'),
            type: $(this).attr('method'),
            data: new FormData(this),
            contentType: false,
            processData: false,
            success: function (data) {
                Notiflix.Loading.remove();
                if (data.status === 200) {
                    Notiflix.Notify.success(data.message);
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                } else {
                    Notiflix.Notify.failure(data.message || 'Unable to save adjustment');
                }
            },
            error: function (xhr) {
                Notiflix.Loading.remove();
                $('#errors').empty();

                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    $('#errorAlert').show();
                    $.each(xhr.responseJSON.errors, function (field, messages) {
                        $('#errors').append('<li>' + messages[0] + '</li>');
                        Notiflix.Notify.failure(messages[0]);
                    });
                } else {
                    Notiflix.Notify.failure(xhr.responseJSON?.message || 'Something went wrong');
                }
            }
        });
    });

    bindRemoveButtons();
    setComputedAdjustmentType();
    updateTotals();
});
</script>
@endsection
