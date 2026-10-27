<?php

namespace App\DataTables\Admin\Report\Accounting;

use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\QueryDataTable;
use Yajra\DataTables\Services\DataTable;

class GeneralLedgerDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): QueryDataTable
    {
        return datatables()
            ->query($query)
            ->editColumn('debit', fn ($row) => number_format((float) $row->debit, 2))
            ->editColumn('credit', fn ($row) => number_format((float) $row->credit, 2))
            ->setRowId('id');
    }

    public function query(): QueryBuilder
    {
        $accountId = (int) $this->request()->get('account_id', 0);

        $query = DB::table('accounting_journal_lines as l')
            ->join('accounting_journals as j', 'j.id', '=', 'l.journal_id')
            ->select('l.id', 'j.journal_no', 'j.posting_date', 'j.reference_no', 'j.source_type', 'l.line_description', 'l.debit', 'l.credit')
            ->where('j.status', 'posted')
            ->orderBy('j.posting_date')
            ->orderBy('l.id');

        if ($accountId <= 0) {
            return $query->whereRaw('1 = 0');
        }

        $fromDate = $this->request()->get('from_date') ?: now()->startOfMonth()->toDateString();
        $toDate = $this->request()->get('to_date') ?: now()->toDateString();

        return $query
            ->where('l.account_id', $accountId)
            ->whereDate('j.posting_date', '>=', $fromDate)
            ->whereDate('j.posting_date', '<=', $toDate);
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('general-ledger-table')
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
            Column::make('posting_date')->title('Date'),
            Column::make('journal_no')->title('Journal'),
            Column::make('reference_no')->title('Reference'),
            Column::make('source_type')->title('Source'),
            Column::make('line_description')->title('Description'),
            Column::make('debit')->title('Debit')->searchable(false),
            Column::make('credit')->title('Credit')->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'GeneralLedger_' . date('YmdHis');
    }
}
