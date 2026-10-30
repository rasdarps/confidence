@extends('layout.main')
@section('title', 'Capital & Loans')

@section('content')
<style>
    .cl-form-card {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05);
        overflow: hidden;
    }
    .cl-form-header {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 14px 20px;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }
    .cl-form-title {
        font-size: 15px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .cl-flow-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 5px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 600;
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
    }
    .cl-section-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 18px 18px 6px 18px;
        margin-bottom: 16px;
    }
    .cl-field-label {
        font-size: 12.5px;
        font-weight: 600;
        color: #334155;
        margin-bottom: 6px;
        display: block;
    }
    .cl-field-hint {
        font-size: 11.5px;
        color: #64748b;
        margin-top: 5px;
        display: block;
        line-height: 1.35;
    }
    .cl-form-card .form-control {
        width: 100%;
        min-width: 0;
        box-sizing: border-box;
    }
    .cl-form-footer {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding-top: 14px;
        border-top: 1px solid #e2e8f0;
    }
</style>

<div class="page-content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h4 class="mb-0">Capital & Loans</h4>
                    <small class="text-muted">Record Owner Capital, Bank Loans, and Individual Loans received into a specific Outlet's Cash on Hand or Company Bank Account.</small>
                </div>
                <span class="badge bg-info text-dark px-3 py-2">
                    <i class="fa fa-hand-holding-usd me-1"></i> Multi-Outlet Funding &amp; Equity
                </span>
            </div>
        </div>

        {{-- Record / Edit Funding Entry Card --}}
        <div class="card cl-form-card mb-4">
            <div class="cl-form-header">
                <h5 class="cl-form-title">
                    <span class="badge bg-primary rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width:28px;height:28px;">
                        <i class="fa fa-hand-holding-usd" style="font-size:12px;"></i>
                    </span>
                    <span id="capital_loan_form_title">Record Funding Entry</span>
                </h5>
                <div class="cl-flow-pill">
                    <i class="fa fa-book"></i>
                    <span id="cl_journal_flow_text">Dr Cash on Hand (1000) &nbsp;&rarr;&nbsp; Cr Owner's Equity (3000)</span>
                </div>
            </div>

            <div class="card-body p-3 p-md-4">
                <form id="capital_loan_form" method="POST" action="{{ route('accounting.setup.capital-loans.store') }}" data-create-action="{{ route('accounting.setup.capital-loans.store') }}" data-update-action-template="{{ route('accounting.setup.capital-loans.update', '__ID__') }}" novalidate>
                    @csrf
                    <input type="hidden" name="_method" id="capital_loan_method" value="POST">

                    <div class="cl-section-box">
                        {{-- Row 1: Outlet / Location, Date, Funding Type (3 equal columns) --}}
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="cl-field-label" for="outlet_id">Outlet / Location <span class="text-danger">*</span></label>
                                <select name="outlet_id" id="outlet_id" class="form-control" required>
                                    @foreach(($outlets ?? collect()) as $outlet)
                                        <option value="{{ $outlet->id }}" {{ (int) ($defaultOutletId ?? 0) === (int) $outlet->id ? 'selected' : '' }}>
                                            {{ $outlet->name }} ({{ $outlet->code }}) {{ $outlet->is_central ? '[Central]' : '[Retail]' }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="cl-field-hint">Location receiving the funding / journal entry.</span>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="cl-field-label" for="transaction_date">Transaction Date <span class="text-danger">*</span></label>
                                <input type="date" name="transaction_date" id="transaction_date" class="form-control" value="{{ now()->toDateString() }}" required>
                                <span class="cl-field-hint">Posting date on General Ledger.</span>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="cl-field-label" for="entry_type">Funding Type <span class="text-danger">*</span></label>
                                <select name="entry_type" id="entry_type" class="form-control" required>
                                    <option value="owner_capital">Owner Capital (GL 3000)</option>
                                    <option value="bank_loan">Bank Loan (GL 2300)</option>
                                    <option value="individual_loan">Individual Loan (GL 2310)</option>
                                </select>
                                <span class="cl-field-hint">Select equity injection or loan liability type.</span>
                            </div>
                        </div>

                        {{-- Row 2: Receive Into, Amount, Reference (3 equal columns) --}}
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="cl-field-label" for="receive_into">Receive Into <span class="text-danger">*</span></label>
                                <select name="receive_into" id="receive_into" class="form-control" required>
                                    <option value="cash">Cash On Hand (GL 1000)</option>
                                    <option value="bank">Bank Account</option>
                                </select>
                                <span class="cl-field-hint">Destination asset account for received funds.</span>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="cl-field-label" for="capital_amount">Amount (GHS) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0.01" name="amount" id="capital_amount" class="form-control fw-semibold" placeholder="0.00" required>
                                <span class="cl-field-hint">Total capital or loan principal received.</span>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="cl-field-label" for="reference_no">Reference #</label>
                                <input type="text" name="reference_no" id="reference_no" class="form-control" placeholder="e.g. CAP-2026-001">
                                <span class="cl-field-hint">Optional agreement, receipt, or voucher number.</span>
                            </div>
                        </div>

                        {{-- Row 3: Conditional Bank Account, Conditional Lender/Source, and Description --}}
                        <div class="row">
                            <div class="col-md-4 mb-3" id="bank_account_wrap" style="display:none;">
                                <label class="cl-field-label" for="bank_account_id">
                                    Bank Account <span class="text-danger d-none" id="bank_account_required_mark">*</span>
                                </label>
                                <select name="bank_account_id" id="bank_account_id" class="form-control">
                                    <option value="">Select bank account...</option>
                                    @foreach($bankAccounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->name }} — Bal: {{ number_format((float) $account->current_balance, 2) }}</option>
                                    @endforeach
                                </select>
                                <span class="cl-field-hint">Required when funds are received into a Bank Account.</span>
                            </div>

                            <div class="col-md-4 mb-3" id="lender_name_wrap" style="display:none;">
                                <label class="cl-field-label" for="lender_name">
                                    Lender / Source <span class="text-danger d-none" id="lender_required_mark">*</span>
                                </label>
                                <input type="text" name="lender_name" id="lender_name" class="form-control" placeholder="e.g. Ecobank SME Facility or John Doe">
                                <span class="cl-field-hint">Required for Bank Loan and Individual Loan entries.</span>
                            </div>

                            <div class="col-md-12 mb-3" id="cl_description_col">
                                <label class="cl-field-label" for="description">Description / Narration</label>
                                <input type="text" name="description" id="description" class="form-control" placeholder="e.g. Initial working capital injection for retail operations">
                                <span class="cl-field-hint">Optional note recorded on the General Ledger journal.</span>
                            </div>
                        </div>
                    </div>

                    <div class="cl-form-footer">
                        <div class="small text-muted">
                            <i class="fa fa-shield-alt text-primary me-1"></i>
                            Automatically posts a balanced double-entry journal tagged to the selected Outlet.
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" id="capital_loan_cancel_edit" class="btn-modern-cancel d-none">
                                <i class="fa fa-times me-1"></i> Cancel Edit
                            </button>
                            <button id="capital_loan_submit" class="btn-modern-primary">
                                <i class="fa fa-check-circle me-1"></i> Post Entry
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Capital & Loans Table --}}
        <div class="card">
            <div class="card-body table-responsive">
                {!! $dataTable->table(['class' => 'table table-bordered table-striped table-sm w-100'], true) !!}
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
        const flowText = $('#cl_journal_flow_text');
        const descriptionCol = $('#cl_description_col');
        const defaultOutletId = '{{ (int) ($defaultOutletId ?? 0) }}';

        function setCreateMode() {
            form.attr('action', form.data('create-action'));
            methodInput.val('POST');
            formTitle.text('Record Funding Entry');
            submitButton.html('<i class="fa fa-check-circle me-1"></i> Post Entry');
            cancelEditButton.addClass('d-none');
            form[0].reset();
            if (defaultOutletId && defaultOutletId !== '0') {
                form.find('[name="outlet_id"]').val(defaultOutletId);
            }
            syncFundingForm();
        }

        function setEditMode(button) {
            const id = button.data('id');
            form.attr('action', String(form.data('update-action-template')).replace('__ID__', encodeURIComponent(id)));
            methodInput.val('PATCH');
            formTitle.text('Edit Funding Entry');
            submitButton.html('<i class="fa fa-save me-1"></i> Update Entry');
            cancelEditButton.removeClass('d-none');

            const outletId = Number(button.data('outlet_id') || 0);
            if (outletId > 0) {
                form.find('[name="outlet_id"]').val(String(outletId));
            }

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

            let visibleExtraCols = 0;
            if (needBank) visibleExtraCols++;
            if (needLender) visibleExtraCols++;

            if (visibleExtraCols === 2) {
                descriptionCol.attr('class', 'col-md-4 mb-3');
            } else if (visibleExtraCols === 1) {
                descriptionCol.attr('class', 'col-md-8 mb-3');
            } else {
                descriptionCol.attr('class', 'col-md-12 mb-3');
            }

            const debitLabel = needBank ? 'Selected Bank GL' : 'Cash on Hand (1000)';
            const creditLabel = entryType === 'bank_loan'
                ? 'Bank Loan Payable (2300)'
                : (entryType === 'individual_loan' ? 'Individual Loan Payable (2310)' : 'Owner\u2019s Equity (3000)');

            flowText.text('Dr ' + debitLabel + '  \u2192  Cr ' + creditLabel);
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
