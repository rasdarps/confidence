@extends('layout.main')
@section('title', 'Bank Accounts')

@section('content')
<style>
    .ba-kpi-card {
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
    .ba-kpi-card.ba-kpi-primary {
        border-left: 4px solid #2563eb;
        background: linear-gradient(135deg, #f8fbff 0%, #ffffff 100%);
    }
    .ba-kpi-card.ba-kpi-teal {
        border-left: 4px solid #0d9488;
    }
    .ba-kpi-card.ba-kpi-indigo {
        border-left: 4px solid #4f46e5;
    }
    .ba-kpi-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        margin-bottom: 4px;
    }
    .ba-kpi-value {
        font-size: 20px;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.2;
    }
    .ba-kpi-sub {
        font-size: 11.5px;
        color: #64748b;
        margin-top: 2px;
    }
    .ba-kpi-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }
    .ba-form-card {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05);
        overflow: hidden;
    }
    .ba-form-header {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 14px 20px;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }
    .ba-form-title {
        font-size: 15px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .ba-flow-pill {
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
    .ba-section-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 18px 18px 6px 18px;
        margin-bottom: 16px;
    }
    .ba-field-label {
        font-size: 12.5px;
        font-weight: 600;
        color: #334155;
        margin-bottom: 6px;
        display: block;
    }
    .ba-field-hint {
        font-size: 11.5px;
        color: #64748b;
        margin-top: 5px;
        display: block;
        line-height: 1.35;
    }
    .ba-form-card .form-control {
        width: 100%;
        min-width: 0;
        box-sizing: border-box;
    }
    .ba-status-box {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 7px 12px;
        min-height: 38px;
        display: flex;
        align-items: center;
    }
    .ba-form-footer {
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
                    <h4 class="mb-0">Bank Accounts</h4>
                    <small class="text-muted">Company treasury accounts available for Central Warehouse and Retail Branch deposits, supplier settlements, and transfers.</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    @can('bank-transaction-list')
                        <a href="{{ route('accounting.setup.bank-transactions.index') }}" class="btn-modern-header">
                            <i class="fa fa-university me-1"></i> Bank Transactions
                        </a>
                    @endcan
                    <span class="badge bg-info text-dark px-3 py-2">
                        <i class="fa fa-piggy-bank me-1"></i> Shared Multi-Outlet Treasury
                    </span>
                </div>
            </div>
        </div>

        {{-- Create / Edit Bank Account Card --}}
        <div class="card ba-form-card mb-4">
            <div class="ba-form-header">
                <h5 class="ba-form-title">
                    <span class="badge bg-primary rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width:28px;height:28px;">
                        <i class="fa fa-piggy-bank" style="font-size:12px;"></i>
                    </span>
                    <span id="bank_account_form_title">Create Bank Account</span>
                </h5>
                <div class="ba-flow-pill">
                    <i class="fa fa-book"></i>
                    <span>Opening Balance: Dr Linked Bank GL &nbsp;&rarr;&nbsp; Cr Owner's Equity (3000)</span>
                </div>
            </div>

            <div class="card-body p-3 p-md-4">
                <form id="bank_account_form" method="POST" action="{{ route('accounting.setup.bank-accounts.store') }}" data-create-action="{{ route('accounting.setup.bank-accounts.store') }}" data-update-action-template="{{ route('accounting.setup.bank-accounts.update', '__ID__') }}" novalidate>
                    @csrf
                    <input type="hidden" name="_method" id="bank_account_method" value="POST">

                    <div class="ba-section-box">
                        {{-- Row 1: Internal Account Name, Bank / Institution, Account Number (3 equal columns) --}}
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="ba-field-label" for="bank_internal_name">Account Name (Internal) <span class="text-danger">*</span></label>
                                <input class="form-control" name="name" id="bank_internal_name" placeholder="e.g. Main Operations Account" required>
                                <span class="ba-field-hint">Unique label shown in POS dropdowns and accounting reports.</span>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="ba-field-label" for="bank_institution_name">Bank / Institution <span class="text-danger">*</span></label>
                                <input class="form-control" name="bank_name" id="bank_institution_name" placeholder="e.g. Ecobank or GCB Bank" required>
                                <span class="ba-field-hint">Official name of the financial institution.</span>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="ba-field-label" for="bank_account_number">Account Number <span class="text-danger">*</span></label>
                                <input class="form-control" name="account_number" id="bank_account_number" placeholder="e.g. 1441002398401" required>
                                <span class="ba-field-hint">Exact account number from your bank statement.</span>
                            </div>
                        </div>

                        {{-- Row 2: Linked GL Account, Opening Balance, Currency & Status (3 equal columns) --}}
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="ba-field-label" for="gl_account_id">Linked GL Account <span class="text-danger">*</span></label>
                                <select name="gl_account_id" id="gl_account_id" class="form-control" required>
                                    <option value="">Select GL account...</option>
                                    @foreach($glAccounts as $acc)
                                        <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                                    @endforeach
                                </select>
                                <span class="ba-field-hint">Chart of Accounts ledger where bank transactions post.</span>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="ba-field-label" for="opening_balance">Opening Balance (GHS) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control fw-semibold" id="opening_balance" name="opening_balance" value="0" required>
                                <span class="ba-field-hint" id="opening_balance_hint">Posts opening GL entry to Central Warehouse.</span>
                            </div>

                            <div class="col-md-4 mb-3">
                                <div class="row">
                                    <div class="col-6">
                                        <label class="ba-field-label" for="bank_currency">Currency <span class="text-danger">*</span></label>
                                        <input class="form-control" name="currency" id="bank_currency" value="GHS" required>
                                        <span class="ba-field-hint">Default currency code.</span>
                                    </div>
                                    <div class="col-6">
                                        <label class="ba-field-label" for="active">Account Status</label>
                                        <div class="ba-status-box">
                                            <div class="form-check mb-0">
                                                <input class="form-check-input" type="checkbox" name="is_active" value="1" checked id="active">
                                                <label class="form-check-label fw-semibold" for="active">Active</label>
                                            </div>
                                        </div>
                                        <span class="ba-field-hint">Available for posting.</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="ba-form-footer">
                        <div class="small text-muted">
                            <i class="fa fa-shield-alt text-primary me-1"></i>
                            Active bank accounts are shared across Central Warehouse and all Retail Branches.
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" id="bank_account_cancel_edit" class="btn-modern-cancel d-none">
                                <i class="fa fa-times me-1"></i> Cancel Edit
                            </button>
                            <button id="bank_account_submit" class="btn-modern-primary">
                                <i class="fa fa-check-circle me-1"></i> Save Bank Account
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Bank Accounts Table --}}
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
        const form = document.getElementById('bank_account_form');
        const title = document.getElementById('bank_account_form_title');
        const methodInput = document.getElementById('bank_account_method');
        const submitButton = document.getElementById('bank_account_submit');
        const cancelButton = document.getElementById('bank_account_cancel_edit');
        const openingBalance = document.getElementById('opening_balance');
        const openingBalanceHint = document.getElementById('opening_balance_hint');

        function resetToCreateMode() {
            if (!form) {
                return;
            }

            form.reset();
            form.action = form.dataset.createAction;
            methodInput.value = 'POST';
            title.textContent = 'Create Bank Account';
            submitButton.innerHTML = '<i class="fa fa-check-circle me-1"></i> Save Bank Account';
            cancelButton.classList.add('d-none');
            if (openingBalance) {
                openingBalance.disabled = false;
                openingBalance.value = '0';
            }
            if (openingBalanceHint) {
                openingBalanceHint.textContent = 'Posts opening GL entry to Central Warehouse.';
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
            submitButton.innerHTML = '<i class="fa fa-save me-1"></i> Update Bank Account';
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
            if (openingBalanceHint) {
                openingBalanceHint.textContent = 'Opening balance is locked after creation.';
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
