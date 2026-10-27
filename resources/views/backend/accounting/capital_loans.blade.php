@extends('layout.main')
@section('title', 'Capital & Loans')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12"><h4 class="mb-0">Capital & Loans</h4></div>
        </div>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title" id="capital_loan_form_title">Record Funding Entry</h5>
                <form id="capital_loan_form" method="POST" action="{{ route('accounting.setup.capital-loans.store') }}" data-create-action="{{ route('accounting.setup.capital-loans.store') }}" data-update-action-template="{{ route('accounting.setup.capital-loans.update', '__ID__') }}" novalidate>
                    @csrf
                    <input type="hidden" name="_method" id="capital_loan_method" value="POST">
                    <div class="row">
                        <div class="col-md-2 mb-2">
                            <label>Date <span class="text-danger">*</span></label>
                            <input type="date" name="transaction_date" class="form-control" value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-md-2 mb-2">
                            <label>Type <span class="text-danger">*</span></label>
                            <select name="entry_type" id="entry_type" class="form-control" required>
                                <option value="owner_capital">Owner Capital</option>
                                <option value="bank_loan">Bank Loan</option>
                                <option value="individual_loan">Individual Loan</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <label>Receive Into <span class="text-danger">*</span></label>
                            <select name="receive_into" id="receive_into" class="form-control" required>
                                <option value="cash">Cash On Hand</option>
                                <option value="bank">Bank Account</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-2" id="bank_account_wrap" style="display:none;">
                            <label>Bank Account <span class="text-danger d-none" id="bank_account_required_mark">*</span></label>
                            <select name="bank_account_id" id="bank_account_id" class="form-control">
                                <option value="">Select account</option>
                                @foreach($bankAccounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-2" id="lender_name_wrap" style="display:none;">
                            <label>Lender/Source <span class="text-danger d-none" id="lender_required_mark">*</span></label>
                            <input type="text" name="lender_name" id="lender_name" class="form-control" placeholder="e.g. ABC Bank or John Doe">
                        </div>
                        <div class="col-md-2 mb-2">
                            <label>Amount <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label>Reference</label>
                            <input type="text" name="reference_no" class="form-control" placeholder="Optional">
                        </div>
                        <div class="col-md-7 mb-2">
                            <label>Description</label>
                            <input type="text" name="description" class="form-control" placeholder="Optional note">
                        </div>
                    </div>
                    <button id="capital_loan_submit" class="btn btn-primary mt-2">Post Entry</button>
                    <button type="button" id="capital_loan_cancel_edit" class="btn btn-light mt-2 d-none">Cancel Edit</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body table-responsive">
                {!! $dataTable->table(['class' => 'table table-bordered table-striped table-sm'], true) !!}
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
{{ $dataTable->scripts() }}
<script>
    $(function () {
        const form = $('#capital_loan_form');
        const formTitle = $('#capital_loan_form_title');
        const methodInput = $('#capital_loan_method');
        const submitButton = $('#capital_loan_submit');
        const cancelEditButton = $('#capital_loan_cancel_edit');

        function setCreateMode() {
            form.attr('action', form.data('create-action'));
            methodInput.val('POST');
            formTitle.text('Record Funding Entry');
            submitButton.text('Post Entry');
            cancelEditButton.addClass('d-none');
            form[0].reset();
            syncFundingForm();
        }

        function setEditMode(button) {
            const id = button.data('id');
            form.attr('action', String(form.data('update-action-template')).replace('__ID__', encodeURIComponent(id)));
            methodInput.val('PATCH');
            formTitle.text('Edit Funding Entry');
            submitButton.text('Update Entry');
            cancelEditButton.removeClass('d-none');

            form.find('[name="transaction_date"]').val(button.data('transaction_date') || '');
            form.find('[name="entry_type"]').val(button.data('entry_type') || 'owner_capital');
            form.find('[name="receive_into"]').val(button.data('receive_into') || 'cash');
            form.find('[name="bank_account_id"]').val(String(button.data('bank_account_id') || ''));
            form.find('[name="lender_name"]').val(button.data('lender_name') || '');
            form.find('[name="amount"]').val(button.data('amount') || '');
            form.find('[name="reference_no"]').val(button.data('reference_no') || '');
            form.find('[name="description"]').val(button.data('description') || '');

            syncFundingForm();
            window.scrollTo({ top: form.offset().top - 80, behavior: 'smooth' });
        }

        function syncFundingForm() {
            const entryType = $('#entry_type').val();
            const receiveInto = $('#receive_into').val();

            const needLender = entryType === 'bank_loan' || entryType === 'individual_loan';
            const needBank = receiveInto === 'bank';

            $('#lender_name_wrap').toggle(needLender);
            $('#lender_name').prop('required', needLender);
            $('#lender_required_mark').toggleClass('d-none', !needLender);

            $('#bank_account_wrap').toggle(needBank);
            $('#bank_account_id').prop('required', needBank);
            $('#bank_account_required_mark').toggleClass('d-none', !needBank);

            if (!needLender) {
                $('#lender_name').val('');
            }

            if (!needBank) {
                $('#bank_account_id').val('');
            }
        }

        $('#entry_type, #receive_into').on('change', syncFundingForm);
        syncFundingForm();

        $(document).off('click', '.js-edit-capital-loan').on('click', '.js-edit-capital-loan', function () {
            setEditMode($(this));
        });

        cancelEditButton.on('click', function () {
            setCreateMode();
        });

        $(document).off('click', '.js-delete-capital-loan').on('click', '.js-delete-capital-loan', function () {
            const id = $(this).data('id');
            const deleteUrl = '{{ route("accounting.setup.capital-loans.destroy", "__ID__") }}'.replace('__ID__', encodeURIComponent(id));

            Notiflix.Confirm.show(
                'Delete Capital or Loan Entry',
                'Are you sure you want to delete this entry?',
                'Yes, Delete',
                'Cancel',
                function () {
                    $.ajax({
                        url: deleteUrl,
                        type: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                            'Accept': 'application/json'
                        },
                        success: function (response) {
                            Notiflix.Notify.success(response.message || 'Capital or loan entry deleted successfully.');
                            if (window.LaravelDataTables && window.LaravelDataTables['capital-loan-entries-table']) {
                                window.LaravelDataTables['capital-loan-entries-table'].ajax.reload(null, false);
                            }
                        },
                        error: function (xhr) {
                            const message = xhr.responseJSON?.message || 'Unable to delete capital or loan entry.';
                            Notiflix.Notify.failure(message);
                        }
                    });
                },
                function () {}
            );
        });

        setCreateMode();
    });
</script>
@endsection

@stack('scripts')
