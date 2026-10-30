<?php

namespace App\DataTables\Admin\Report\Accounting;

use App\Models\AccountingJournal;
use App\Models\Outlet;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\QueryDataTable;
use Yajra\DataTables\Services\DataTable;

class CashFlowDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): QueryDataTable
    {
        $summaryQuery = clone $query;
        $summaryRow = DB::query()
            ->fromSub($summaryQuery, 'cf')
            ->selectRaw('ROUND(COALESCE(SUM(cf.inflow),0),2) as total_inflow, ROUND(COALESCE(SUM(cf.outflow),0),2) as total_outflow')
            ->first();

        $openingBalance = $this->calculateOpeningBalance();
        $inflow = round((float) ($summaryRow->total_inflow ?? 0), 2);
        $outflow = round((float) ($summaryRow->total_outflow ?? 0), 2);
        $net = round($inflow - $outflow, 2);
        $closingBalance = round($openingBalance + $net, 2);

        return datatables()
            ->query($query)
            ->editColumn('outlet_name', fn ($row) => $row->outlet_name ?: 'Central Warehouse')
            ->editColumn('account_label', fn ($row) => trim(($row->account_code ?? '') . ' - ' . ($row->account_name ?? '')))
            ->editColumn('source_type', fn ($row) => ucwords(str_replace('_', ' ', (string) $row->source_type)))
            ->editColumn('inflow', fn ($row) => number_format((float) $row->inflow, 2))
            ->editColumn('outflow', fn ($row) => number_format((float) $row->outflow, 2))
            ->editColumn('net_effect', fn ($row) => number_format((float) $row->net_effect, 2))
            ->with([
                'summary' => [
                    'opening_balance' => $openingBalance,
                    'inflow' => $inflow,
                    'outflow' => $outflow,
                    'net' => $net,
                    'closing_balance' => $closingBalance,
                ],
            ])
            ->setRowId('id');
    }

    public function query(): QueryBuilder
    {
        $fromDate = $this->request()->get('from_date') ?: now()->startOfMonth()->toDateString();
        $toDate = $this->request()->get('to_date') ?: now()->toDateString();
        $accountId = (int) $this->request()->get('account_id', 0);
        $sourceType = trim((string) $this->request()->get('source_type', ''));
        $flowDirection = trim((string) $this->request()->get('flow_direction', ''));

        $outletId = (auth()->check() && auth()->user()->outlet_id)
            ? (int) auth()->user()->outlet_id
            : ($this->request()->filled('outlet_id') ? (int) $this->request()->get('outlet_id') : null);

        $centralOutletName = (string) (Outlet::where('is_central', true)->value('name') ?? 'Central Warehouse');

        $query = DB::table('accounting_journal_lines as l')
            ->join('accounting_journals as j', 'j.id', '=', 'l.journal_id')
            ->join('accounting_accounts as a', 'a.id', '=', 'l.account_id');

        if (AccountingJournal::hasOutletColumn()) {
            $query->leftJoin('outlets as o', 'o.id', '=', 'j.outlet_id')
                ->select(
                    'l.id',
                    'j.posting_date',
                    'j.journal_no',
                    'j.reference_no',
                    'j.source_type',
                    'a.code as account_code',
                    'a.name as account_name',
                    DB::raw("CONCAT(a.code, ' - ', a.name) as account_label"),
                    'l.line_description',
                    DB::raw('ROUND(COALESCE(l.debit, 0), 2) as inflow'),
                    DB::raw('ROUND(COALESCE(l.credit, 0), 2) as outflow'),
                    DB::raw('ROUND(COALESCE(l.debit - l.credit, 0), 2) as net_effect'),
                    DB::raw('COALESCE(o.name, ' . DB::getPdo()->quote($centralOutletName) . ') as outlet_name')
                );
        } else {
            $query->select(
                'l.id',
                'j.posting_date',
                'j.journal_no',
                'j.reference_no',
                'j.source_type',
                'a.code as account_code',
                'a.name as account_name',
                DB::raw("CONCAT(a.code, ' - ', a.name) as account_label"),
                'l.line_description',
                DB::raw('ROUND(COALESCE(l.debit, 0), 2) as inflow'),
                DB::raw('ROUND(COALESCE(l.credit, 0), 2) as outflow'),
                DB::raw('ROUND(COALESCE(l.debit - l.credit, 0), 2) as net_effect'),
                DB::raw("COALESCE(
                    (SELECT o.name FROM sales s JOIN outlets o ON o.id = s.outlet_id WHERE s.id = j.source_id AND j.source_type IN ('sale','sale_cancel','sale_receipt') LIMIT 1),
                    (SELECT o.name FROM expense_vouchers ev JOIN outlets o ON o.id = ev.outlet_id WHERE ev.id = j.source_id AND j.source_type IN ('expense_voucher','expense_voucher_reversal') LIMIT 1),
                    (SELECT o.name FROM stock_adjustments sa JOIN outlets o ON o.id = sa.outlet_id WHERE sa.id = j.source_id AND j.source_type IN ('stock_adjustment','stock_adjustment_reversal') LIMIT 1),
                    (SELECT o.name FROM cash_reconciliations cr JOIN outlets o ON o.id = cr.outlet_id WHERE cr.id = j.source_id AND j.source_type IN ('cash_reconciliation','cash_reconciliation_reversal') LIMIT 1),
                    " . DB::getPdo()->quote($centralOutletName) . "
                ) as outlet_name")
            );
        }

        $query->where(function ($q) {
                $q->whereIn('a.code', ['1000', '1010'])
                  ->orWhere('a.is_bank_account', true);
            })
            ->where('j.status', 'posted')
            ->whereDate('j.posting_date', '>=', $fromDate)
            ->whereDate('j.posting_date', '<=', $toDate)
            ->whereRaw("NOT EXISTS (SELECT 1 FROM sales s WHERE s.id = j.source_id AND j.source_type IN ('sale','sale_cancel') AND s.cancelled_at IS NOT NULL)")
            ->whereRaw("NOT EXISTS (SELECT 1 FROM purchases p WHERE p.id = j.source_id AND j.source_type IN ('purchase','purchase_cancel') AND p.cancelled_at IS NOT NULL)")
            ->orderBy('j.posting_date')
            ->orderBy('l.id');

        if ($accountId > 0) {
            $query->where('l.account_id', $accountId);
        }

        if ($sourceType !== '') {
            $query->where('j.source_type', $sourceType);
        }

        if ($flowDirection === 'inflow') {
            $query->where('l.debit', '>', 0);
        } elseif ($flowDirection === 'outflow') {
            $query->where('l.credit', '>', 0);
        }

        AccountingJournal::applyOutletFilter($query, $outletId, 'j');

        return $query;
    }

    private function calculateOpeningBalance(): float
    {
        $fromDate = $this->request()->get('from_date') ?: now()->startOfMonth()->toDateString();
        $accountId = (int) $this->request()->get('account_id', 0);

        $outletId = (auth()->check() && auth()->user()->outlet_id)
            ? (int) auth()->user()->outlet_id
            : ($this->request()->filled('outlet_id') ? (int) $this->request()->get('outlet_id') : null);

        $openingQuery = DB::table('accounting_journal_lines as l')
            ->join('accounting_journals as j', 'j.id', '=', 'l.journal_id')
            ->join('accounting_accounts as a', 'a.id', '=', 'l.account_id')
            ->where(function ($q) {
                $q->whereIn('a.code', ['1000', '1010'])
                  ->orWhere('a.is_bank_account', true);
            })
            ->where('j.status', 'posted')
            ->whereDate('j.posting_date', '<', $fromDate)
            ->whereRaw("NOT EXISTS (SELECT 1 FROM sales s WHERE s.id = j.source_id AND j.source_type IN ('sale','sale_cancel') AND s.cancelled_at IS NOT NULL)")
            ->whereRaw("NOT EXISTS (SELECT 1 FROM purchases p WHERE p.id = j.source_id AND j.source_type IN ('purchase','purchase_cancel') AND p.cancelled_at IS NOT NULL)");

        if ($accountId > 0) {
            $openingQuery->where('l.account_id', $accountId);
        }

        AccountingJournal::applyOutletFilter($openingQuery, $outletId, 'j');

        $row = $openingQuery
            ->selectRaw('ROUND(COALESCE(SUM(l.debit - l.credit), 0), 2) as opening_balance')
            ->first();

        return round((float) ($row->opening_balance ?? 0), 2);
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('cash-flow-table')
            ->columns($this->getColumns())
            ->minifiedAjax(request()->getRequestUri())
            ->orderBy(0)
            ->selectStyleSingle()
            ->parameters([
                'dom' => "<'row'l>Bfrtip",
                'buttons' => [
                    'colvis',
                    ['extend' => 'excel', 'footer' => true],
                    ['extend' => 'csv', 'footer' => true],
                    'reset',
                    'reload',
                ],
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::make('posting_date')->title('Date'),
            Column::make('outlet_name')->title('Outlet')->searchable(false)->orderable(false),
            Column::make('account_label')->name('a.code')->title('Cash / Bank Account'),
            Column::make('journal_no')->title('Journal'),
            Column::make('reference_no')->title('Reference'),
            Column::make('source_type')->title('Activity / Source'),
            Column::make('line_description')->title('Description'),
            Column::make('inflow')->title('Inflow (Gh¢)')->searchable(false),
            Column::make('outflow')->title('Outflow (Gh¢)')->searchable(false),
            Column::make('net_effect')->title('Net Effect (Gh¢)')->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'CashFlow_' . date('YmdHis');
    }
}

