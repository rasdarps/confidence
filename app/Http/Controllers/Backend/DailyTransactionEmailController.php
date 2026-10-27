<?php

namespace App\Http\Controllers\Backend;

use App\DataTables\Admin\Settings\DailyTransactionEmailDataTable;
use App\Http\Controllers\Controller;
use App\Mail\DailyTransactionMail;
use App\Models\AppSetting;
use App\Models\Company;
use App\Models\DailyTransactionEmail;
use App\Models\ExpenseVoucherItem;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DailyTransactionEmailController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->only(['index', 'logs', 'sendManual', 'getStatus']);
        $this->middleware('permission:settings-list|settings-edit|daily-transaction-email-send')->only(['index', 'logs', 'getStatus']);
        $this->middleware('permission:daily-transaction-email-send', ['only' => ['sendManual']]);
    }

     public function index()
    {
        return view('backend.settings.daily_transaction_report');
    }

    public function logs(DailyTransactionEmailDataTable $dataTable)
    {
        return $dataTable->render('backend.settings.daily_transaction_email_logs');
    }

    /**
     * Send daily transaction email manually
     */
    public function sendManual()
    {
        // Check if feature is enabled
        if (!$this->isEmailFeatureEnabled()) {
            return response()->json([
                'status' => 400,
                'message' => 'Daily transaction email feature is not enabled.',
            ]);
        }

        try {
            $company = Company::first();
            if (!$company) {
                return response()->json([
                    'status' => 400,
                    'message' => 'No company found.',
                ]);
            }

            // Check if already sent today
            if (DailyTransactionEmail::alreadySentToday($company->id)) {
                $lastSent = DailyTransactionEmail::lastSent($company->id);
                return response()->json([
                    'status' => 400,
                    'message' => 'Email already sent today at ' . $lastSent->sent_at->format('H:i:s') . '. Cannot send duplicate.',
                ]);
            }

            // Calculate totals
            $businessDate = today();
            $totals = $this->calculateDailyTotals($businessDate);

            // Create email record
            $emailRecord = DailyTransactionEmail::create([
                'company_id' => $company->id,
                'business_date' => $businessDate,
                'sales_total' => $totals['sales'],
                'expenses_total' => $totals['expenses'],
                'cash_shortage' => $totals['shortage'],
                'recipient_email' => $company->email,
                'sent_at' => now(),
                'sent_by' => Auth::id(),
                'type' => 'manual',
            ]);

            // Send email
            try {
                Mail::to($company->email)->send(new DailyTransactionMail($emailRecord, $company->name));
            } catch (\Throwable $mailError) {
                Log::error('Email send failed: ' . $mailError->getMessage(), [
                    'exception' => $mailError,
                    'company_email' => $company->email,
                    'line' => $mailError->getLine(),
                    'file' => $mailError->getFile(),
                ]);
                throw $mailError;
            }

            return response()->json([
                'status' => 200,
                'message' => 'Daily transaction email sent successfully to ' . $company->email . '.',
                'data' => [
                    'sales_total' => $totals['sales'],
                    'expenses_total' => $totals['expenses'],
                    'cash_shortage' => $totals['shortage'],
                    'sent_at' => $emailRecord->sent_at,
                ],
            ]);
        } catch (\Throwable $e) {
             Log::error('DailyTransactionEmail error: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'status' => 500,
                'message' => 'Error sending email: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Send daily transaction email automatically (called by scheduler)
     * This is called by the scheduled task in Kernel.php
     */
    public function sendAuto()
    {
        // Check if feature is enabled
        if (!$this->isEmailFeatureEnabled()) {
            Log::info('Daily transaction email feature is disabled.');
            return ['status' => 'skipped', 'message' => 'Feature is disabled'];
        }

        try {
            $company = Company::first();
            if (!$company) {
                Log::warning('No company found for auto-send.');
                return ['status' => 'skipped', 'message' => 'No company found'];
            }

            if (empty($company->email)) {
                Log::warning('Company email is empty for auto-send.', ['company_id' => $company->id]);
                return ['status' => 'skipped', 'message' => 'Company email is empty'];
            }

            // Check if already sent today
            if (DailyTransactionEmail::alreadySentToday($company->id)) {
                Log::info('Email already sent today for company: ' . $company->id);
                return ['status' => 'skipped', 'message' => 'Already sent today'];
            }

            // Calculate totals
            $businessDate = today();
            $totals = $this->calculateDailyTotals($businessDate);

            // Create email record
            $emailRecord = DailyTransactionEmail::create([
                'company_id' => $company->id,
                'business_date' => $businessDate,
                'sales_total' => $totals['sales'],
                'expenses_total' => $totals['expenses'],
                'cash_shortage' => $totals['shortage'],
                'recipient_email' => $company->email,
                'sent_at' => now(),
                'sent_by' => null, // Auto-send, no user
                'type' => 'auto',
            ]);

            // Send email
            Mail::to($company->email)->send(new DailyTransactionMail($emailRecord, $company->name));

            Log::info('Auto-send daily transaction email sent successfully to ' . $company->email);
            return ['status' => 'sent', 'message' => 'Email sent', 'recipient' => $company->email];
        } catch (\Throwable $e) {
            Log::error('Error auto-sending daily transaction email: ' . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Get status of last sent email
     */
    public function getStatus()
    {
        $enabled = $this->isEmailFeatureEnabled();
        $configuredHour = (int) config('pos.daily_email_report_time', 18);
        $configuredTimeLabel = str_pad((string) $configuredHour, 2, '0', STR_PAD_LEFT) . ':00';
        $businessDate = now()->toDateString();
        $schedulerHealth = $this->getSchedulerHealthSnapshot();

        try {
            $company = Company::first();
            if (!$company) {
                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'feature_enabled' => $enabled,
                    'enabled' => $enabled,
                    'already_sent_today' => false,
                    'last_sent_at' => null,
                    'last_sent' => null,
                    'last_send_type' => null,
                    'send_type' => null,
                    'recipient_email' => null,
                    'auto_send_enabled' => $enabled,
                    'configured_auto_send_time_label' => $configuredTimeLabel,
                    'scheduler_health' => $schedulerHealth,
                    'business_date' => $businessDate,
                    'can_send' => false,
                    'message' => 'No company found.',
                ]);
            }

            $lastSent = DailyTransactionEmail::lastSent($company->id);
            $alreadySentToday = DailyTransactionEmail::alreadySentToday($company->id);
            $canSend = $enabled
                && !$alreadySentToday
                && auth()->check()
                && Gate::allows('daily-transaction-email-send');

            $lastSentAt = $lastSent ? $lastSent->sent_at->format('Y-m-d H:i:s') : null;
            $lastSendType = $lastSent?->type;

            return response()->json([
                'success' => true,
                'status' => 200,
                'feature_enabled' => $enabled,
                'enabled' => $enabled,
                'already_sent_today' => $alreadySentToday,
                'last_sent_at' => $lastSentAt,
                'last_sent' => $lastSentAt,
                'last_send_type' => $lastSendType,
                'send_type' => $lastSendType,
                'recipient_email' => $company->email,
                'auto_send_enabled' => $enabled,
                'configured_auto_send_time_label' => $configuredTimeLabel,
                'scheduler_health' => $schedulerHealth,
                'business_date' => $businessDate,
                'can_send' => $canSend,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'feature_enabled' => $enabled,
                'enabled' => $enabled,
                'already_sent_today' => false,
                'last_sent_at' => null,
                'last_sent' => null,
                'last_send_type' => null,
                'send_type' => null,
                'recipient_email' => null,
                'auto_send_enabled' => $enabled,
                'configured_auto_send_time_label' => $configuredTimeLabel,
                'scheduler_health' => $schedulerHealth,
                'business_date' => $businessDate,
                'can_send' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Calculate daily totals: sales, expenses, and cash shortage
     */
    private function calculateDailyTotals($date)
    {
        $date = Carbon::parse($date);

        // Calculate total sales from payments (full or partial paid)
        $salesTotal = (float) Payment::whereDate('created_at', $date)->sum('total_amount');

        // Calculate total expenses from expense voucher items
        $expensesTotal = (float) ExpenseVoucherItem::whereDate('created_at', $date)->sum('amount');

        // Calculate cash shortage from cash reconciliations
        // Shortage = negative variance (physical cash < expected)
        $cashShortage = (float) \App\Models\CashReconciliation::whereDate('business_date', $date)
            ->where('variance', '<', 0) // Only negative variances
            ->sum('variance');
        $cashShortage = abs($cashShortage); // Make it positive for display

        return [
            'sales' => round($salesTotal, 2),
            'expenses' => round($expensesTotal, 2),
            'shortage' => round($cashShortage, 2),
        ];
    }

    /**
     * Check if email feature is enabled
     */
    private function isEmailFeatureEnabled()
    {
        // Prefer runtime config when available, but fall back to DB value to avoid
        // stale config states when cache/middleware timing differs across requests.
        $configEnabled = filter_var(config('pos.enable_daily_email_report', false), FILTER_VALIDATE_BOOLEAN);

        $raw = AppSetting::getValue('enable_daily_email_report');
        if ($raw === null) {
            return (bool) $configEnabled;
        }

        return filter_var($raw, FILTER_VALIDATE_BOOLEAN);
    }

    private function getSchedulerHealthSnapshot(): array
    {
        $maxAgeMinutes = 20;
        $lastRunRaw = Cache::get('health:daily-email-scheduler:last_run_at');

        if (!$lastRunRaw) {
            return [
                'status' => 'missing',
                'healthy' => false,
                'last_run_at' => null,
                'age_minutes' => null,
                'max_age_minutes' => $maxAgeMinutes,
                'message' => 'No scheduler heartbeat recorded yet.',
            ];
        }

        try {
            $lastRun = Carbon::parse($lastRunRaw);
        } catch (\Throwable $e) {
            return [
                'status' => 'invalid',
                'healthy' => false,
                'last_run_at' => (string) $lastRunRaw,
                'age_minutes' => null,
                'max_age_minutes' => $maxAgeMinutes,
                'message' => 'Scheduler heartbeat value is invalid.',
            ];
        }

        $ageMinutes = $lastRun->diffInMinutes(now());
        $healthy = $ageMinutes <= $maxAgeMinutes;

        return [
            'status' => $healthy ? 'ok' : 'stale',
            'healthy' => $healthy,
            'last_run_at' => $lastRun->format('Y-m-d H:i:s'),
            'age_minutes' => $ageMinutes,
            'max_age_minutes' => $maxAgeMinutes,
            'message' => $healthy
                ? 'Scheduler heartbeat is healthy.'
                : 'Scheduler heartbeat is stale.',
        ];
    }
}
