<?php

namespace App\DataTables\Admin\Report\Accounting;

use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\QueryDataTable;
use Yajra\DataTables\Services\DataTable;

class ApAgingDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): QueryDataTable
    {
        return datatables()
            ->query($query)
            ->editColumn('current', fn ($row) => number_format((float) $row->current, 2))
            ->editColumn('days_31_60', fn ($row) => number_format((float) $row->days_31_60, 2))
            ->editColumn('days_61_90', fn ($row) => number_format((float) $row->days_61_90, 2))
            ->editColumn('days_90_plus', fn ($row) => number_format((float) $row->days_90_plus, 2))
            ->editColumn('balance', fn ($row) => number_format((float) $row->balance, 2))
            ->setRowId('entity_id');
    }

    public function query(): QueryBuilder
    {
        $asOfDate = $this->request()->get('to_date') ?: now()->toDateString();
        $accountId = DB::table('accounting_accounts')->where('code', '2000')->value('id');

        $query = DB::table('accounting_journal_lines as l')
            ->join('accounting_journals as j', 'j.id', '=', 'l.journal_id')
            ->where('j.status', 'posted')
            ->whereDate('j.posting_date', '<=', $asOfDate)
            ->where('l.entity_type', 'supplier')
            ->groupBy('l.entity_id')
            ->selectRaw(
                'l.entity_id,
                 MAX(j.posting_date) as last_date,
                 TIMESTAMPDIFF(DAY, MAX(j.posting_date), ?) as days,
                 ROUND(SUM(l.credit - l.debit),2) as balance',
                [$asOfDate]
            );

        if (!$accountId) {
            return $query->whereRaw('1 = 0');
        }

        return DB::query()
            ->fromSub($query->where('l.account_id', $accountId), 'x')
            ->selectRaw(
                'x.entity_id,
                 x.last_date,
                 CASE WHEN x.days <= 30 THEN x.balance ELSE 0 END as current,
                 CASE WHEN x.days > 30 AND x.days <= 60 THEN x.balance ELSE 0 END as days_31_60,
                 CASE WHEN x.days > 60 AND x.days <= 90 THEN x.balance ELSE 0 END as days_61_90,
                 CASE WHEN x.days > 90 THEN x.balance ELSE 0 END as days_90_plus,
                 x.balance'
            )
            ->whereRaw('ABS(x.balance) > 0.0001');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('ap-aging-table')
            ->columns($this->getColumns())
            ->minifiedAjax(request()->getRequestUri())
            ->orderBy(0)
            ->selectStyleSingle()
            ->parameters([
                'dom' => "<'row'l>Bfrtip",
                'buttons' => ['colvis', 'excel', 'csv', 'reset', 'reload'],
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::make('entity_id')->title('Entity ID'),
            Column::make('last_date')->title('Last Date'),
            Column::make('current')->title('0-30')->searchable(false),
            Column::make('days_31_60')->title('31-60')->searchable(false),
            Column::make('days_61_90')->title('61-90')->searchable(false),
            Column::make('days_90_plus')->title('90+')->searchable(false),
            Column::make('balance')->title('Balance')->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'APAging_' . date('YmdHis');
    }
}
