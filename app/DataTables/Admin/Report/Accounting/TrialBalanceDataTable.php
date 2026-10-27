<?php

namespace App\DataTables\Admin\Report\Accounting;

use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\QueryDataTable;
use Yajra\DataTables\Services\DataTable;

class TrialBalanceDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): QueryDataTable
    {
        $summaryQuery = clone $query;
        $summaryRow = DB::query()
            ->fromSub($summaryQuery, 'tb')
            ->selectRaw('ROUND(COALESCE(SUM(tb.debit_total),0),2) as total_debit, ROUND(COALESCE(SUM(tb.credit_total),0),2) as total_credit')
            ->first();

        return datatables()
            ->query($query)
            ->editColumn('type', fn ($row) => ucfirst((string) $row->type))
            ->editColumn('debit_total', fn ($row) => number_format((float) $row->debit_total, 2))
            ->editColumn('credit_total', fn ($row) => number_format((float) $row->credit_total, 2))
            ->with([
                'summary' => [
                    'total_debit' => (float) ($summaryRow->total_debit ?? 0),
                    'total_credit' => (float) ($summaryRow->total_credit ?? 0),
                ],
            ])
            ->setRowId('id');
    }

    public function query(): QueryBuilder
    {
        $fromDate = $this->request()->get('from_date') ?: now()->startOfMonth()->toDateString();
        $toDate = $this->request()->get('to_date') ?: now()->toDateString();

        return DB::table('accounting_accounts as a')
            ->leftJoin('accounting_journal_lines as l', 'l.account_id', '=', 'a.id')
            ->leftJoin('accounting_journals as j', function ($join) use ($fromDate, $toDate) {
                $join->on('j.id', '=', 'l.journal_id')
                    ->where('j.status', '=', 'posted')
                    ->whereDate('j.posting_date', '>=', $fromDate)
                    ->whereDate('j.posting_date', '<=', $toDate)
                    ->whereRaw("NOT EXISTS (SELECT 1 FROM sales s WHERE s.id = j.source_id AND j.source_type IN ('sale','sale_cancel') AND s.cancelled_at IS NOT NULL)")
                    ->whereRaw("NOT EXISTS (SELECT 1 FROM purchases p WHERE p.id = j.source_id AND j.source_type IN ('purchase','purchase_cancel') AND p.cancelled_at IS NOT NULL)");
            })
            ->select(
                'a.id',
                'a.code',
                'a.name',
                'a.type',
                DB::raw('ROUND(COALESCE(SUM(CASE WHEN j.id IS NOT NULL THEN l.debit ELSE 0 END), 0), 2) as debit_total'),
                DB::raw('ROUND(COALESCE(SUM(CASE WHEN j.id IS NOT NULL THEN l.credit ELSE 0 END), 0), 2) as credit_total')
            )
            ->groupBy('a.id', 'a.code', 'a.name', 'a.type')
            ->orderBy('a.code');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('trial-balance-table')
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
            Column::make('code')->title('Code'),
            Column::make('name')->title('Account'),
            Column::make('type')->title('Type'),
            Column::make('debit_total')->title('Debit')->searchable(false),
            Column::make('credit_total')->title('Credit')->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'TrialBalance_' . date('YmdHis');
    }
}
