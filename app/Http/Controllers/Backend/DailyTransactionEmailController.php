<?php

namespace App\Http\Controllers\Backend;

use App\DataTables\Admin\Settings\DailyTransactionEmailDataTable;
use App\Http\Controllers\Controller;
use App\Mail\DailyTransactionMail;
use App\Models\AppSetting;
use App\Models\CashReconciliation;
use App\Models\Company;
use App\Models\DailyTransactionEmail;
use App\Models\ExpenseVoucherItem;
use App\Models\Outlet;
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
        if (!Auth::user()->isCentral()) {
            return redirect()->route('dashboard')->with([
                'message' => 'Unauthorized. Only Central Headquarters staff can access enterprise daily transaction email reporting.',
                'alert-type' => 'error',
            ]);
        }

        $businessDate = today();
        $totals = $this->calculateDailyTotals($businessDate);
        $company = Company::first();
        $lastSent = $company ? DailyTransactionEmail::lastSent($company->id) : null;
        $alreadySentToday = $company ? DailyTransactionEmail::alreadySentToday($company->id) : false;
        $isFeatureEnabled = $this->isEmailFeatureEnabled();
        $autoSendHour = (int) config('pos.daily_email_report_time', 18);
        $schedulerHealth = $this->getSchedulerHealthSnapshot();

        return view('backend.settings.daily_transaction_report', compact(
            'totals',
            'company',
            'lastSent',
            'alreadySentToday',
            'isFeatureEnabled',
            'autoSendHour',
            'schedulerHealth',
            'businessDate'
        ));
    }

    public function logs(DailyTransactionEmailDataTable $dataTable)
    {
        if (!Auth::user()->isCentral()) {
            return redirect()->route('dashboard')->with([
                'message' => 'Unauthorized. Only Central Headquarters staff can view daily transaction email audit logs.',
                'alert-type' => 'error',
            ]);
        }

        return $dataTable->render('backend.settings.daily_transaction_email_logs');
    }

    /**
     * Send daily transaction email manually
     */
    public function sendManual()
    {
        if (!Auth::user()->isCentral()) {
            return response()->json([
                'status' => 403,
                'message' => 'Unauthorized. Only Central Headquarters staff can dispatch enterprise daily transaction reports.',
            ], 403);
        }

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
                Mail::to($company->email)->send(new DailyTransactionMail($emailRecord, $company->name, $totals['breakdown'] ?? []));
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
            Mail::to($company->email)->send(new DailyTransactionMail($emailRecord, $company->name, $totals['breakdown'] ?? []));

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
        if (!Auth::user()->isCentral()) {
            return response()->json([
                'success' => false,
                'status' => 403,
                'message' => 'Unauthorized. Only Central Headquarters staff can access daily transaction email status.',
            ], 403);
        }

        $enabled = $this->isEmailFeatureEnabled();
        $configuredHour = (int) config('pos.daily_email_report_time', 18);
        $configuredTimeLabel = str_pad((string) $configuredHour, 2, '0', STR_PAD_LEFT) . ':00';
        $businessDate = now()->toDateString();
        $schedulerHealth = $this->getSchedulerHealthSnapshot();
        $totals = $this->calculateDailyTotals($businessDate);

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
                    'totals' => $totals,
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
                'totals' => $totals,
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
     * Calculate daily totals: sales, expenses, and cash shortage, plus per-outlet breakdown
     */
    private function calculateDailyTotals($date)
    {
        $date = Carbon::parse($date);

        $outlets = Outlet::orderBy('is_central', 'desc')->orderBy('name', 'asc')->get();

        // Group sales by outlet
        $salesByOutlet = Payment::join('sales', 'payments.sale_id', '=', 'sales.id')
            ->whereNull('sales.deleted_at')
            ->whereDate('payments.created_at', $date)
            ->selectRaw('sales.outlet_id, SUM(payments.total_amount) as total')
            ->groupBy('sales.outlet_id')
            ->pluck('total', 'sales.outlet_id');

        // Group operating expenses by outlet
        $expensesByOutlet = ExpenseVoucherItem::join('expense_vouchers', 'expense_voucher_items.expense_voucher_id', '=', 'expense_vouchers.id')
            ->whereNull('expense_vouchers.deleted_at')
            ->whereDate('expense_voucher_items.created_at', $date)
            ->selectRaw('expense_vouchers.outlet_id, SUM(expense_voucher_items.amount) as total')
            ->groupBy('expense_vouchers.outlet_id')
            ->pluck('total', 'expense_vouchers.outlet_id');

        // Group cash shortages by outlet (negative variances only)
        $shortagesByOutlet = CashReconciliation::whereDate('business_date', $date)
            ->where('variance', '<', 0)
            ->selectRaw('outlet_id, SUM(ABS(variance)) as total')
            ->groupBy('outlet_id')
            ->pluck('total', 'outlet_id');

        $breakdown = [];
        $totalSales = 0.0;
        $totalExpenses = 0.0;
        $totalShortage = 0.0;

        foreach ($outlets as $outlet) {
            $s = round((float) ($salesByOutlet[$outlet->id] ?? 0), 2);
            $e = round((float) ($expensesByOutlet[$outlet->id] ?? 0), 2);
            $sh = round((float) ($shortagesByOutlet[$outlet->id] ?? 0), 2);
            $net = round($s - $e - $sh, 2);

            $totalSales += $s;
            $totalExpenses += $e;
            $totalShortage += $sh;

            $breakdown[] = [
                'outlet_id' => $outlet->id,
                'name' => $outlet->name,
                'code' => $outlet->code ?? ('OUT-' . $outlet->id),
                'is_central' => (bool) $outlet->is_central,
                'sales' => $s,
                'expenses' => $e,
                'shortage' => $sh,
                'net' => $net,
                'has_activity' => ($s > 0 || $e > 0 || $sh > 0),
            ];
        }

        // Catch any unassigned payments (if any legacy sale had null outlet_id)
        $unassignedSales = round((float) Payment::leftJoin('sales', 'payments.sale_id', '=', 'sales.id')
            ->whereNull('sales.deleted_at')
            ->whereNull('sales.outlet_id')
            ->whereDate('payments.created_at', $date)
            ->sum('payments.total_amount'), 2);

        if ($unassignedSales > 0) {
            $totalSales += $unassignedSales;
            $breakdown[] = [
                'outlet_id' => null,
                'name' => 'Unassigned Outlets',
                'code' => 'N/A',
                'is_central' => false,
                'sales' => $unassignedSales,
                'expenses' => 0.0,
                'shortage' => 0.0,
                'net' => $unassignedSales,
                'has_activity' => true,
            ];
        }

        return [
            'sales' => round($totalSales, 2),
            'expenses' => round($totalExpenses, 2),
            'shortage' => round($totalShortage, 2),
            'net' => round($totalSales - $totalExpenses - $totalShortage, 2),
            'breakdown' => $breakdown,
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
