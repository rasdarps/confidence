@extends('layout.main')
@section('title', 'Bank Accounts')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row mb-3"><div class="col-12"><h4 class="mb-0">Bank Accounts</h4></div></div>
        <div class="card"><div class="card-body">
            <h5 id="bank_account_form_title">Create Bank Account</h5>
            <form id="bank_account_form" method="POST" action="{{ route('accounting.setup.bank-accounts.store') }}" data-create-action="{{ route('accounting.setup.bank-accounts.store') }}" data-update-action-template="{{ route('accounting.setup.bank-accounts.update', '__ID__') }}" novalidate>
                @csrf
                <input type="hidden" name="_method" id="bank_account_method" value="POST">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <label>Account Name (Internal) <span class="text-danger">*</span></label>
                        <input class="form-control" name="name" required>
                        <small class="text-muted">A unique internal label for this specific account (for example, Main Operations Account). This is what users see in POS dropdowns and reports.</small>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label>Bank / Institution <span class="text-danger">*</span></label>
                        <input class="form-control" name="bank_name" required>
                        <small class="text-muted">The real bank name, for example Ecobank or GCB Bank.</small>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label>Account Number <span class="text-danger">*</span></label>
                        <input class="form-control" name="account_number" required>
                        <small class="text-muted">Exact number from your bank statement.</small>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label>Currency <span class="text-danger">*</span></label>
                        <input class="form-control" name="currency" value="GHS" required>
                    </div>
                    <div class="col-md-2 mb-2"><label>Opening Balance <span class="text-danger">*</span></label><input type="number" step="0.01" class="form-control" id="opening_balance" name="opening_balance" value="0" required></div>
                    <div class="col-md-4 mb-2">
                        <label>Linked GL Account <span class="text-danger">*</span></label>
                        <select name="gl_account_id" id="gl_account_id" class="form-control" required>
                            <option value="">Select account</option>
                            @foreach($glAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Journal entries will post to this chart of account.</small>
                    </div>
                    <div class="col-md-2 mb-2 d-flex align-items-end">
                        <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked id="active"><label class="form-check-label" for="active">Active</label></div>
                    </div>
                </div>
                <button id="bank_account_submit" class="btn btn-primary mt-2">Save</button>
                <button type="button" id="bank_account_cancel_edit" class="btn btn-light mt-2 d-none">Cancel Edit</button>
            </form>
        </div></div>

        <div class="card"><div class="card-body table-responsive">
            {!! $dataTable->table(['class' => 'table table-bordered table-striped table-sm'], true) !!}
        </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
{{ $dataTable->scripts() }}
<script>
    (function () {
        const form = document.getElementById('bank_account_form');
        const title = document.getElementById('bank_account_form_title');
        const methodInput = document.getElementById('bank_account_method');
        const submitButton = document.getElementById('bank_account_submit');
        const cancelButton = document.getElementById('bank_account_cancel_edit');
        const openingBalance = document.getElementById('opening_balance');

        function resetToCreateMode() {
            if (!form) {
                return;
            }

            form.reset();
            form.action = form.dataset.createAction;
            methodInput.value = 'POST';
            title.textContent = 'Create Bank Account';
            submitButton.textContent = 'Save';
            cancelButton.classList.add('d-none');
            if (openingBalance) {
                openingBalance.disabled = false;
            }

            const active = document.getElementById('active');
            if (active) {
                active.checked = true;
            }
        }

        $(document).off('click', '.js-edit-bank-account').on('click', '.js-edit-bank-account', function () {
            const btn = $(this);
            const id = btn.data('id');
            const updateAction = form.dataset.updateActionTemplate.replace('__ID__', encodeURIComponent(id));

            form.action = updateAction;
            methodInput.value = 'PATCH';
            title.textContent = 'Edit Bank Account';
            submitButton.textContent = 'Update';
            cancelButton.classList.remove('d-none');

            form.querySelector('[name="name"]').value = btn.data('name') || '';
            form.querySelector('[name="bank_name"]').value = btn.data('bank_name') || '';
            form.querySelector('[name="account_number"]').value = btn.data('account_number') || '';
            form.querySelector('[name="currency"]').value = btn.data('currency') || 'GHS';
            form.querySelector('[name="gl_account_id"]').value = String(btn.data('gl_account_id') || '');

            if (openingBalance) {
                openingBalance.value = '0';
                openingBalance.disabled = true;
            }

            const active = document.getElementById('active');
            if (active) {
                active.checked = Number(btn.data('is_active') || 0) === 1;
            }

            window.scrollTo({ top: form.offsetTop - 80, behavior: 'smooth' });
        });

        if (cancelButton) {
            cancelButton.addEventListener('click', resetToCreateMode);
        }

        function bindDeleteBankAccount() {
            $(document).off('click', '.js-delete-bank-account').on('click', '.js-delete-bank-account', function () {
                const id = $(this).data('id');
                const deleteUrl = '{{ route("accounting.setup.bank-accounts.destroy", "__ID__") }}'.replace('__ID__', encodeURIComponent(id));

                Notiflix.Confirm.show(
                    'Delete Bank Account',
                    'Are you sure you want to delete this bank account?',
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
                                Notiflix.Notify.success(response.message || 'Bank account deleted successfully.');
                                if (window.LaravelDataTables && window.LaravelDataTables['bank-accounts-table']) {
                                    window.LaravelDataTables['bank-accounts-table'].ajax.reload(null, false);
                                }
                            },
                            error: function (xhr) {
                                const message = xhr.responseJSON?.message || 'Unable to delete bank account.';
                                Notiflix.Notify.failure(message);
                            }
                        });
                    },
                    function () {}
                );
            });
        }

        bindDeleteBankAccount();
        resetToCreateMode();
    })();
</script>
@endsection

@stack('scripts')
