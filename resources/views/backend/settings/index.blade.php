@extends('layout.main')
@section('title') {{ 'System Settings' }} @endsection

@section('content')
<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Enterprise System Settings</h4>
                </div>
            </div>
        </div>
        <!-- end page title -->

        <div class="row">
            <div class="col-12 col-xl-11 mx-auto">
                
                {{-- Master Governance Banner --}}
                <div class="alert alert-info d-flex align-items-center mb-4">
                    <i class="fas fa-sliders-h fa-2x mr-3 me-3 text-info"></i>
                    <div>
                        <strong class="d-block font-size-15">Enterprise-Wide Governance:</strong>
                        <span class="text-muted">These global policies govern all retail branch outlets, POS terminals, Central Warehouse logistics, and financial reconciliations across the enterprise. Only Central Headquarters staff can modify these rules.</span>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show mb-4">
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        <h6 class="alert-heading mb-1"><i class="fas fa-exclamation-triangle me-1"></i> Please correct the following errors:</h6>
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('settings.update') }}" method="POST">
                    @csrf
                    @method('PATCH')

                    {{-- Section 1: Branch POS & Retail Controls --}}
                    <div class="card settings-section-card mb-4">
                        <div class="card-header bg-transparent border-bottom py-3 d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <span class="badge-section-icon me-2"><i class="fas fa-cash-register"></i></span>
                                <h5 class="card-title mb-0 font-size-16">Branch POS & Retail Sales Controls</h5>
                            </div>
                            <span class="badge bg-light text-muted border">Affects All Retail Stations</span>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <!-- Enforce Previous Day Reconciliation -->
                                <div class="col-md-6">
                                    <div class="form-group border rounded p-3 h-100 setting-box">
                                        <input type="hidden" name="enforce_previous_day_reconciliation" value="0">
                                        <div class="custom-control custom-switch">
                                            <input
                                                type="checkbox"
                                                class="custom-control-input"
                                                id="enforce_previous_day_reconciliation"
                                                name="enforce_previous_day_reconciliation"
                                                value="1"
                                                {{ old('enforce_previous_day_reconciliation', $enforcePreviousDayReconciliation) ? 'checked' : '' }}
                                            >
                                            <label class="custom-control-label fw-bold" for="enforce_previous_day_reconciliation">
                                                Require Yesterday Reconciliation Before Today's Sales
                                            </label>
                                        </div>
                                        <small class="text-muted d-block mt-2">
                                            When enabled, cashiers across all branch outlets must submit and balance yesterday's cash reconciliation before opening POS checkout today.
                                        </small>
                                    </div>
                                </div>

                                <!-- Allow Negative Stock -->
                                <div class="col-md-6">
                                    <div class="form-group border rounded p-3 h-100 setting-box">
                                        <input type="hidden" name="allow_negative_stock" value="0">
                                        <div class="custom-control custom-switch">
                                            <input
                                                type="checkbox"
                                                class="custom-control-input"
                                                id="allow_negative_stock"
                                                name="allow_negative_stock"
                                                value="1"
                                                {{ old('allow_negative_stock', $allowNegativeStock) ? 'checked' : '' }}
                                            >
                                            <label class="custom-control-label fw-bold" for="allow_negative_stock">
                                                Allow Negative Stock (Overselling at Outlets)
                                            </label>
                                        </div>
                                        <small class="text-muted d-block mt-2">
                                            When enabled, sales continue even when branch physical inventory is depleted. When disabled, POS sales are blocked if branch station stock is zero.
                                        </small>
                                    </div>
                                </div>

                                <!-- Prevent Backdated Invoices -->
                                <div class="col-md-6">
                                    <div class="form-group border rounded p-3 h-100 setting-box">
                                        <input type="hidden" name="prevent_backdated_invoices" value="0">
                                        <div class="custom-control custom-switch">
                                            <input
                                                type="checkbox"
                                                class="custom-control-input"
                                                id="prevent_backdated_invoices"
                                                name="prevent_backdated_invoices"
                                                value="1"
                                                {{ old('prevent_backdated_invoices', $preventBackdatedInvoices) ? 'checked' : '' }}
                                            >
                                            <label class="custom-control-label fw-bold" for="prevent_backdated_invoices">
                                                Prevent Backdated Invoices
                                            </label>
                                        </div>
                                        <small class="text-muted d-block mt-2">
                                            When enabled, users across all branch outlets are restricted to creating sales with today's date only, preserving timeline audit integrity.
                                        </small>
                                    </div>
                                </div>

                                <!-- Allow Manual Discount Entry by Staff -->
                                <div class="col-md-6">
                                    <div class="form-group border rounded p-3 h-100 setting-box">
                                        <input type="hidden" name="allow_manual_discount_entry_by_staff" value="0">
                                        <div class="custom-control custom-switch">
                                            <input
                                                type="checkbox"
                                                class="custom-control-input"
                                                id="allow_manual_discount_entry_by_staff"
                                                name="allow_manual_discount_entry_by_staff"
                                                value="1"
                                                {{ old('allow_manual_discount_entry_by_staff', $allowManualDiscountEntryByStaff) ? 'checked' : '' }}
                                            >
                                            <label class="custom-control-label fw-bold" for="allow_manual_discount_entry_by_staff">
                                                Allow Manual Discounts by Checkout Staff
                                            </label>
                                        </div>
                                        <small class="text-muted d-block mt-2">
                                            When disabled, the discount input on branch POS screens is locked to 0 to strictly enforce central product prices.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Section 2: Procurement & Inventory Rules --}}
                    <div class="card settings-section-card mb-4">
                        <div class="card-header bg-transparent border-bottom py-3 d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <span class="badge-section-icon me-2"><i class="fas fa-truck-loading"></i></span>
                                <h5 class="card-title mb-0 font-size-16">Procurement & Supplier Purchase Rules</h5>
                            </div>
                            <span class="badge bg-light text-muted border">Central Warehouse Policy</span>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <!-- Enable Purchase Editing -->
                                <div class="col-md-6">
                                    <div class="form-group border rounded p-3 h-100 setting-box">
                                        <input type="hidden" name="purchase_edit_enabled" value="0">
                                        <div class="custom-control custom-switch">
                                            <input
                                                type="checkbox"
                                                class="custom-control-input"
                                                id="purchase_edit_enabled"
                                                name="purchase_edit_enabled"
                                                value="1"
                                                onchange="togglePurchaseEditWindow()"
                                                {{ old('purchase_edit_enabled', $purchaseEditEnabled) ? 'checked' : '' }}
                                            >
                                            <label class="custom-control-label fw-bold" for="purchase_edit_enabled">
                                                Enable Supplier Purchase Editing
                                            </label>
                                        </div>
                                        <small class="text-muted d-block mt-2">
                                            When enabled, Central Warehouse staff with edit permissions can modify posted supplier purchases within the authorized grace period.
                                        </small>
                                    </div>
                                </div>

                                <!-- Purchase Edit Window Settings -->
                                <div class="col-md-6" id="purchaseEditWindowRow" style="{{ old('purchase_edit_enabled', $purchaseEditEnabled) ? '' : 'display:none;' }}">
                                    <div class="form-group border rounded p-3 h-100 setting-box">
                                        <label for="purchase_edit_window_hours" class="fw-bold">
                                            Purchase Edit Window (Hours) <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            type="number"
                                            id="purchase_edit_window_hours"
                                            name="purchase_edit_window_hours"
                                            class="form-control"
                                            min="24"
                                            max="720"
                                            value="{{ old('purchase_edit_window_hours', $purchaseEditWindowHours) }}"
                                            {{ old('purchase_edit_enabled', $purchaseEditEnabled) ? '' : 'disabled' }}
                                        >
                                        <small class="text-muted d-block mt-2">
                                            Allowed range: 24 hours (1 day) to 720 hours (30 days) from purchase creation time.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Section 3: Automated Financial Reporting --}}
                    <div class="card settings-section-card mb-4">
                        <div class="card-header bg-transparent border-bottom py-3 d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <span class="badge-section-icon me-2"><i class="fas fa-envelope-open-text"></i></span>
                                <h5 class="card-title mb-0 font-size-16">Automated Financial Reporting</h5>
                            </div>
                            <span class="badge bg-light text-muted border">Enterprise Aggregation</span>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <!-- Enable Daily Email Report -->
                                <div class="col-md-6">
                                    <div class="form-group border rounded p-3 h-100 setting-box">
                                        <input type="hidden" name="enable_daily_email_report" value="0">
                                        <div class="custom-control custom-switch">
                                            <input
                                                type="checkbox"
                                                class="custom-control-input"
                                                id="enable_daily_email_report"
                                                name="enable_daily_email_report"
                                                value="1"
                                                onchange="toggleEmailTimeField()"
                                                {{ old('enable_daily_email_report', $enableDailyEmailReport) ? 'checked' : '' }}
                                            >
                                            <label class="custom-control-label fw-bold" for="enable_daily_email_report">
                                                Enable Daily Transaction Email Report
                                            </label>
                                        </div>
                                        <small class="text-muted d-block mt-2">
                                            Sends an automated end-of-day summary aggregating sales, expenses, and cash shortage variances across all active branch outlets to company management.
                                        </small>
                                    </div>
                                </div>

                                <!-- Email Report Time Settings -->
                                <div class="col-md-6" id="emailTimeRow" style="{{ old('enable_daily_email_report', $enableDailyEmailReport) ? '' : 'display:none;' }}">
                                    <div class="form-group border rounded p-3 h-100 setting-box">
                                        <label for="daily_email_report_time" class="fw-bold">
                                            Daily Email Report Send Time <span class="text-danger">*</span>
                                        </label>
                                        <select
                                            id="daily_email_report_time"
                                            name="daily_email_report_time"
                                            class="form-control"
                                            {{ old('enable_daily_email_report', $enableDailyEmailReport) ? '' : 'disabled' }}
                                        >
                                            @for ($hour = 18; $hour <= 23; $hour++)
                                                <option value="{{ $hour }}" {{ old('daily_email_report_time', $dailyEmailReportTime) == $hour ? 'selected' : '' }}>
                                                    {{ str_pad($hour, 2, '0', STR_PAD_LEFT) }}:00 ({{ $hour == 18 ? '6:00 PM' : ($hour == 23 ? '11:00 PM' : ($hour - 12) . ':00 PM') }})
                                                </option>
                                            @endfor
                                        </select>
                                        <small class="text-muted d-block mt-2">
                                            Select scheduled dispatch hour (between 6:00 PM and 11:00 PM) after retail stations close.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Section 4: System Security & Workstation Sessions --}}
                    <div class="card settings-section-card mb-4">
                        <div class="card-header bg-transparent border-bottom py-3 d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <span class="badge-section-icon me-2"><i class="fas fa-shield-alt"></i></span>
                                <h5 class="card-title mb-0 font-size-16">System Security & Workstation Sessions</h5>
                            </div>
                            <span class="badge bg-light text-muted border">All Devices & Stations</span>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <!-- Session Lifetime -->
                                <div class="col-md-4">
                                    <div class="form-group border rounded p-3 h-100 setting-box">
                                        <label for="session_lifetime_minutes" class="fw-bold">
                                            Session Inactivity Timeout (minutes) <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            type="number"
                                            id="session_lifetime_minutes"
                                            name="session_lifetime_minutes"
                                            min="1"
                                            max="1440"
                                            class="form-control mt-1"
                                            value="{{ old('session_lifetime_minutes', $sessionLifetimeMinutes) }}"
                                            required
                                        >
                                        <small class="text-muted d-block mt-2">Users are automatically signed out after this duration of inactivity.</small>
                                    </div>
                                </div>

                                <!-- Session Expire on Close -->
                                <div class="col-md-4">
                                    <div class="form-group border rounded p-3 h-100 setting-box">
                                        <input type="hidden" name="session_expire_on_close" value="0">
                                        <div class="custom-control custom-switch">
                                            <input
                                                type="checkbox"
                                                class="custom-control-input"
                                                id="session_expire_on_close"
                                                name="session_expire_on_close"
                                                value="1"
                                                {{ old('session_expire_on_close', $sessionExpireOnClose) ? 'checked' : '' }}
                                            >
                                            <label class="custom-control-label fw-bold" for="session_expire_on_close">
                                                Expire Session On Browser Close
                                            </label>
                                        </div>
                                        <small class="text-muted d-block mt-2">When enabled, closing the browser immediately terminates the authenticated session.</small>
                                    </div>
                                </div>

                                <!-- Require Password Change on First Login -->
                                <div class="col-md-4">
                                    <div class="form-group border rounded p-3 h-100 setting-box">
                                        <input type="hidden" name="require_password_change_on_first_login" value="0">
                                        <div class="custom-control custom-switch">
                                            <input
                                                type="checkbox"
                                                class="custom-control-input"
                                                id="require_password_change_on_first_login"
                                                name="require_password_change_on_first_login"
                                                value="1"
                                                {{ old('require_password_change_on_first_login', $requirePasswordChangeOnFirstLogin) ? 'checked' : '' }}
                                            >
                                            <label class="custom-control-label fw-bold" for="require_password_change_on_first_login">
                                                Force Password Change on First Login
                                            </label>
                                        </div>
                                        <small class="text-muted d-block mt-2">New staff members must set a personal password upon initial system sign-in.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Submit Button --}}
                    <div class="row mb-5">
                        <div class="col-12 text-center">
                            <button type="submit" class="btn btn-primary px-5 py-2 font-size-15 shadow-sm" style="background-color: #0F766E; border-color: #0F766E;">
                                <i class="fas fa-save me-1"></i> Save Enterprise Settings
                            </button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

@endsection

@section('styles')
<style>
    .settings-section-card {
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
        transition: all 0.2s ease-in-out;
    }
    .settings-section-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }
    .badge-section-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background-color: #E6FFFA;
        color: #0F766E;
        font-size: 15px;
    }
    .setting-box {
        background-color: #ffffff;
        transition: border-color 0.2s ease;
    }
    .setting-box:hover {
        border-color: #0F766E !important;
    }
    .custom-control-input:checked ~ .custom-control-label::before {
        border-color: #0F766E !important;
        background-color: #0F766E !important;
    }
    .custom-control-input:focus ~ .custom-control-label::before {
        box-shadow: 0 0 0 0.2rem rgba(15, 118, 110, 0.25) !important;
    }
    .form-control:focus {
        border-color: #0F766E !important;
        box-shadow: 0 0 0 0.2rem rgba(15, 118, 110, 0.2) !important;
    }
</style>
@endsection

@section('scripts')
<script>
function toggleEmailTimeField() {
    const checkbox = document.getElementById('enable_daily_email_report');
    const timeRow = document.getElementById('emailTimeRow');
    const timeSelect = document.getElementById('daily_email_report_time');
    
    if (checkbox.checked) {
        timeRow.style.display = '';
        timeSelect.disabled = false;
    } else {
        timeRow.style.display = 'none';
        timeSelect.disabled = true;
    }
}

function togglePurchaseEditWindow() {
    const checkbox = document.getElementById('purchase_edit_enabled');
    const row = document.getElementById('purchaseEditWindowRow');
    const input = document.getElementById('purchase_edit_window_hours');

    if (checkbox.checked) {
        row.style.display = '';
        input.disabled = false;
    } else {
        row.style.display = 'none';
        input.disabled = true;
    }
}
</script>
@endsection
