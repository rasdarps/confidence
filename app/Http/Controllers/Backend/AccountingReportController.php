<?php

namespace App\Http\Controllers\Backend;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Company;
use App\DataTables\Admin\Report\Accounting\ApAgingDataTable;
use App\DataTables\Admin\Report\Accounting\ArAgingDataTable;
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

        $totals = DB::table('accounting_accounts as a')
            ->leftJoin('accounting_journal_lines as l', 'l.account_id', '=', 'a.id')
            ->leftJoin('accounting_journals as j', function ($join) use ($fromDate, $toDate) {
                $join->on('j.id', '=', 'l.journal_id')
                    ->where('j.status', '=', 'posted')
                    ->whereDate('j.posting_date', '>=', $fromDate)
                    ->whereDate('j.posting_date', '<=', $toDate)
                    ->whereRaw("NOT EXISTS (SELECT 1 FROM sales s WHERE s.id = j.source_id AND j.source_type IN ('sale','sale_cancel') AND s.cancelled_at IS NOT NULL)")
                    ->whereRaw("NOT EXISTS (SELECT 1 FROM purchases p WHERE p.id = j.source_id AND j.source_type IN ('purchase','purchase_cancel') AND p.cancelled_at IS NOT NULL)");
            })
            ->selectRaw('ROUND(COALESCE(SUM(CASE WHEN j.id IS NOT NULL THEN l.debit ELSE 0 END),0),2) as total_debit, ROUND(COALESCE(SUM(CASE WHEN j.id IS NOT NULL THEN l.credit ELSE 0 END),0),2) as total_credit')
            ->first();

        $totalDebit = (float) ($totals->total_debit ?? 0);
        $totalCredit = (float) ($totals->total_credit ?? 0);

        return $dataTable->render('backend.report.accounting.trial_balance', compact('fromDate', 'toDate', 'totalDebit', 'totalCredit'));
    }

    public function generalLedger(Request $request, GeneralLedgerDataTable $dataTable)
    {
        [$fromDate, $toDate] = $this->resolveDateRange($request);

        $accounts = DB::table('accounting_accounts')->orderBy('code')->get(['id', 'code', 'name']);
        $accountId = (int) $request->get('account_id', 0);
        $account = $accountId > 0 ? $accounts->firstWhere('id', $accountId) : null;

        return $dataTable->render('backend.report.accounting.general_ledger', compact('accounts', 'account', 'accountId', 'fromDate', 'toDate'));
    }

    public function incomeStatement(Request $request)
    {
        [$fromDate, $toDate] = $this->resolveDateRange($request);

        $income = $this->accountTypeNet('income', $fromDate, $toDate);
        $expense = $this->accountTypeNet('expense', $fromDate, $toDate);
        $netProfit = $income - $expense;

        return view('backend.report.accounting.income_statement', compact('income', 'expense', 'netProfit', 'fromDate', 'toDate'));
    }

    public function balanceSheet(Request $request)
    {
        $asOfDate = $request->get('to_date') ?: now()->toDateString();
        $report = $this->buildBalanceSheetReport($asOfDate);
        $companyName = optional(Company::first())->name ?? config('app.name', 'Company');

        return view('backend.report.accounting.balance_sheet', array_merge($report, [
            'asOfDate' => $asOfDate,
            'companyName' => $companyName,
        ]));
    }

    public function balanceSheetPdf(Request $request)
    {
        $asOfDate = $request->get('to_date') ?: now()->toDateString();
        $report = $this->buildBalanceSheetReport($asOfDate);
        $companyName = optional(Company::first())->name ?? config('app.name', 'Company');

        $pdf = Pdf::loadView('backend.report.accounting.balance_sheet_pdf', array_merge($report, [
            'asOfDate' => $asOfDate,
            'companyName' => $companyName,
            'generatedAt' => now(),
        ]))->setPaper('a4', 'portrait');

        return $pdf->download('balance-sheet-' . $asOfDate . '.pdf');
    }

    private function buildBalanceSheetReport(string $asOfDate): array
    {
        $assets = $this->balanceByTypeAsOf('asset', $asOfDate);
        $liabilities = $this->balanceByTypeAsOf('liability', $asOfDate);
        $equity = $this->balanceByTypeAsOf('equity', $asOfDate);
        $currentEarnings = $this->currentEarningsAsOf($asOfDate);

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

    public function cashFlow(Request $request)
    {
        [$fromDate, $toDate] = $this->resolveDateRange($request);

        $cashAccountIds = DB::table('accounting_accounts')
            ->whereIn('code', ['1000', '1010'])
            ->pluck('id');

        $totals = DB::table('accounting_journal_lines as l')
            ->join('accounting_journals as j', 'j.id', '=', 'l.journal_id')
            ->whereIn('l.account_id', $cashAccountIds)
            ->where('j.status', 'posted')
            ->whereDate('j.posting_date', '>=', $fromDate)
            ->whereDate('j.posting_date', '<=', $toDate)
            ->whereRaw("NOT EXISTS (SELECT 1 FROM sales s WHERE s.id = j.source_id AND j.source_type IN ('sale','sale_cancel') AND s.cancelled_at IS NOT NULL)")
            ->whereRaw("NOT EXISTS (SELECT 1 FROM purchases p WHERE p.id = j.source_id AND j.source_type IN ('purchase','purchase_cancel') AND p.cancelled_at IS NOT NULL)")
            ->selectRaw('ROUND(COALESCE(SUM(l.debit),0),2) as inflow, ROUND(COALESCE(SUM(l.credit),0),2) as outflow')
            ->first();

        $inflow = (float) ($totals->inflow ?? 0);
        $outflow = (float) ($totals->outflow ?? 0);
        $net = $inflow - $outflow;

        return view('backend.report.accounting.cash_flow', compact('fromDate', 'toDate', 'inflow', 'outflow', 'net'));
    }

    public function arAging(Request $request, ArAgingDataTable $dataTable)
    {
        $asOfDate = $request->get('to_date') ?: now()->toDateString();

        return $dataTable->render('backend.report.accounting.ar_aging', compact('asOfDate'));
    }

    public function apAging(Request $request, ApAgingDataTable $dataTable)
    {
        $asOfDate = $request->get('to_date') ?: now()->toDateString();

        return $dataTable->render('backend.report.accounting.ap_aging', compact('asOfDate'));
    }

    private function resolveDateRange(Request $request): array
    {
        $fromDate = $request->get('from_date') ?: now()->startOfMonth()->toDateString();
        $toDate = $request->get('to_date') ?: now()->toDateString();

        return [$fromDate, $toDate];
    }

    private function accountTypeNet(string $type, string $fromDate, string $toDate): float
    {
        $row = DB::table('accounting_accounts as a')
            ->join('accounting_journal_lines as l', 'l.account_id', '=', 'a.id')
            ->join('accounting_journals as j', 'j.id', '=', 'l.journal_id')
            ->where('a.type', $type)
            ->where('j.status', 'posted')
            ->whereDate('j.posting_date', '>=', $fromDate)
            ->whereDate('j.posting_date', '<=', $toDate)
            ->selectRaw('ROUND(COALESCE(SUM(l.credit),0),2) as total_credit, ROUND(COALESCE(SUM(l.debit),0),2) as total_debit')
            ->first();

        if ($type === 'income') {
            return (float) (($row->total_credit ?? 0) - ($row->total_debit ?? 0));
        }

        return (float) (($row->total_debit ?? 0) - ($row->total_credit ?? 0));
    }

    private function balanceByTypeAsOf(string $type, string $asOfDate)
    {
        return DB::table('accounting_accounts as a')
            ->leftJoin('accounting_journal_lines as l', 'l.account_id', '=', 'a.id')
            ->leftJoin('accounting_journals as j', function ($join) use ($asOfDate) {
                $join->on('j.id', '=', 'l.journal_id')
                    ->where('j.status', '=', 'posted')
                    ->whereDate('j.posting_date', '<=', $asOfDate);
            })
            ->where('a.type', $type)
            ->groupBy('a.id', 'a.code', 'a.name', 'a.type')
            ->select(
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

    private function currentEarningsAsOf(string $asOfDate): float
    {
        $income = DB::table('accounting_accounts as a')
            ->join('accounting_journal_lines as l', 'l.account_id', '=', 'a.id')
            ->join('accounting_journals as j', 'j.id', '=', 'l.journal_id')
            ->where('a.type', 'income')
            ->where('j.status', 'posted')
            ->whereDate('j.posting_date', '<=', $asOfDate)
            ->selectRaw('ROUND(COALESCE(SUM(l.credit),0),2) as total_credit, ROUND(COALESCE(SUM(l.debit),0),2) as total_debit')
            ->first();

        $expense = DB::table('accounting_accounts as a')
            ->join('accounting_journal_lines as l', 'l.account_id', '=', 'a.id')
            ->join('accounting_journals as j', 'j.id', '=', 'l.journal_id')
            ->where('a.type', 'expense')
            ->where('j.status', 'posted')
            ->whereDate('j.posting_date', '<=', $asOfDate)
            ->selectRaw('ROUND(COALESCE(SUM(l.debit),0),2) as total_debit, ROUND(COALESCE(SUM(l.credit),0),2) as total_credit')
            ->first();

        $incomeNet = (float) (($income->total_credit ?? 0) - ($income->total_debit ?? 0));
        $expenseNet = (float) (($expense->total_debit ?? 0) - ($expense->total_credit ?? 0));

        return round($incomeNet - $expenseNet, 2);
    }

}
