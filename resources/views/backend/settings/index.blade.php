@extends('layout.main')
@section('title') {{ 'Settings' }} @endsection

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header with-border">
                        <span class="card-title" style="font-size:20px;">System Settings</span>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('settings.update') }}" method="POST">
                            @csrf
                            @method('PATCH')

                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <!-- Session Lifetime - Full Width -->
                            <div class="row mb-3">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="session_lifetime_minutes"><strong>Session Lifetime (minutes)</strong></label>
                                        <input
                                            type="number"
                                            id="session_lifetime_minutes"
                                            name="session_lifetime_minutes"
                                            min="1"
                                            max="1440"
                                            class="form-control"
                                            value="{{ old('session_lifetime_minutes', $sessionLifetimeMinutes) }}"
                                            required
                                        >
                                        <small class="text-muted">Users are logged out after this many minutes of inactivity.</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Toggles - 2 Columns per Row -->
                            <div class="row">
                                <!-- Session Expire on Close -->
                                <div class="col-md-6 mb-3">
                                    <div class="form-group border rounded p-3 h-100">
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
                                            <label class="custom-control-label" for="session_expire_on_close">
                                                <strong>Expire Session When Browser Closes</strong>
                                            </label>
                                        </div>
                                        <small class="text-muted d-block mt-2">When enabled, login ends when the browser is closed.</small>
                                    </div>
                                </div>

                                <!-- Enforce Previous Day Reconciliation -->
                                <div class="col-md-6 mb-3">
                                    <div class="form-group border rounded p-3 h-100">
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
                                            <label class="custom-control-label" for="enforce_previous_day_reconciliation">
                                                <strong>Require Yesterday Reconciliation Before Today's Sales</strong>
                                            </label>
                                        </div>
                                        <small class="text-muted d-block mt-2">When enabled, a cashier who sold yesterday must reconcile yesterday before selling today.</small>
                                    </div>
                                </div>

                                <!-- Allow Negative Stock -->
                                <div class="col-md-6 mb-3">
                                    <div class="form-group border rounded p-3 h-100">
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
                                            <label class="custom-control-label" for="allow_negative_stock">
                                                <strong>Allow Negative Stock (Overselling)</strong>
                                            </label>
                                        </div>
                                        <small class="text-muted d-block mt-2">When enabled, sales can continue even when available stock is not enough.</small>
                                    </div>
                                </div>

                                <!-- Prevent Backdated Invoices -->
                                <div class="col-md-6 mb-3">
                                    <div class="form-group border rounded p-3 h-100">
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
                                            <label class="custom-control-label" for="prevent_backdated_invoices">
                                                <strong>Prevent Backdated Invoices</strong>
                                            </label>
                                        </div>
                                        <small class="text-muted d-block mt-2">When enabled, users can only create invoices with today\'s date.</small>
                                    </div>
                                </div>

                                <!-- Allow Manual Discount Entry by Staff -->
                                <div class="col-md-6 mb-3">
                                    <div class="form-group border rounded p-3 h-100">
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
                                            <label class="custom-control-label" for="allow_manual_discount_entry_by_staff">
                                                <strong>Allow Manual Discount Entry by Staff</strong>
                                            </label>
                                        </div>
                                        <small class="text-muted d-block mt-2">When disabled, discount input is locked and treated as 0 during checkout.</small>
                                    </div>
                                </div>

                                <!-- Require Password Change on First Login -->
                                <div class="col-md-6 mb-3">
                                    <div class="form-group border rounded p-3 h-100">
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
                                            <label class="custom-control-label" for="require_password_change_on_first_login">
                                                <strong>Require Password Change on First Login</strong>
                                            </label>
                                        </div>
                                        <small class="text-muted d-block mt-2">When enabled, users flagged for first login must change password before using the system.</small>
                                    </div>
                                </div>

                                <!-- Enable Daily Email Report -->
                                <div class="col-md-6 mb-3">
                                    <div class="form-group border rounded p-3 h-100">
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
                                            <label class="custom-control-label" for="enable_daily_email_report">
                                                <strong>Enable Daily Transaction Email Report</strong>
                                            </label>
                                        </div>
                                        <small class="text-muted d-block mt-2">When enabled, daily sales, expenses, and cash shortage summary is sent automatically or manually.</small>
                                    </div>
                                </div>

                                <!-- Enable Purchase Editing -->
                                <div class="col-md-6 mb-3">
                                    <div class="form-group border rounded p-3 h-100">
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
                                            <label class="custom-control-label" for="purchase_edit_enabled">
                                                <strong>Enable Purchase Editing</strong>
                                            </label>
                                        </div>
                                        <small class="text-muted d-block mt-2">When enabled, users with purchase-edit permission can edit posted purchases within the configured window.</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Email Report Time Settings - Full Width -->
                            <div class="row" id="emailTimeRow" style="{{ old('enable_daily_email_report', $enableDailyEmailReport) ? '' : 'display:none;' }}">
                                <div class="col-md-6 mb-3">
                                    <div class="form-group">
                                        <label for="daily_email_report_time"><strong>Daily Email Report Send Time</strong></label>
                                        <select
                                            id="daily_email_report_time"
                                            name="daily_email_report_time"
                                            class="form-control"
                                            {{ old('enable_daily_email_report', $enableDailyEmailReport) ? '' : 'disabled' }}
                                        >
                                            @for ($hour = 18; $hour <= 23; $hour++)
                                                <option value="{{ $hour }}" {{ old('daily_email_report_time', $dailyEmailReportTime) == $hour ? 'selected' : '' }}>
                                                    {{ str_pad($hour, 2, '0', STR_PAD_LEFT) }}:00 ({{ $hour == 18 ? '6 PM' : ($hour == 23 ? '11 PM' : ($hour - 12) . ' PM') }})
                                                </option>
                                            @endfor
                                        </select>
                                        <small class="text-muted">Select the time between 6 PM and 12 AM for automatic email sending.</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Purchase Edit Window Settings -->
                            <div class="row" id="purchaseEditWindowRow" style="{{ old('purchase_edit_enabled', $purchaseEditEnabled) ? '' : 'display:none;' }}">
                                <div class="col-md-6 mb-3">
                                    <div class="form-group">
                                        <label for="purchase_edit_window_hours"><strong>Purchase Edit Window (Hours)</strong></label>
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
                                        <small class="text-muted">Allowed range is 24 hours (1 day) up to 720 hours (30 days).</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                            <div class="col-12">
                                <button type="submit" class="btn btn-success">Save Settings</button>
                            </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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
