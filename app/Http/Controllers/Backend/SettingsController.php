<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:settings-manage', ['only' => ['index', 'update']]);
    }

    public function index()
    {
        return view('backend.settings.index', [
            'sessionLifetimeMinutes' => $this->getIntSetting('session_lifetime_minutes', (int) config('session.lifetime', 120)),
            'sessionExpireOnClose' => $this->getBoolSetting('session_expire_on_close', (bool) config('session.expire_on_close', false)),
            'enforcePreviousDayReconciliation' => $this->getBoolSetting(
                'enforce_previous_day_reconciliation',
                (bool) config('pos.enforce_previous_day_reconciliation', false)
            ),
            'allowNegativeStock' => $this->getBoolSetting(
                'allow_negative_stock',
                (bool) config('pos.allow_negative_stock', false)
            ),
            'preventBackdatedInvoices' => $this->getBoolSetting(
                'prevent_backdated_invoices',
                (bool) config('pos.prevent_backdated_invoices', false)
            ),
            'allowManualDiscountEntryByStaff' => $this->getBoolSetting(
                'allow_manual_discount_entry_by_staff',
                (bool) config('pos.allow_manual_discount_entry_by_staff', true)
            ),
            'requirePasswordChangeOnFirstLogin' => $this->getBoolSetting(
                'require_password_change_on_first_login',
                (bool) config('pos.require_password_change_on_first_login', false)
            ),
            'enableDailyEmailReport' => $this->getBoolSetting(
                'enable_daily_email_report',
                (bool) config('pos.enable_daily_email_report', false)
            ),
            'dailyEmailReportTime' => $this->getIntSetting(
                'daily_email_report_time',
                (int) config('pos.daily_email_report_time', 18)
            ),
            'purchaseEditEnabled' => $this->getBoolSetting(
                'purchase_edit_enabled',
                (bool) config('pos.purchase_edit_enabled', false)
            ),
            'purchaseEditWindowHours' => $this->getIntSetting(
                'purchase_edit_window_hours',
                (int) config('pos.purchase_edit_window_hours', 24)
            ),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'session_lifetime_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'session_expire_on_close' => ['nullable', 'boolean'],
            'enforce_previous_day_reconciliation' => ['nullable', 'boolean'],
            'allow_negative_stock' => ['nullable', 'boolean'],
            'prevent_backdated_invoices' => ['nullable', 'boolean'],
            'allow_manual_discount_entry_by_staff' => ['nullable', 'boolean'],
            'require_password_change_on_first_login' => ['nullable', 'boolean'],
            'enable_daily_email_report' => ['nullable', 'boolean'],
            'daily_email_report_time' => ['required_if:enable_daily_email_report,true', 'integer', 'min:18', 'max:23'],
            'purchase_edit_enabled' => ['nullable', 'boolean'],
            'purchase_edit_window_hours' => ['required_if:purchase_edit_enabled,true', 'integer', 'min:24', 'max:720'],
        ]);

        $values = [
            'session_lifetime_minutes' => (string) $validated['session_lifetime_minutes'],
            'session_expire_on_close' => $request->boolean('session_expire_on_close') ? '1' : '0',
            'enforce_previous_day_reconciliation' => $request->boolean('enforce_previous_day_reconciliation') ? '1' : '0',
            'allow_negative_stock' => $request->boolean('allow_negative_stock') ? '1' : '0',
            'prevent_backdated_invoices' => $request->boolean('prevent_backdated_invoices') ? '1' : '0',
            'allow_manual_discount_entry_by_staff' => $request->boolean('allow_manual_discount_entry_by_staff') ? '1' : '0',
            'require_password_change_on_first_login' => $request->boolean('require_password_change_on_first_login') ? '1' : '0',
            'enable_daily_email_report' => $request->boolean('enable_daily_email_report') ? '1' : '0',
            'daily_email_report_time' => (string) ($validated['daily_email_report_time'] ?? 18),
            'purchase_edit_enabled' => $request->boolean('purchase_edit_enabled') ? '1' : '0',
            'purchase_edit_window_hours' => (string) ($validated['purchase_edit_window_hours'] ?? 24),
        ];

        DB::transaction(function () use ($values) {
            foreach ($values as $key => $value) {
                AppSetting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
            }
        });

        Cache::forget('runtime_app_settings');

        return redirect()->route('settings.index')->with([
            'message' => 'Settings updated successfully.',
            'alert-type' => 'success',
        ]);
    }

    private function getIntSetting(string $key, int $default): int
    {
        $raw = AppSetting::getValue($key);
        if ($raw === null || !is_numeric($raw)) {
            return $default;
        }

        return (int) $raw;
    }

    private function getBoolSetting(string $key, bool $default): bool
    {
        $raw = AppSetting::getValue($key);
        if ($raw === null) {
            return $default;
        }

        return filter_var($raw, FILTER_VALIDATE_BOOLEAN);
    }
}
