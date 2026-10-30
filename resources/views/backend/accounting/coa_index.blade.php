@extends('layout.main')
@section('title', 'Chart of Accounts')

@section('content')
<style>
    .coa-form-card {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05);
        overflow: hidden;
    }
    .coa-form-header {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 14px 20px;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }
    .coa-form-title {
        font-size: 15px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .coa-flow-pill {
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
    .coa-section-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 18px 18px 6px 18px;
        margin-bottom: 16px;
    }
    .coa-field-label {
        font-size: 12.5px;
        font-weight: 600;
        color: #334155;
        margin-bottom: 6px;
        display: block;
    }
    .coa-field-hint {
        font-size: 11.5px;
        color: #64748b;
        margin-top: 5px;
        display: block;
        line-height: 1.35;
    }
    .coa-form-card .form-control {
        width: 100%;
        min-width: 0;
        box-sizing: border-box;
    }
    .coa-status-box {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 7px 14px;
        min-height: 38px;
        display: flex;
        align-items: center;
    }
    .coa-form-footer {
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
                    <h4 class="mb-0">Chart of Accounts</h4>
                    <small class="text-muted">Unified master chart of accounts shared across Central Warehouse and all Retail Branches.</small>
                </div>
                <span class="badge bg-info text-dark px-3 py-2">
                    <i class="fa fa-sitemap me-1"></i> Global Multi-Outlet COA
                </span>
            </div>
        </div>

        {{-- Create / Edit Account Card --}}
        <div class="card coa-form-card mb-4">
            <div class="coa-form-header">
                <h5 class="coa-form-title">
                    <span class="badge bg-primary rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width:28px;height:28px;">
                        <i class="fa fa-sitemap" style="font-size:12px;"></i>
                    </span>
                    <span id="coa_form_title">Create Account</span>
                </h5>
                <div class="coa-flow-pill">
                    <i class="fa fa-globe"></i>
                    <span>Master Ledger Scope: Central Warehouse &amp; All Retail Branches</span>
                </div>
            </div>

            <div class="card-body p-3 p-md-4">
                <form id="coa_form" method="POST" action="{{ route('accounting.setup.coa.store') }}" data-create-action="{{ route('accounting.setup.coa.store') }}" data-update-action-template="{{ route('accounting.setup.coa.update', '__ID__') }}" novalidate>
                    @csrf
                    <input type="hidden" name="_method" id="coa_method" value="POST">

                    <div class="coa-section-box">
                        {{-- Row 1: Account Code, Account Name, Account Type (3 equal columns) --}}
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="coa-field-label" for="coa_code">Account Code <span class="text-danger">*</span></label>
                                <input class="form-control fw-semibold" name="code" id="coa_code" placeholder="e.g. 5210" required>
                                <span class="coa-field-hint" id="coa_code_hint">Unique GL code (1xxx Asset, 2xxx Liability, 3xxx Equity, 4xxx Income, 5xxx Expense).</span>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="coa-field-label" for="coa_name">Account Name <span class="text-danger">*</span></label>
                                <input class="form-control" name="name" id="coa_name" placeholder="e.g. Store Utilities Expense" required>
                                <span class="coa-field-hint">Descriptive ledger title displayed on financial statements.</span>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="coa-field-label" for="coa_type">Account Type <span class="text-danger">*</span></label>
                                <select class="form-control" name="type" id="coa_type" required>
                                    <option value="asset">Asset (Balance Sheet)</option>
                                    <option value="liability">Liability (Balance Sheet)</option>
                                    <option value="equity">Equity (Balance Sheet)</option>
                                    <option value="income">Income / Revenue (Income Statement)</option>
                                    <option value="expense">Expense (Income Statement)</option>
                                </select>
                                <span class="coa-field-hint" id="coa_type_hint">Determines normal balance and financial statement placement.</span>
                            </div>
                        </div>

                        {{-- Row 2: Bank Account GL Flag & Account Status --}}
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="coa-field-label" for="is_bank_account">Bank Account GL Flag</label>
                                <div class="coa-status-box">
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="checkbox" name="is_bank_account" value="1" id="is_bank_account">
                                        <label class="form-check-label fw-semibold" for="is_bank_account">Is Bank Account GL</label>
                                    </div>
                                </div>
                                <span class="coa-field-hint">Enable if this Asset account links to a company Bank Account under Treasury Setup.</span>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="coa-field-label" for="is_active">Account Status</label>
                                <div class="coa-status-box">
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" checked>
                                        <label class="form-check-label fw-semibold" for="is_active">Active</label>
                                    </div>
                                </div>
                                <span class="coa-field-hint">Active accounts are available for journal postings across all outlets.</span>
                            </div>
                        </div>
                    </div>

                    <div class="coa-form-footer">
                        <div class="small text-muted">
                            <i class="fa fa-shield-alt text-primary me-1"></i>
                            All outlets post into this unified Chart of Accounts while tagging each journal with its Outlet ID.
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" id="coa_cancel_edit" class="btn-modern-cancel d-none">
                                <i class="fa fa-times me-1"></i> Cancel Edit
                            </button>
                            <button id="coa_submit" class="btn-modern-primary">
                                <i class="fa fa-check-circle me-1"></i> Save Account
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Chart of Accounts Table --}}
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
        const form = document.getElementById('coa_form');
        const title = document.getElementById('coa_form_title');
        const methodInput = document.getElementById('coa_method');
        const submitButton = document.getElementById('coa_submit');
        const cancelButton = document.getElementById('coa_cancel_edit');
        const codeInput = document.getElementById('coa_code');
        const typeSelect = document.getElementById('coa_type');
        const codeHint = document.getElementById('coa_code_hint');
        const typeHint = document.getElementById('coa_type_hint');

        function resetToCreateMode() {
            if (!form) {
                return;
            }

            form.reset();
            form.action = form.dataset.createAction;
            methodInput.value = 'POST';
            title.textContent = 'Create Account';
            submitButton.innerHTML = '<i class="fa fa-check-circle me-1"></i> Save Account';
            cancelButton.classList.add('d-none');

            if (codeInput) {
                codeInput.disabled = false;
            }
            if (typeSelect) {
                typeSelect.disabled = false;
            }
            if (codeHint) {
                codeHint.textContent = 'Unique GL code (1xxx Asset, 2xxx Liability, 3xxx Equity, 4xxx Income, 5xxx Expense).';
            }
            if (typeHint) {
                typeHint.textContent = 'Determines normal balance and financial statement placement.';
            }

            const active = document.getElementById('is_active');
            if (active) {
                active.checked = true;
            }
        }

        $(document).off('click', '.js-edit-coa').on('click', '.js-edit-coa', function () {
            const btn = $(this);
            const id = btn.data('id');
            form.action = form.dataset.updateActionTemplate.replace('__ID__', encodeURIComponent(id));
            methodInput.value = 'PATCH';
            title.textContent = 'Edit Account (' + (btn.data('code') || '') + ')';
            submitButton.innerHTML = '<i class="fa fa-save me-1"></i> Update Account';
            cancelButton.classList.remove('d-none');

            if (codeInput) {
                codeInput.value = btn.data('code') || '';
                codeInput.disabled = true;
            }
            if (typeSelect) {
                typeSelect.value = btn.data('type') || 'asset';
                typeSelect.disabled = true;
            }
            if (codeHint) {
                codeHint.textContent = 'Account code is locked after creation.';
            }
            if (typeHint) {
                typeHint.textContent = 'Account type is locked after creation.';
            }

            form.querySelector('[name="name"]').value = btn.data('name') || '';

            const isBank = document.getElementById('is_bank_account');
            if (isBank) {
                isBank.checked = Number(btn.data('is_bank_account') || 0) === 1;
            }

            const isActive = document.getElementById('is_active');
            if (isActive) {
                isActive.checked = Number(btn.data('is_active') || 0) === 1;
            }

            window.scrollTo({ top: form.offsetTop - 80, behavior: 'smooth' });
        });

        if (cancelButton) {
            cancelButton.addEventListener('click', resetToCreateMode);
        }
    })();
</script>
@endsection

@stack('scripts')
