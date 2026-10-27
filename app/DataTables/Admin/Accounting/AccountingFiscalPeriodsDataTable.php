<?php

namespace App\DataTables\Admin\Accounting;

use App\Models\AccountingFiscalPeriod;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class AccountingFiscalPeriodsDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('status_text', fn ($row) => $row->is_closed ? 'Closed' : 'Open')
            ->addColumn('action', function ($row) {
                if ($row->is_closed) {
                    return '';
                }

                $url = route('accounting.setup.fiscal-periods.close', $row->id);
                $csrf = csrf_field();

                return '<div class="d-inline-flex align-items-center flex-nowrap gap-2 action-buttons">'
                    . '<form class="d-inline-block m-0" method="POST" action="' . $url . '">'
                    . $csrf
                    . '<button class="btn btn-sm btn-danger text-nowrap" style="display:inline-flex;align-items:center;gap:6px;width:auto;" onclick="return confirm(\'Close this fiscal period?\')">Close Period</button>'
                    . '</form>'
                    . '</div>';
            })
            ->rawColumns(['action'])
            ->setRowId('id');
    }

    public function query(AccountingFiscalPeriod $model): QueryBuilder
    {
        return $model->newQuery()->orderByDesc('start_date');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('accounting-fiscal-periods-table')
            ->columns($this->getColumns())
            ->minifiedAjax(request()->getRequestUri())
            ->orderBy(1, 'desc')
            ->selectStyleSingle()
            ->parameters([
                'dom' => "<'row'l>Bfrtip",
                'buttons' => ['colvis', 'excel', 'csv', 'reset', 'reload'],
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::make('name')->title('Name'),
            Column::make('start_date')->title('Start'),
            Column::make('end_date')->title('End'),
            Column::computed('status_text')->title('Status')->searchable(false),
            Column::make('closed_at')->title('Closed At'),
            Column::computed('action')->title('Action')->searchable(false)->exportable(false)->printable(false),
        ];
    }

    protected function filename(): string
    {
        return 'FiscalPeriods_' . date('YmdHis');
    }
}
