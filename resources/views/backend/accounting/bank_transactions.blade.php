@extends('layout.main')
@section('title', 'Bank Transactions')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row mb-3"><div class="col-12"><h4 class="mb-0">Bank Transactions</h4></div></div>
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-info mb-0 py-2">
                    <strong>Cash-In-Hand Balance (Reference):</strong>
                    <span id="cash_in_hand_reference">{{ number_format((float) ($cashInHandBalance ?? 0), 2) }}</span>
                </div>
            </div>
        </div>
        <div class="card"><div class="card-body">
            <h5 id="bank_transaction_form_title">Post Bank Transaction</h5>
            <form id="bank_transaction_form" method="POST" action="{{ route('accounting.setup.bank-transactions.store') }}" data-create-action="{{ route('accounting.setup.bank-transactions.store') }}" data-update-action-template="{{ route('accounting.setup.bank-transactions.update', '__ID__') }}" novalidate>
                @csrf
                <div class="row">
                    <div class="col-md-3 mb-2"><label>Bank Account <span class="text-danger">*</span></label>
                        <select name="bank_account_id" class="form-control" required>
                            <option value="">Select account</option>
                            @foreach($bankAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2"><label>Date <span class="text-danger">*</span></label><input type="date" name="transaction_date" class="form-control" value="{{ now()->toDateString() }}" required></div>
                    <div class="col-md-2 mb-2"><label>Type <span class="text-danger">*</span></label>
                        <select name="type" id="transaction_type" class="form-control" required>
                            <option value="deposit">Deposit</option>
                            <option value="withdrawal">Withdrawal</option>
                            <option value="transfer_out">Transfer Out</option>
                            <option value="bank_fee">Bank Fee</option>
                            <option value="interest_income">Interest Income</option>
                            <option value="receipt">Receipt</option>
                            <option value="payment">Payment</option>
                            <option value="reconciliation_adjustment">Reconciliation Adjustment</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-2 d-none" id="supplier_payment_wrap">
                        <label>Supplier Invoice (Outstanding) <span class="text-danger" id="supplier_payment_required_mark">*</span></label>
                        <select name="purchase_payment_id" id="purchase_payment_id" class="form-control">
                            <option value="">Select invoice</option>
                            @foreach(($supplierPayments ?? collect()) as $item)
                                <option value="{{ $item->id }}">
                                    {{ optional($item->purchase)->purchase_no ?? ('PUR-' . $item->purchase_id) }} - {{ optional($item->supplier)->name ?? 'Supplier' }} (Due: {{ number_format((float) $item->due_amount, 2) }})
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Required when Type is Payment. This reduces supplier due balance.</small>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label>Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
                        <small class="text-muted">Cash-in-hand ref: {{ number_format((float) ($cashInHandBalance ?? 0), 2) }}</small>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label>Counterparty (for transfer out) <span id="counterparty_required_mark" class="text-danger d-none">*</span></label>
                        <select name="counterparty_bank_account_id" id="counterparty_bank_account_id" class="form-control" disabled>
                            <option value="">None</option>
                            @foreach($bankAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Enabled only when Type is Transfer Out.</small>
                    </div>
                    <div class="col-md-3 mb-2"><label>Reference</label><input name="reference_no" class="form-control"></div>
                    <div class="col-md-9 mb-2"><label>Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                </div>
                <button id="bank_transaction_submit" class="btn btn-primary mt-2">Post</button>
                <button type="button" id="bank_transaction_cancel_edit" class="btn btn-light mt-2 d-none">Cancel Edit</button>
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
        const typeSelect = document.getElementById('transaction_type');
        const counterpartySelect = document.getElementById('counterparty_bank_account_id');
        const requiredMark = document.getElementById('counterparty_required_mark');
        const supplierPaymentWrap = document.getElementById('supplier_payment_wrap');
        const supplierPaymentSelect = document.getElementById('purchase_payment_id');
        const form = document.getElementById('bank_transaction_form');
        const submitButton = document.getElementById('bank_transaction_submit');
        const formTitle = document.getElementById('bank_transaction_form_title');
        const cancelEditButton = document.getElementById('bank_transaction_cancel_edit');
        const cashReference = document.getElementById('cash_in_hand_reference');
        let editingId = null;

        if (!typeSelect || !counterpartySelect) {
            return;
        }

        function setCreateMode() {
            editingId = null;
            form.action = form.dataset.createAction;
            formTitle.textContent = 'Post Bank Transaction';
            submitButton.textContent = 'Post';
            if (cancelEditButton) {
                cancelEditButton.classList.add('d-none');
            }
            form.reset();
            typeSelect.value = 'deposit';
            toggleCounterparty();
        }

        function setEditMode(button) {
            editingId = Number(button.data('id'));
            form.action = form.dataset.updateActionTemplate.replace('__ID__', encodeURIComponent(editingId));
            formTitle.textContent = 'Edit Bank Transaction';
            submitButton.textContent = 'Update';
            if (cancelEditButton) {
                cancelEditButton.classList.remove('d-none');
            }

            form.querySelector('[name="bank_account_id"]').value = String(button.data('bank_account_id') || '');
            form.querySelector('[name="transaction_date"]').value = button.data('transaction_date') || '';
            form.querySelector('[name="type"]').value = button.data('type') || 'deposit';
            form.querySelector('[name="amount"]').value = button.data('amount') || '';
            form.querySelector('[name="counterparty_bank_account_id"]').value = String(button.data('counterparty_bank_account_id') || '');
            form.querySelector('[name="purchase_payment_id"]').value = String(button.data('purchase_payment_id') || '');
            form.querySelector('[name="reference_no"]').value = button.data('reference_no') || '';
            form.querySelector('[name="description"]').value = button.data('description') || '';
            toggleCounterparty();

            window.scrollTo({ top: form.offsetTop - 80, behavior: 'smooth' });
        }

        function toggleCounterparty() {
            const isTransferOut = typeSelect.value === 'transfer_out';
            const isSupplierPayment = typeSelect.value === 'payment';

            counterpartySelect.disabled = !isTransferOut;
            counterpartySelect.required = isTransferOut;
            if (!isTransferOut) {
                counterpartySelect.value = '';
            }

            if (requiredMark) {
                requiredMark.classList.toggle('d-none', !isTransferOut);
            }

            if (supplierPaymentWrap && supplierPaymentSelect) {
                supplierPaymentWrap.classList.toggle('d-none', !isSupplierPayment);
                supplierPaymentSelect.required = isSupplierPayment;
                if (!isSupplierPayment) {
                    supplierPaymentSelect.value = '';
                }
            }
        }

        typeSelect.addEventListener('change', toggleCounterparty);
        toggleCounterparty();

        function formatMoney(value) {
            const number = Number(value || 0);
            return number.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function clearValidationErrors() {
            form.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
            form.querySelectorAll('.invalid-feedback.dynamic-error').forEach((el) => el.remove());
        }

        function showFieldError(fieldName, message) {
            const field = form.querySelector('[name="' + fieldName + '"]');
            if (!field) {
                return;
            }

            field.classList.add('is-invalid');
            const feedback = document.createElement('div');
            feedback.className = 'invalid-feedback d-block dynamic-error';
            feedback.textContent = message;
            field.insertAdjacentElement('afterend', feedback);
        }

        async function submitBankTransaction(event) {
            event.preventDefault();
            clearValidationErrors();

            const originalButtonText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.textContent = editingId ? 'Updating...' : 'Posting...';

            try {
                const formData = new FormData(form);
                if (editingId) {
                    formData.append('_method', 'PATCH');
                }

                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                });

                const payload = await response.json();

                if (!response.ok) {
                    if (response.status === 422 && payload.errors) {
                        Object.keys(payload.errors).forEach((fieldName) => {
                            const message = Array.isArray(payload.errors[fieldName]) ? payload.errors[fieldName][0] : payload.errors[fieldName];
                            showFieldError(fieldName, message);
                        });
                        if (window.Notiflix) {
                            Notiflix.Notify.failure('Please correct the highlighted fields.');
                        }
                        return;
                    }

                    if (window.Notiflix) {
                        Notiflix.Notify.failure(payload.message || 'Unable to post bank transaction.');
                    }
                    return;
                }

                if (window.Notiflix) {
                    Notiflix.Notify.success(payload.message || (editingId ? 'Bank transaction updated successfully.' : 'Bank transaction posted successfully.'));
                }

                setCreateMode();

                if (cashReference && typeof payload.cash_in_hand_balance !== 'undefined') {
                    cashReference.textContent = formatMoney(payload.cash_in_hand_balance);
                    form.querySelectorAll('small.text-muted').forEach((el) => {
                        if (el.textContent.indexOf('Cash-in-hand ref:') === 0) {
                            el.textContent = 'Cash-in-hand ref: ' + formatMoney(payload.cash_in_hand_balance);
                        }
                    });
                }

                if (window.LaravelDataTables && window.LaravelDataTables['bank-transactions-table']) {
                    window.LaravelDataTables['bank-transactions-table'].ajax.reload(null, false);
                }
            } catch (error) {
                if (window.Notiflix) {
                    Notiflix.Notify.failure('Network error while posting transaction.');
                }
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = originalButtonText;
            }
        }

        if (form && submitButton) {
            form.addEventListener('submit', submitBankTransaction);
        }

        if (cancelEditButton) {
            cancelEditButton.addEventListener('click', setCreateMode);
        }

        $(document).off('click', '.js-edit-bank-transaction').on('click', '.js-edit-bank-transaction', function () {
            setEditMode($(this));
        });

        $(document).off('click', '.js-delete-bank-transaction').on('click', '.js-delete-bank-transaction', function () {
            const id = $(this).data('id');
            const deleteUrl = '{{ route("accounting.setup.bank-transactions.destroy", "__ID__") }}'.replace('__ID__', encodeURIComponent(id));

            Notiflix.Confirm.show(
                'Delete Bank Transaction',
                'Are you sure you want to delete this transaction?',
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
                            Notiflix.Notify.success(response.message || 'Bank transaction deleted successfully.');

                            if (cashReference && typeof response.cash_in_hand_balance !== 'undefined') {
                                const formatted = formatMoney(response.cash_in_hand_balance);
                                cashReference.textContent = formatted;
                                form.querySelectorAll('small.text-muted').forEach((el) => {
                                    if (el.textContent.indexOf('Cash-in-hand ref:') === 0) {
                                        el.textContent = 'Cash-in-hand ref: ' + formatted;
                                    }
                                });
                            }

                            if (window.LaravelDataTables && window.LaravelDataTables['bank-transactions-table']) {
                                window.LaravelDataTables['bank-transactions-table'].ajax.reload(null, false);
                            }
                        },
                        error: function (xhr) {
                            const message = xhr.responseJSON?.message || 'Unable to delete bank transaction.';
                            Notiflix.Notify.failure(message);
                        }
                    });
                },
                function () {}
            );
        });

        setCreateMode();
    })();
</script>
@endsection

@stack('scripts')
