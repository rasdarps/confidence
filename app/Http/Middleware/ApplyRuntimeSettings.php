<?php

namespace App\Http\Middleware;

use App\Models\AppSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class ApplyRuntimeSettings
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            if (Schema::hasTable('app_settings')) {
                $settings = Cache::remember('runtime_app_settings', 60, function () {
                    return AppSetting::query()
                        ->whereIn('key', [
                            'session_lifetime_minutes',
                            'session_expire_on_close',
                            'enforce_previous_day_reconciliation',
                            'allow_negative_stock',
                            'prevent_backdated_invoices',
                            'allow_manual_discount_entry_by_staff',
                            'require_password_change_on_first_login',
                            'enable_daily_email_report',
                            'daily_email_report_time',
                            'purchase_edit_enabled',
                            'purchase_edit_window_hours',
                        ])
                        ->pluck('value', 'key')
                        ->toArray();
                });

                if (isset($settings['session_lifetime_minutes']) && is_numeric($settings['session_lifetime_minutes'])) {
                    $minutes = (int) $settings['session_lifetime_minutes'];
                    config(['session.lifetime' => max(1, min(1440, $minutes))]);
                }

                if (array_key_exists('session_expire_on_close', $settings)) {
                    config([
                        'session.expire_on_close' => filter_var($settings['session_expire_on_close'], FILTER_VALIDATE_BOOLEAN),
                    ]);
                }

                if (array_key_exists('enforce_previous_day_reconciliation', $settings)) {
                    config([
                        'pos.enforce_previous_day_reconciliation' => filter_var($settings['enforce_previous_day_reconciliation'], FILTER_VALIDATE_BOOLEAN),
                    ]);
                }

                if (array_key_exists('allow_negative_stock', $settings)) {
                    config([
                        'pos.allow_negative_stock' => filter_var($settings['allow_negative_stock'], FILTER_VALIDATE_BOOLEAN),
                    ]);
                }

                if (array_key_exists('prevent_backdated_invoices', $settings)) {
                    config([
                        'pos.prevent_backdated_invoices' => filter_var($settings['prevent_backdated_invoices'], FILTER_VALIDATE_BOOLEAN),
                    ]);
                }

                if (array_key_exists('allow_manual_discount_entry_by_staff', $settings)) {
                    config([
                        'pos.allow_manual_discount_entry_by_staff' => filter_var($settings['allow_manual_discount_entry_by_staff'], FILTER_VALIDATE_BOOLEAN),
                    ]);
                }

                if (array_key_exists('require_password_change_on_first_login', $settings)) {
                    config([
                        'pos.require_password_change_on_first_login' => filter_var($settings['require_password_change_on_first_login'], FILTER_VALIDATE_BOOLEAN),
                    ]);
                }

                if (array_key_exists('enable_daily_email_report', $settings)) {
                    config([
                        'pos.enable_daily_email_report' => filter_var($settings['enable_daily_email_report'], FILTER_VALIDATE_BOOLEAN),
                    ]);
                }

                if (array_key_exists('daily_email_report_time', $settings)) {
                    config([
                        'pos.daily_email_report_time' => (int) $settings['daily_email_report_time'],
                    ]);
                }

                if (array_key_exists('purchase_edit_enabled', $settings)) {
                    config([
                        'pos.purchase_edit_enabled' => filter_var($settings['purchase_edit_enabled'], FILTER_VALIDATE_BOOLEAN),
                    ]);
                }

                if (array_key_exists('purchase_edit_window_hours', $settings) && is_numeric($settings['purchase_edit_window_hours'])) {
                    $windowHours = (int) $settings['purchase_edit_window_hours'];
                    config([
                        'pos.purchase_edit_window_hours' => max(24, min(720, $windowHours)),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // Use static config when settings table is unavailable or request is during bootstrap/migration.
        }

        return $next($request);
    }
}
