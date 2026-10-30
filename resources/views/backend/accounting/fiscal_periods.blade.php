@extends('layout.main')
@section('title', 'Fiscal Periods')

@section('content')
<style>
    .fp-form-card {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05);
        overflow: hidden;
    }
    .fp-form-header {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 14px 20px;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }
    .fp-form-title {
        font-size: 15px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .fp-flow-pill {
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
    .fp-section-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 18px 18px 6px 18px;
        margin-bottom: 16px;
    }
    .fp-field-label {
        font-size: 12.5px;
        font-weight: 600;
        color: #334155;
        margin-bottom: 6px;
        display: block;
    }
    .fp-field-hint {
        font-size: 11.5px;
        color: #64748b;
        margin-top: 5px;
        display: block;
        line-height: 1.35;
    }
    .fp-form-card .form-control {
        width: 100%;
        min-width: 0;
        box-sizing: border-box;
    }
    .fp-form-footer {
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
                    <h4 class="mb-0">Fiscal Periods</h4>
                    <small class="text-muted">Company-wide fiscal period cutoffs enforced simultaneously across Central Warehouse and all Retail Branches.</small>
                </div>
                <span class="badge bg-info text-dark px-3 py-2">
                    <i class="fa fa-calendar-alt me-1"></i> Global Multi-Outlet Cutoff
                </span>
            </div>
        </div>

        {{-- Create / Edit Fiscal Period Card --}}
        <div class="card fp-form-card mb-4">
            <div class="fp-form-header">
                <h5 class="fp-form-title">
                    <span class="badge bg-primary rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width:28px;height:28px;">
                        <i class="fa fa-calendar-plus" style="font-size:12px;"></i>
                    </span>
                    <span id="fiscal_period_form_title">Create Fiscal Period</span>
                </h5>
                <div class="fp-flow-pill">
                    <i class="fa fa-lock"></i>
                    <span>Accounting Cutoff: Postings blocked across all outlets once closed</span>
                </div>
            </div>

            <div class="card-body p-3 p-md-4">
                <form id="fiscal_period_form" method="POST" action="{{ route('accounting.setup.fiscal-periods.store') }}" data-create-action="{{ route('accounting.setup.fiscal-periods.store') }}" data-update-action-template="{{ route('accounting.setup.fiscal-periods.update', '__ID__') }}" novalidate>
                    @csrf
                    <input type="hidden" name="_method" id="fiscal_period_method" value="POST">

                    <div class="fp-section-box">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="fp-field-label" for="fp_name">Period Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="fp_name" class="form-control fw-semibold" placeholder="e.g. FY 2026 or Q1 2026" required>
                                <span class="fp-field-hint">Label identifying this financial period on reports.</span>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="fp-field-label" for="fp_start_date">Start Date <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" id="fp_start_date" class="form-control" value="{{ now()->startOfYear()->toDateString() }}" required>
                                <span class="fp-field-hint">First date of this accounting period.</span>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="fp-field-label" for="fp_end_date">End Date <span class="text-danger">*</span></label>
                                <input type="date" name="end_date" id="fp_end_date" class="form-control" value="{{ now()->endOfYear()->toDateString() }}" required>
                                <span class="fp-field-hint">Final date of this accounting period.</span>
                            </div>
                        </div>
                    </div>

                    <div class="fp-form-footer">
                        <div class="small text-muted">
                            <i class="fa fa-shield-alt text-primary me-1"></i>
                            Journal postings with dates falling inside closed fiscal periods are strictly prevented across all outlets.
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" id="fiscal_period_cancel_edit" class="btn-modern-cancel d-none">
                                <i class="fa fa-times me-1"></i> Cancel Edit
                            </button>
                            <button id="fiscal_period_submit" class="btn-modern-primary">
                                <i class="fa fa-check-circle me-1"></i> Save Period
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Fiscal Periods Table --}}
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
        const form = document.getElementById('fiscal_period_form');
        const formTitle = document.getElementById('fiscal_period_form_title');
        const methodInput = document.getElementById('fiscal_period_method');
        const submitButton = document.getElementById('fiscal_period_submit');
        const cancelButton = document.getElementById('fiscal_period_cancel_edit');

        function resetToCreateMode() {
            if (!form) return;
            form.reset();
            form.action = form.dataset.createAction;
            methodInput.value = 'POST';
            formTitle.textContent = 'Create Fiscal Period';
            submitButton.innerHTML = '<i class="fa fa-check-circle me-1"></i> Save Period';
            cancelButton.classList.add('d-none');
        }

        $(document).off('click', '.js-edit-fiscal-period').on('click', '.js-edit-fiscal-period', function () {
            const btn = $(this);
            const id = btn.data('id');
            form.action = form.dataset.updateActionTemplate.replace('__ID__', encodeURIComponent(id));
            methodInput.value = 'PATCH';
            formTitle.textContent = 'Edit Fiscal Period (' + (btn.data('name') || '') + ')';
            submitButton.innerHTML = '<i class="fa fa-save me-1"></i> Update Period';
            cancelButton.classList.remove('d-none');

            form.querySelector('[name="name"]').value = btn.data('name') || '';
            form.querySelector('[name="start_date"]').value = btn.data('start_date') || '';
            form.querySelector('[name="end_date"]').value = btn.data('end_date') || '';

            window.scrollTo({ top: form.offsetTop - 80, behavior: 'smooth' });
        });

        if (cancelButton) {
            cancelButton.addEventListener('click', resetToCreateMode);
        }
    })();
</script>
@endsection

@stack('scripts')
