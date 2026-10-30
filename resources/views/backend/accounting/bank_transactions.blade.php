@extends('layout.main')
@section('title', 'Bank Transactions')

@section('content')
@php
    $totalBankBalance = (float) ($bankAccounts ?? collect())->sum('current_balance');
@endphp
<style>
    .bt-kpi-card {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #ffffff;
        padding: 14px 18px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    .bt-kpi-card.bt-kpi-primary {
        border-left: 4px solid #2563eb;
        background: linear-gradient(135deg, #f8fbff 0%, #ffffff 100%);
    }
    .bt-kpi-card.bt-kpi-teal {
        border-left: 4px solid #0d9488;
    }
    .bt-kpi-card.bt-kpi-indigo {
        border-left: 4px solid #4f46e5;
    }
    .bt-kpi-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        margin-bottom: 4px;
    }
    .bt-kpi-value {
        font-size: 20px;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.2;
    }
    .bt-kpi-sub {
        font-size: 11.5px;
        color: #64748b;
        margin-top: 2px;
    }
    .bt-kpi-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }
    .bt-form-card {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05);
        overflow: hidden;
    }
    .bt-form-header {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 14px 20px;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }
    .bt-form-title {
        font-size: 15px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .bt-flow-pill {
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
    .bt-section-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 18px 18px 6px 18px;
        margin-bottom: 16px;
    }
    .bt-field-label {
        font-size: 12.5px;
        font-weight: 600;
        color: #334155;
        margin-bottom: 6px;
        display: block;
    }
    .bt-field-hint {
        font-size: 11.5px;
        color: #64748b;
        margin-top: 5px;
        display: block;
        line-height: 1.35;
    }
    .bt-form-card .form-control {
        width: 100%;
        min-width: 0;
        box-sizing: border-box;
    }
    .bt-form-footer {
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
                    <h4 class="mb-0">Bank Transactions</h4>
                    <small class="text-muted">Post deposits, withdrawals, supplier settlements, and inter-bank transfers linked to the specific Outlet / Location.</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    @can('bank-account-list')
                        <a href="{{ route('accounting.setup.bank-accounts.index') }}" class="btn-modern-header">
                            <i class="fa fa-piggy-bank me-1"></i> Manage Bank Accounts
                        </a>
                    @endcan
                </div>
            </div>
        </div>

        {{-- KPI Summary Strip --}}
        <div class="row mb-3">
            <div class="col-md-4 mb-2 mb-md-0">
                <div class="bt-kpi-card bt-kpi-primary">
                    <div>
                        <div class="bt-kpi-label">Selected Outlet Cash-In-Hand (GL 1000)</div>
                        <div class="bt-kpi-value" id="cash_in_hand_reference">0.00</div>
                        <div class="bt-kpi-sub" id="selected_outlet_name_hint">Select an outlet below to view its available cash</div>
                    </div>
                    <div class="bt-kpi-icon" style="background:#dbeafe;color:#1d4ed8;">
                        <i class="fa fa-cash-register"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-2 mb-md-0">
                <div class="bt-kpi-card bt-kpi-teal">
                    <div>
                        <div class="bt-kpi-label">Consolidated Cash-In-Hand (All Outlets)</div>
                        <div class="bt-kpi-value" id="consolidated_cash_in_hand_reference">{{ number_format((float) ($consolidatedCashInHandBalance ?? 0), 2) }}</div>
                        <div class="bt-kpi-sub">Combined GL 1000 across Central &amp; Retail branches</div>
                    </div>
                    <div class="bt-kpi-icon" style="background:#ccfbf1;color:#0f766e;">
                        <i class="fa fa-coins"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="bt-kpi-card bt-kpi-indigo">
                    <div>
                        <div class="bt-kpi-label">Active Bank Accounts ({{ ($bankAccounts ?? collect())->count() }})</div>
                        <div class="bt-kpi-value">{{ number_format($totalBankBalance, 2) }}</div>
                        <div class="bt-kpi-sub">Total treasury balance across active bank accounts</div>
                    </div>
                    <div class="bt-kpi-icon" style="background:#e0e7ff;color:#4338ca;">
                        <i class="fa fa-university"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Post Bank Transaction Card --}}
        <div class="card bt-form-card mb-4">
            <div class="bt-form-header">
                <h5 class="bt-form-title">
                    <span class="badge bg-primary rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width:28px;height:28px;">
                        <i class="fa fa-exchange-alt" style="font-size:12px;"></i>
                    </span>
                    <span id="bank_transaction_form_title">Post Bank Transaction</span>
                </h5>
                <div id="bt_journal_flow_badge" class="bt-flow-pill">
                    <i class="fa fa-book"></i>
                    <span id="bt_journal_flow_text">Dr Bank GL &nbsp;&rarr;&nbsp; Cr Outlet Cash on Hand (1000)</span>
                </div>
            </div>

            <div class="card-body p-3 p-md-4">
                <form id="bank_transaction_form" method="POST" action="{{ route('accounting.setup.bank-transactions.store') }}" data-create-action="{{ route('accounting.setup.bank-transactions.store') }}" data-update-action-template="{{ route('accounting.setup.bank-transactions.update', '__ID__') }}" novalidate>
                    @csrf

                    <div class="bt-section-box">
                        {{-- Row 1: Location, Bank Account, Transaction Type (3 equal columns) --}}
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="bt-field-label" for="outlet_id">Outlet / Location <span class="text-danger">*</span></label>
                                <select name="outlet_id" id="outlet_id" class="form-control" required>
                                    <option value="">Select outlet...</option>
                                    @foreach(($outlets ?? collect()) as $outlet)
                                        @php
                                            $outletCashAmt = (float) ($cashInHandByOutlet[(string) $outlet->id] ?? 0);
                                            $outletLabelBase = $outlet->name . ' (' . $outlet->code . ') ' . ($outlet->is_central ? '[Central]' : '[Retail]');
                                        @endphp
                                        <option value="{{ $outlet->id }}" data-base-label="{{ $outletLabelBase }}" data-outlet-name="{{ $outlet->name }}">
                                            {{ $outletLabelBase }} — Cash: {{ number_format($outletCashAmt, 2) }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="bt-field-hint" id="outlet_field_hint">Branch whose Cash on Hand (GL 1000) is affected.</span>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="bt-field-label" for="bank_account_id">Bank Account <span class="text-danger">*</span></label>
                                <select name="bank_account_id" id="bank_account_id" class="form-control" required>
                                    <option value="">Select bank account...</option>
                                    @foreach($bankAccounts as $acc)
                                        <option value="{{ $acc->id }}">{{ $acc->name }} — Bal: {{ number_format((float) $acc->current_balance, 2) }}</option>
                                    @endforeach
                                </select>
                                <span class="bt-field-hint">Target company treasury account.</span>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="bt-field-label" for="transaction_type">Transaction Type <span class="text-danger">*</span></label>
                                <select name="type" id="transaction_type" class="form-control" required>
                                    <option value="">Select transaction type...</option>
                                    <option value="deposit">Deposit (Cash to Bank)</option>
                                    <option value="withdrawal">Withdrawal (Bank to Cash)</option>
                                    <option value="transfer_out">Transfer Out (Bank to Bank)</option>
                                    <option value="payment">Payment (Supplier AP)</option>
                                    <option value="receipt">Receipt (Customer AR)</option>
                                    <option value="bank_fee">Bank Fee / Charges</option>
                                    <option value="interest_income">Interest Income</option>
                                    <option value="reconciliation_adjustment">Reconciliation Adjustment</option>
                                </select>
                                <span class="bt-field-hint">Determines GL debit &amp; credit pair.</span>
                            </div>
                        </div>

                        {{-- Row 2: Transaction Date, Amount, Counterparty (3 equal columns) --}}
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="bt-field-label" for="transaction_date">Transaction Date <span class="text-danger">*</span></label>
                                <input type="date" name="transaction_date" id="transaction_date" class="form-control" value="{{ now()->toDateString() }}" required>
                                <span class="bt-field-hint">Posting date on General Ledger.</span>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="bt-field-label" for="transaction_amount">Amount (GHS) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0.01" name="amount" id="transaction_amount" class="form-control fw-semibold" placeholder="0.00" required>
                                <span class="bt-field-hint" id="cash_in_hand_hint">Outlet cash ref: {{ number_format((float) ($cashInHandBalance ?? 0), 2) }}</span>
                            </div>

                            <div class="col-md-4 mb-3" id="counterparty_wrap">
                                <label class="bt-field-label" for="counterparty_bank_account_id">
                                    Counterparty (for transfer out) <span id="counterparty_required_mark" class="text-danger d-none">*</span>
                                </label>
                                <select name="counterparty_bank_account_id" id="counterparty_bank_account_id" class="form-control" disabled>
                                    <option value="">None</option>
                                    @foreach($bankAccounts as $acc)
                                        <option value="{{ $acc->id }}">{{ $acc->name }} — Bal: {{ number_format((float) $acc->current_balance, 2) }}</option>
                                    @endforeach
                                </select>
                                <span class="bt-field-hint">Enabled only when Type is Transfer Out.</span>
                            </div>
                        </div>

                        {{-- Row 3: Supplier Invoice (when Payment), Reference & Description --}}
                        <div class="row">
                            <div class="col-md-4 mb-3 d-none" id="supplier_payment_wrap">
                                <label class="bt-field-label" for="purchase_payment_id">
                                    Supplier Invoice (Outstanding) <span class="text-danger" id="supplier_payment_required_mark">*</span>
                                </label>
                                <select name="purchase_payment_id" id="purchase_payment_id" class="form-control">
                                    <option value="">Select outstanding supplier invoice...</option>
                                    @foreach(($supplierPayments ?? collect()) as $item)
                                        <option value="{{ $item->id }}" data-outlet_id="{{ (int) (optional($item->purchase)->outlet_id ?? 0) }}" data-due="{{ (float) $item->due_amount }}">
                                            {{ optional($item->purchase)->purchase_no ?? ('PUR-' . $item->purchase_id) }} — {{ optional($item->supplier)->name ?? 'Supplier' }} (Due: {{ number_format((float) $item->due_amount, 2) }})
                                        </option>
                                    @endforeach
                                </select>
                                <span class="bt-field-hint">Required when Type is Payment. Reduces supplier due balance.</span>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="bt-field-label" for="reference_no">Reference / Slip / Cheque #</label>
                                <input type="text" name="reference_no" id="reference_no" class="form-control" placeholder="e.g. DEP-2026-0041">
                                <span class="bt-field-hint">Optional bank deposit slip or cheque reference.</span>
                            </div>

                            <div class="col-md-8 mb-3" id="description_col">
                                <label class="bt-field-label" for="description">Description / Narration</label>
                                <input type="text" name="description" id="description" class="form-control" placeholder="e.g. Daily cash sales deposit at Ecobank branch">
                                <span class="bt-field-hint">Optional narration for the General Ledger entry.</span>
                            </div>
                        </div>
                    </div>

                    <div class="bt-form-footer">
                        <div class="small text-muted">
                            <i class="fa fa-shield-alt text-primary me-1"></i>
                            Double-entry journal is automatically posted and linked to the selected Outlet.
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" id="bank_transaction_cancel_edit" class="btn-modern-cancel d-none">
                                <i class="fa fa-times me-1"></i> Cancel Edit
                            </button>
                            <button id="bank_transaction_submit" class="btn-modern-primary">
                                <i class="fa fa-check-circle me-1"></i> Post Transaction
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Transactions History Table --}}
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
    (function () {
        const typeSelect = document.getElementById('transaction_type');
        const outletSelect = document.getElementById('outlet_id');
        const counterpartyWrap = document.getElementById('counterparty_wrap');
        const counterpartySelect = document.getElementById('counterparty_bank_account_id');
        const requiredMark = document.getElementById('counterparty_required_mark');
        const supplierPaymentWrap = document.getElementById('supplier_payment_wrap');
        const supplierPaymentSelect = document.getElementById('purchase_payment_id');
        const descriptionCol = document.getElementById('description_col');
        const form = document.getElementById('bank_transaction_form');
        const submitButton = document.getElementById('bank_transaction_submit');
        const formTitle = document.getElementById('bank_transaction_form_title');
        const cancelEditButton = document.getElementById('bank_transaction_cancel_edit');
        const cashReference = document.getElementById('cash_in_hand_reference');
        const consolidatedCashReference = document.getElementById('consolidated_cash_in_hand_reference');
        const selectedOutletNameHint = document.getElementById('selected_outlet_name_hint');
        const cashHint = document.getElementById('cash_in_hand_hint');
        const flowText = document.getElementById('bt_journal_flow_text');
        const defaultOutletId = '{{ (int) ($defaultOutletId ?? 0) }}';
        let cashInHandByOutlet = @json($cashInHandByOutlet ?? []);
        let editingId = null;

        if (!typeSelect || !counterpartySelect) {
            return;
        }

        const flowDescriptions = {
            deposit: 'Dr Bank GL  \u2192  Cr Outlet Cash on Hand (1000)',
            withdrawal: 'Dr Outlet Cash on Hand (1000)  \u2192  Cr Bank GL',
            transfer_out: 'Dr Destination Bank GL  \u2192  Cr Source Bank GL',
            payment: 'Dr Accounts Payable (2000)  \u2192  Cr Bank GL',
            receipt: 'Dr Bank GL  \u2192  Cr Accounts Receivable (1100)',
            bank_fee: 'Dr Bank Charges (5300)  \u2192  Cr Bank GL',
            interest_income: 'Dr Bank GL  \u2192  Cr Interest Income (5400)',
            reconciliation_adjustment: 'Dr Bank GL  \u2192  Cr Owner\u2019s Equity (3000)'
        };

        function formatMoney(value) {
            const number = Number(value || 0);
            return number.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function refreshCashDisplay() {
            const selectedOutlet = outletSelect ? String(outletSelect.value || '') : '';
            const hasOutlet = Boolean(selectedOutlet && typeof cashInHandByOutlet[selectedOutlet] !== 'undefined');
            const outletCash = hasOutlet ? cashInHandByOutlet[selectedOutlet] : 0;

            if (outletSelect) {
                Array.from(outletSelect.options).forEach((opt) => {
                    const baseLabel = opt.getAttribute('data-base-label');
                    const optId = String(opt.value || '');
                    if (baseLabel && optId && typeof cashInHandByOutlet[optId] !== 'undefined') {
                        opt.textContent = baseLabel + ' \u2014 Cash: ' + formatMoney(cashInHandByOutlet[optId]);
                    }
                });

                const activeOpt = outletSelect.options[outletSelect.selectedIndex];
                const outletName = (hasOutlet && activeOpt) ? (activeOpt.getAttribute('data-outlet-name') || 'Selected outlet') : '';
                if (selectedOutletNameHint) {
                    selectedOutletNameHint.textContent = hasOutlet
                        ? ('Available physical cash at ' + outletName)
                        : 'Select an outlet below to view its available cash';
                }
            }

            if (cashReference) {
                cashReference.textContent = formatMoney(outletCash);
            }
            if (consolidatedCashReference && typeof cashInHandByOutlet.all !== 'undefined') {
                consolidatedCashReference.textContent = formatMoney(cashInHandByOutlet.all);
            }
            if (cashHint) {
                cashHint.textContent = hasOutlet
                    ? ('Outlet cash ref: ' + formatMoney(outletCash))
                    : 'Outlet cash ref: Select an outlet';
            }
        }

        if (outletSelect) {
            outletSelect.addEventListener('change', refreshCashDisplay);
        }

        if (supplierPaymentSelect && outletSelect) {
            supplierPaymentSelect.addEventListener('change', function () {
                const selectedOpt = supplierPaymentSelect.options[supplierPaymentSelect.selectedIndex];
                const invoiceOutletId = selectedOpt ? Number(selectedOpt.getAttribute('data-outlet_id') || 0) : 0;
                if (invoiceOutletId > 0) {
                    outletSelect.value = String(invoiceOutletId);
                    refreshCashDisplay();
                }
            });
        }

        function setCreateMode() {
            editingId = null;
            form.action = form.dataset.createAction;
            formTitle.textContent = 'Post Bank Transaction';
            submitButton.innerHTML = '<i class="fa fa-check-circle me-1"></i> Post Transaction';
            if (cancelEditButton) {
                cancelEditButton.classList.add('d-none');
            }
            form.reset();
            if (outletSelect) {
                outletSelect.value = '';
            }
            typeSelect.value = '';
            toggleCounterparty();
            refreshCashDisplay();
        }

        function setEditMode(button) {
            editingId = Number(button.data('id'));
            form.action = form.dataset.updateActionTemplate.replace('__ID__', encodeURIComponent(editingId));
            formTitle.textContent = 'Edit Bank Transaction';
            submitButton.innerHTML = '<i class="fa fa-save me-1"></i> Update Transaction';
            if (cancelEditButton) {
                cancelEditButton.classList.remove('d-none');
            }

            const btnOutletId = Number(button.data('outlet_id') || 0);
            if (outletSelect) {
                outletSelect.value = btnOutletId > 0 ? String(btnOutletId) : '';
            }

            form.querySelector('[name="bank_account_id"]').value = String(button.data('bank_account_id') || '');
            form.querySelector('[name="transaction_date"]').value = button.data('transaction_date') || '';
            form.querySelector('[name="type"]').value = button.data('type') || '';
            form.querySelector('[name="amount"]').value = button.data('amount') || '';
            form.querySelector('[name="counterparty_bank_account_id"]').value = String(button.data('counterparty_bank_account_id') || '');
            form.querySelector('[name="purchase_payment_id"]').value = String(button.data('purchase_payment_id') || '');
            form.querySelector('[name="reference_no"]').value = button.data('reference_no') || '';
            form.querySelector('[name="description"]').value = button.data('description') || '';
            toggleCounterparty();
            refreshCashDisplay();

            window.scrollTo({ top: form.offsetTop - 80, behavior: 'smooth' });
        }

        function toggleCounterparty() {
            const currentType = typeSelect.value;
            const isTransferOut = currentType === 'transfer_out';
            const isSupplierPayment = currentType === 'payment';

            if (flowText) {
                flowText.textContent = flowDescriptions[currentType] || 'Select Transaction Type to preview GL posting';
            }

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

            if (descriptionCol) {
                descriptionCol.className = isSupplierPayment ? 'col-md-4 mb-3' : 'col-md-8 mb-3';
            }
        }

        typeSelect.addEventListener('change', toggleCounterparty);
        toggleCounterparty();

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

            const originalButtonHtml = submitButton.innerHTML;
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

                if (payload.cash_in_hand_by_outlet) {
                    cashInHandByOutlet = payload.cash_in_hand_by_outlet;
                }

                setCreateMode();

                if (window.LaravelDataTables && window.LaravelDataTables['bank-transactions-table']) {
                    window.LaravelDataTables['bank-transactions-table'].ajax.reload(null, false);
                }
            } catch (error) {
                if (window.Notiflix) {
                    Notiflix.Notify.failure('Network error while posting transaction.');
                }
            } finally {
                submitButton.disabled = false;
                if (editingId) {
                    submitButton.innerHTML = originalButtonHtml;
                }
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

                            if (response.cash_in_hand_by_outlet) {
                                cashInHandByOutlet = response.cash_in_hand_by_outlet;
                                refreshCashDisplay();
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
