@extends('layout.main')
@section('title') {{ 'Create Expense Voucher' }} @endsection

@section('content')
<script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>

<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header with-border">
                        <span class="card-title" style="font-size:20px;">Create | Expense Voucher</span>

                        <a href="{{ route('expense-vouchers.index') }}" style="float:right">
                            <button type="button" class="btn btn-primary modal_btn">View Expense Vouchers</button>
                        </a>

                        <hr>

                        <div class="card-body">
                            <form action="{{ route('expense-vouchers.store') }}" method="POST" id="myForm" novalidate>
                                @csrf

                                <div class="alert alert-danger alert-dismissible fade show" id="errorAlert" style="display: none;">
                                    <button type="button" class="close h-100" data-dismiss="alert" aria-label="Close">
                                        <span><i class="mdi mdi-close"></i></span>
                                    </button>
                                    <ul id="errors"></ul>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="voucher_date"><strong>Voucher Date <span class="text-danger">*</span></strong></label>
                                            <input type="date" id="voucher_date" name="voucher_date" class="form-control" value="{{ old('voucher_date', now()->format('Y-m-d')) }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="reference_no"><strong>Reference No</strong></label>
                                            <input type="text" id="reference_no" name="reference_no" class="form-control" value="{{ old('reference_no') }}" placeholder="receipt/Sale number">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="notes"><strong>Notes</strong></label>
                                            <input type="text" id="notes" name="notes" class="form-control" value="{{ old('notes') }}" placeholder="optional notes">
                                        </div>
                                    </div>
                                </div>

                                <h5 class="mt-3">Expense Lines</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="expense-lines-table">
                                        <thead>
                                            <tr>
                                                <th style="width: 35%;">Category</th>
                                                <th style="width: 20%;">Amount</th>
                                                <th>Description</th>
                                                <th style="width: 10%;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="expense-line-row">
                                                <td>
                                                    <input type="hidden" name="item_id[]" value="">
                                                    <select name="expense_category_id[]" class="form-control expense-category select2" required>
                                                        <option value="">Select Category</option>
                                                        @foreach($expenseCategories as $category)
                                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="number" step="0.01" min="0.01" name="amount[]" class="form-control amount-input" placeholder="0.00" required>
                                                </td>
                                                <td>
                                                    <input type="text" name="description[]" class="form-control" placeholder="line description">
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-danger btn-sm remove-line" disabled>
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th class="text-right">Total</th>
                                                <th><span id="line-total">0.00</span></th>
                                                <th colspan="2"></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                                <div class="mb-3">
                                    <button type="button" id="add-line" class="btn btn-info btn-sm">
                                        <i class="fas fa-plus-circle"></i> Add Line
                                    </button>
                                </div>

                                <div class="row">
                                    <div class="col-12 text-center">
                                        <button type="submit" class="btn btn-success mt-2">Submit Voucher</button>
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
        const categoryOptionsHtml = $('#expense-lines-table tbody tr:first .expense-category').html();

        function updateTotal() {
            let total = 0;
            $('.amount-input').each(function () {
                const value = parseFloat($(this).val());
                if (!isNaN(value)) {
                    total += value;
                }
            });
            $('#line-total').text(total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        }

        function bindRemoveButtons() {
            const rowCount = $('#expense-lines-table tbody tr').length;
            $('.remove-line').prop('disabled', rowCount <= 1);
        }

        function buildExpenseLineRow() {
            return $(
                '<tr class="expense-line-row">' +
                    '<td>' +
                        '<input type="hidden" name="item_id[]" value="">' +
                        '<select name="expense_category_id[]" class="form-control expense-category select2" required>' +
                            categoryOptionsHtml +
                        '</select>' +
                    '</td>' +
                    '<td>' +
                        '<input type="number" step="0.01" min="0.01" name="amount[]" class="form-control amount-input" placeholder="0.00" required>' +
                    '</td>' +
                    '<td>' +
                        '<input type="text" name="description[]" class="form-control" placeholder="line description">' +
                    '</td>' +
                    '<td class="text-center">' +
                        '<button type="button" class="btn btn-danger btn-sm remove-line" disabled>' +
                            '<i class="fas fa-trash"></i>' +
                        '</button>' +
                    '</td>' +
                '</tr>'
            );
        }

        $('#add-line').on('click', function () {
            const $newRow = buildExpenseLineRow();
            $('#expense-lines-table tbody').append($newRow);

            if ($.fn.select2) {
                $newRow.find('.expense-category').val('').select2({
                    width: '100%'
                });
            } else {
                $newRow.find('.expense-category').val('');
            }

            bindRemoveButtons();
        });

        $(document).on('click', '.remove-line', function () {
            if ($('#expense-lines-table tbody tr').length > 1) {
                $(this).closest('tr').remove();
                bindRemoveButtons();
                updateTotal();
            }
        });

        $(document).on('input', '.amount-input', function () {
            updateTotal();
        });

        $('#myForm').on('submit', function (e) {
            e.preventDefault();
            if (!$('#voucher_date').val()) {
                Notiflix.Notify.failure('Please select voucher date');
                return;
            }

            let hasInvalidCategory = false;
            let hasInvalidAmount = false;

            $('.expense-category').each(function () {
                if (!$(this).val()) {
                    hasInvalidCategory = true;
                    return false;
                }
            });

            $('.amount-input').each(function () {
                const value = parseFloat($(this).val());
                if (isNaN(value) || value <= 0) {
                    hasInvalidAmount = true;
                    return false;
                }
            });

            if (hasInvalidCategory) {
                Notiflix.Notify.failure('Please select category for all lines');
                return;
            }

            if (hasInvalidAmount) {
                Notiflix.Notify.failure('Please enter valid amount for all lines');
                return;
            }

            Notiflix.Loading.standard('Saving voucher, please wait...');

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
                        Notiflix.Notify.failure(data.message || 'Unable to create voucher');
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
                        $('html, body').animate({ scrollTop: 0 }, 'slow');
                    } else {
                        Notiflix.Notify.failure(xhr.responseJSON?.message || 'Something went wrong');
                    }
                }
            });
        });

        bindRemoveButtons();
        updateTotal();
    });
</script>
@endsection

