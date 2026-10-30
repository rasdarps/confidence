<?php

namespace App\Http\Controllers\Backend;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\AccountingJournal;
use App\Models\Company;
use App\Models\Outlet;
use App\DataTables\Admin\Report\Accounting\ApAgingDataTable;
use App\DataTables\Admin\Report\Accounting\ArAgingDataTable;
use App\DataTables\Admin\Report\Accounting\CashFlowDataTable;
use App\DataTables\Admin\Report\Accounting\GeneralLedgerDataTable;
use App\DataTables\Admin\Report\Accounting\TrialBalanceDataTable;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountingReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:report-trial-balance')->only('trialBalance');
        $this->middleware('permission:report-general-ledger')->only('generalLedger');
        $this->middleware('permission:report-income-statement')->only('incomeStatement');
        $this->middleware('permission:report-balance-sheet')->only(['balanceSheet', 'balanceSheetPdf']);
        $this->middleware('permission:report-cash-flow')->only('cashFlow');
        $this->middleware('permission:report-ar-aging')->only('arAging');
        $this->middleware('permission:report-ap-aging')->only('apAging');
    }

    public function trialBalance(Request $request, TrialBalanceDataTable $dataTable)
    {
        [$fromDate, $toDate] = $this->resolveDateRange($request);

        $outlets = Outlet::where('is_active', true)
            ->orderByDesc('is_central')
            ->orderBy('name')
            ->get(['id', 'name', 'is_central']);

        $outletId = (auth()->check() && auth()->user()->outlet_id)
            ? (int) auth()->user()->outlet_id
            : ($request->filled('outlet_id') ? (int) $request->get('outlet_id') : null);

        $totals = DB::table('accounting_accounts as a')
            ->leftJoin('accounting_journal_lines as l', 'l.account_id', '=', 'a.id')
            ->leftJoin('accounting_journals as j', function ($join) use ($fromDate, $toDate, $outletId) {
                $join->on('j.id', '=', 'l.journal_id')
                    ->where('j.status', '=', 'posted')
                    ->whereDate('j.posting_date', '>=', $fromDate)
                    ->whereDate('j.posting_date', '<=', $toDate)
                    ->whereRaw("NOT EXISTS (SELECT 1 FROM sales s WHERE s.id = j.source_id AND j.source_type IN ('sale','sale_cancel') AND s.cancelled_at IS NOT NULL)")
                    ->whereRaw("NOT EXISTS (SELECT 1 FROM purchases p WHERE p.id = j.source_id AND j.source_type IN ('purchase','purchase_cancel') AND p.cancelled_at IS NOT NULL)");

                AccountingJournal::applyOutletFilter($join, $outletId, 'j');
            })
            ->selectRaw('ROUND(COALESCE(SUM(CASE WHEN j.id IS NOT NULL THEN l.debit ELSE 0 END),0),2) as total_debit, ROUND(COALESCE(SUM(CASE WHEN j.id IS NOT NULL THEN l.credit ELSE 0 END),0),2) as total_credit')
            ->first();

        $totalDebit = (float) ($totals->total_debit ?? 0);
        $totalCredit = (float) ($totals->total_credit ?? 0);

        return $dataTable->render('backend.report.accounting.trial_balance', compact('fromDate', 'toDate', 'totalDebit', 'totalCredit', 'outlets', 'outletId'));
    }

    public function generalLedger(Request $request, GeneralLedgerDataTable $dataTable)
    {
        [$fromDate, $toDate] = $this->resolveDateRange($request);

        $outlets = Outlet::where('is_active', true)
            ->orderByDesc('is_central')
            ->orderBy('name')
            ->get(['id', 'name', 'is_central']);

        $outletId = (auth()->check() && auth()->user()->outlet_id)
            ? (int) auth()->user()->outlet_id
            : ($request->filled('outlet_id') ? (int) $request->get('outlet_id') : null);

        $accounts = DB::table('accounting_accounts')->orderBy('code')->get(['id', 'code', 'name', 'type']);
        $accountId = (int) $request->get('account_id', 0);
        $account = $accountId > 0 ? $accounts->firstWhere('id', $accountId) : null;

        return $dataTable->render('backend.report.accounting.general_ledger', compact('accounts', 'account', 'accountId', 'fromDate', 'toDate', 'outlets', 'outletId'));
    }

    public function incomeStatement(Request $request)
    {
        [$fromDate, $toDate] = $this->resolveDateRange($request);

        $outlets = Outlet::where('is_active', true)
            ->orderByDesc('is_central')
            ->orderBy('name')
            ->get(['id', 'name', 'is_central']);

        $outletId = (auth()->check() && auth()->user()->outlet_id)
            ? (int) auth()->user()->outlet_id
            : ($request->filled('outlet_id') ? (int) $request->get('outlet_id') : null);

        $incomeRows = $this->accountTypeBreakdown('income', $fromDate, $toDate, $outletId);
        $expenseRows = $this->accountTypeBreakdown('expense', $fromDate, $toDate, $outletId);

        $cogsRows = $expenseRows->filter(fn ($row) => (string) $row->code === '5000')->values();
        $operatingExpenseRows = $expenseRows->reject(fn ($row) => (string) $row->code === '5000')->values();

        $income = round((float) $incomeRows->sum('net_amount'), 2);
        $expense = round((float) $expenseRows->sum('net_amount'), 2);
        $cogs = round((float) $cogsRows->sum('net_amount'), 2);
        $operatingExpense = round((float) $operatingExpenseRows->sum('net_amount'), 2);
        $grossProfit = round($income - $cogs, 2);
        $netProfit = round($income - $expense, 2);

        $grossMargin = $income > 0 ? round(($grossProfit / $income) * 100, 2) : 0.0;
        $netMargin = $income > 0 ? round(($netProfit / $income) * 100, 2) : 0.0;
        $companyName = optional(Company::first())->name ?? config('app.name', 'Company');
        $selectedOutlet = $outletId ? $outlets->firstWhere('id', $outletId) : null;

        return view('backend.report.accounting.income_statement', compact(
            'incomeRows',
            'expenseRows',
            'cogsRows',
            'operatingExpenseRows',
            'income',
            'expense',
            'cogs',
            'operatingExpense',
            'grossProfit',
            'netProfit',
            'grossMargin',
            'netMargin',
            'fromDate',
            'toDate',
            'outlets',
            'outletId',
            'selectedOutlet',
            'companyName'
        ));
    }

    public function balanceSheet(Request $request)
    {
        $asOfDate = $request->get('to_date') ?: now()->toDateString();

        $outlets = Outlet::where('is_active', true)
            ->orderByDesc('is_central')
            ->orderBy('name')
            ->get(['id', 'name', 'is_central']);

        $outletId = (auth()->check() && auth()->user()->outlet_id)
            ? (int) auth()->user()->outlet_id
            : ($request->filled('outlet_id') ? (int) $request->get('outlet_id') : null);

        $selectedOutlet = $outletId ? $outlets->firstWhere('id', $outletId) : null;
        $report = $this->buildBalanceSheetReport($asOfDate, $outletId);
        $companyName = optional(Company::first())->name ?? config('app.name', 'Company');

        return view('backend.report.accounting.balance_sheet', array_merge($report, [
            'asOfDate' => $asOfDate,
            'companyName' => $companyName,
            'outlets' => $outlets,
            'outletId' => $outletId,
            'selectedOutlet' => $selectedOutlet,
        ]));
    }

    public function balanceSheetPdf(Request $request)
    {
        $asOfDate = $request->get('to_date') ?: now()->toDateString();

        $outletId = (auth()->check() && auth()->user()->outlet_id)
            ? (int) auth()->user()->outlet_id
            : ($request->filled('outlet_id') ? (int) $request->get('outlet_id') : null);

        $selectedOutlet = $outletId ? Outlet::find($outletId) : null;
        $report = $this->buildBalanceSheetReport($asOfDate, $outletId);
        $companyName = optional(Company::first())->name ?? config('app.name', 'Company');

        $pdf = Pdf::loadView('backend.report.accounting.balance_sheet_pdf', array_merge($report, [
            'asOfDate' => $asOfDate,
            'companyName' => $companyName,
            'outletId' => $outletId,
            'selectedOutlet' => $selectedOutlet,
            'generatedAt' => now(),
        ]))->setPaper('a4', 'portrait');

        return $pdf->download('balance-sheet-' . $asOfDate . '.pdf');
    }

    private function buildBalanceSheetReport(string $asOfDate, ?int $outletId = null): array
    {
        $assets = $this->balanceByTypeAsOf('asset', $asOfDate, $outletId);
        $liabilities = $this->balanceByTypeAsOf('liability', $asOfDate, $outletId);
        $equity = $this->balanceByTypeAsOf('equity', $asOfDate, $outletId);
        $currentEarnings = $this->currentEarningsAsOf($asOfDate, $outletId);

        $assetTotal = round((float) $assets->sum('balance'), 2);
        $liabilityTotal = round((float) $liabilities->sum('balance'), 2);
        $equityTotal = round((float) $equity->sum('balance'), 2);
        $equityWithEarningsTotal = round($equityTotal + $currentEarnings, 2);
        $liabilitiesAndEquityTotal = round($liabilityTotal + $equityWithEarningsTotal, 2);
        $balanceDifference = round($assetTotal - $liabilitiesAndEquityTotal, 2);

        return compact(
            'assets',
            'liabilities',
            'equity',
            'assetTotal',
            'liabilityTotal',
            'equityTotal',
            'currentEarnings',
            'equityWithEarningsTotal',
            'liabilitiesAndEquityTotal',
            'balanceDifference'
        );
    }

    public function cashFlow(Request $request, CashFlowDataTable $dataTable)
    {
        [$fromDate, $toDate] = $this->resolveDateRange($request);

        $outlets = Outlet::where('is_active', true)
            ->orderByDesc('is_central')
            ->orderBy('name')
            ->get(['id', 'name', 'is_central']);

        $outletId = (auth()->check() && auth()->user()->outlet_id)
            ? (int) auth()->user()->outlet_id
            : ($request->filled('outlet_id') ? (int) $request->get('outlet_id') : null);

        $cashAccounts = DB::table('accounting_accounts')
            ->where(function ($q) {
                $q->whereIn('code', ['1000', '1010'])
                  ->orWhere('is_bank_account', true);
            })
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $cashAccountIds = $cashAccounts->pluck('id');

        $openingBalance = 0.0;
        $inflow = 0.0;
        $outflow = 0.0;

        if ($cashAccountIds->isNotEmpty()) {
            $openingQuery = DB::table('accounting_journal_lines as l')
                ->join('accounting_journals as j', 'j.id', '=', 'l.journal_id')
                ->whereIn('l.account_id', $cashAccountIds)
                ->where('j.status', 'posted')
                ->whereDate('j.posting_date', '<', $fromDate)
                ->whereRaw("NOT EXISTS (SELECT 1 FROM sales s WHERE s.id = j.source_id AND j.source_type IN ('sale','sale_cancel') AND s.cancelled_at IS NOT NULL)")
                ->whereRaw("NOT EXISTS (SELECT 1 FROM purchases p WHERE p.id = j.source_id AND j.source_type IN ('purchase','purchase_cancel') AND p.cancelled_at IS NOT NULL)");

            AccountingJournal::applyOutletFilter($openingQuery, $outletId, 'j');

            $openingRow = $openingQuery
                ->selectRaw('ROUND(COALESCE(SUM(l.debit - l.credit),0),2) as opening_balance')
                ->first();

            $openingBalance = round((float) ($openingRow->opening_balance ?? 0), 2);

            $totalsQuery = DB::table('accounting_journal_lines as l')
                ->join('accounting_journals as j', 'j.id', '=', 'l.journal_id')
                ->whereIn('l.account_id', $cashAccountIds)
                ->where('j.status', 'posted')
                ->whereDate('j.posting_date', '>=', $fromDate)
                ->whereDate('j.posting_date', '<=', $toDate)
                ->whereRaw("NOT EXISTS (SELECT 1 FROM sales s WHERE s.id = j.source_id AND j.source_type IN ('sale','sale_cancel') AND s.cancelled_at IS NOT NULL)")
                ->whereRaw("NOT EXISTS (SELECT 1 FROM purchases p WHERE p.id = j.source_id AND j.source_type IN ('purchase','purchase_cancel') AND p.cancelled_at IS NOT NULL)");

            AccountingJournal::applyOutletFilter($totalsQuery, $outletId, 'j');

            $totals = $totalsQuery
                ->selectRaw('ROUND(COALESCE(SUM(l.debit),0),2) as inflow, ROUND(COALESCE(SUM(l.credit),0),2) as outflow')
                ->first();

            $inflow = round((float) ($totals->inflow ?? 0), 2);
            $outflow = round((float) ($totals->outflow ?? 0), 2);
        }

        $net = round($inflow - $outflow, 2);
        $closingBalance = round($openingBalance + $net, 2);

        return $dataTable->render('backend.report.accounting.cash_flow', compact(
            'fromDate',
            'toDate',
            'openingBalance',
            'inflow',
            'outflow',
            'net',
            'closingBalance',
            'cashAccounts',
            'outlets',
            'outletId'
        ));
    }

    public function arAging(Request $request, ArAgingDataTable $dataTable)
    {
        $asOfDate = $request->get('to_date') ?: now()->toDateString();

        $outlets = Outlet::where('is_active', true)
            ->orderByDesc('is_central')
            ->orderBy('name')
            ->get(['id', 'name', 'is_central']);

        $outletId = (auth()->check() && auth()->user()->outlet_id)
            ? (int) auth()->user()->outlet_id
            : ($request->filled('outlet_id') ? (int) $request->get('outlet_id') : null);

        $customers = DB::table('customers')
            ->orderBy('name')
            ->get(['id', 'name', 'mobile_no']);

        return $dataTable->render('backend.report.accounting.ar_aging', compact(
            'asOfDate',
            'outlets',
            'outletId',
            'customers'
        ));
    }

    public function apAging(Request $request, ApAgingDataTable $dataTable)
    {
        $asOfDate = $request->get('to_date') ?: now()->toDateString();

        $centralOutlet = Outlet::where('is_central', true)->first(['id', 'name']);
        $centralOutletName = $centralOutlet ? $centralOutlet->name : 'Central Warehouse';

        $suppliers = DB::table('suppliers')
            ->orderBy('name')
            ->get(['id', 'name', 'mobile_no']);

        return $dataTable->render('backend.report.accounting.ap_aging', compact(
            'asOfDate',
            'centralOutletName',
            'suppliers'
        ));
    }

    private function resolveDateRange(Request $request): array
    {
        $fromDate = $request->get('from_date') ?: now()->startOfMonth()->toDateString();
        $toDate = $request->get('to_date') ?: now()->toDateString();

        return [$fromDate, $toDate];
    }

    private function accountTypeBreakdown(string $type, string $fromDate, string $toDate, ?int $outletId = null)
    {
        return DB::table('accounting_accounts as a')
            ->leftJoin('accounting_journal_lines as l', 'l.account_id', '=', 'a.id')
            ->leftJoin('accounting_journals as j', function ($join) use ($fromDate, $toDate, $outletId) {
                $join->on('j.id', '=', 'l.journal_id')
                    ->where('j.status', '=', 'posted')
                    ->whereDate('j.posting_date', '>=', $fromDate)
                    ->whereDate('j.posting_date', '<=', $toDate)
                    ->whereRaw("NOT EXISTS (SELECT 1 FROM sales s WHERE s.id = j.source_id AND j.source_type IN ('sale','sale_cancel') AND s.cancelled_at IS NOT NULL)")
                    ->whereRaw("NOT EXISTS (SELECT 1 FROM purchases p WHERE p.id = j.source_id AND j.source_type IN ('purchase','purchase_cancel') AND p.cancelled_at IS NOT NULL)");

                AccountingJournal::applyOutletFilter($join, $outletId, 'j');
            })
            ->where('a.type', $type)
            ->groupBy('a.id', 'a.code', 'a.name', 'a.type')
            ->orderBy('a.code')
            ->select(
                'a.id',
                'a.code',
                'a.name',
                'a.type',
                DB::raw('ROUND(COALESCE(SUM(CASE WHEN j.id IS NOT NULL THEN l.debit ELSE 0 END),0),2) as total_debit'),
                DB::raw('ROUND(COALESCE(SUM(CASE WHEN j.id IS NOT NULL THEN l.credit ELSE 0 END),0),2) as total_credit')
            )
            ->get()
            ->map(function ($row) use ($type) {
                $debit = (float) ($row->total_debit ?? 0);
                $credit = (float) ($row->total_credit ?? 0);
                $row->net_amount = $type === 'income'
                    ? round($credit - $debit, 2)
                    : round($debit - $credit, 2);

                return $row;
            });
    }

    private function accountTypeNet(string $type, string $fromDate, string $toDate, ?int $outletId = null): float
    {
        return round((float) $this->accountTypeBreakdown($type, $fromDate, $toDate, $outletId)->sum('net_amount'), 2);
    }

    private function balanceByTypeAsOf(string $type, string $asOfDate, ?int $outletId = null)
    {
        return DB::table('accounting_accounts as a')
            ->leftJoin('accounting_journal_lines as l', 'l.account_id', '=', 'a.id')
            ->leftJoin('accounting_journals as j', function ($join) use ($asOfDate, $outletId) {
                $join->on('j.id', '=', 'l.journal_id')
                    ->where('j.status', '=', 'posted')
                    ->whereDate('j.posting_date', '<=', $asOfDate)
                    ->whereRaw("NOT EXISTS (SELECT 1 FROM sales s WHERE s.id = j.source_id AND j.source_type IN ('sale','sale_cancel') AND s.cancelled_at IS NOT NULL)")
                    ->whereRaw("NOT EXISTS (SELECT 1 FROM purchases p WHERE p.id = j.source_id AND j.source_type IN ('purchase','purchase_cancel') AND p.cancelled_at IS NOT NULL)");

                AccountingJournal::applyOutletFilter($join, $outletId, 'j');
            })
            ->where('a.type', $type)
            ->groupBy('a.id', 'a.code', 'a.name', 'a.type')
            ->orderBy('a.code')
            ->select(
                'a.id',
                'a.code',
                'a.name',
                DB::raw('ROUND(COALESCE(SUM(CASE WHEN j.id IS NOT NULL THEN l.debit ELSE 0 END),0),2) as debit_total'),
                DB::raw('ROUND(COALESCE(SUM(CASE WHEN j.id IS NOT NULL THEN l.credit ELSE 0 END),0),2) as credit_total')
            )
            ->get()
            ->map(function ($row) use ($type) {
                $debit = (float) $row->debit_total;
                $credit = (float) $row->credit_total;

                if ($type === 'asset' || $type === 'expense') {
                    $row->balance = round($debit - $credit, 2);
                } else {
                    $row->balance = round($credit - $debit, 2);
                }

                return $row;
            })
            ->filter(fn ($row) => abs((float) $row->balance) > 0.0001)
            ->values();
    }

    private function currentEarningsAsOf(string $asOfDate, ?int $outletId = null): float
    {
        $incomeQuery = DB::table('accounting_accounts as a')
            ->join('accounting_journal_lines as l', 'l.account_id', '=', 'a.id')
            ->join('accounting_journals as j', 'j.id', '=', 'l.journal_id')
            ->where('a.type', 'income')
            ->where('j.status', 'posted')
            ->whereDate('j.posting_date', '<=', $asOfDate)
            ->whereRaw("NOT EXISTS (SELECT 1 FROM sales s WHERE s.id = j.source_id AND j.source_type IN ('sale','sale_cancel') AND s.cancelled_at IS NOT NULL)")
            ->whereRaw("NOT EXISTS (SELECT 1 FROM purchases p WHERE p.id = j.source_id AND j.source_type IN ('purchase','purchase_cancel') AND p.cancelled_at IS NOT NULL)");

        AccountingJournal::applyOutletFilter($incomeQuery, $outletId, 'j');

        $income = $incomeQuery
            ->selectRaw('ROUND(COALESCE(SUM(l.credit),0),2) as total_credit, ROUND(COALESCE(SUM(l.debit),0),2) as total_debit')
            ->first();

        $expenseQuery = DB::table('accounting_accounts as a')
            ->join('accounting_journal_lines as l', 'l.account_id', '=', 'a.id')
            ->join('accounting_journals as j', 'j.id', '=', 'l.journal_id')
            ->where('a.type', 'expense')
            ->where('j.status', 'posted')
            ->whereDate('j.posting_date', '<=', $asOfDate)
            ->whereRaw("NOT EXISTS (SELECT 1 FROM sales s WHERE s.id = j.source_id AND j.source_type IN ('sale','sale_cancel') AND s.cancelled_at IS NOT NULL)")
            ->whereRaw("NOT EXISTS (SELECT 1 FROM purchases p WHERE p.id = j.source_id AND j.source_type IN ('purchase','purchase_cancel') AND p.cancelled_at IS NOT NULL)");

        AccountingJournal::applyOutletFilter($expenseQuery, $outletId, 'j');

        $expense = $expenseQuery
            ->selectRaw('ROUND(COALESCE(SUM(l.debit),0),2) as total_debit, ROUND(COALESCE(SUM(l.credit),0),2) as total_credit')
            ->first();

        $incomeNet = (float) (($income->total_credit ?? 0) - ($income->total_debit ?? 0));
        $expenseNet = (float) (($expense->total_debit ?? 0) - ($expense->total_credit ?? 0));

        return round($incomeNet - $expenseNet, 2);
    }
}
