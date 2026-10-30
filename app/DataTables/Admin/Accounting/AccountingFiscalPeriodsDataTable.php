<?php

namespace App\DataTables\Admin\Accounting;

use App\Models\AccountingFiscalPeriod;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class AccountingFiscalPeriodsDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->editColumn('name', fn ($row) => '<span class="fw-bold">' . e((string) $row->name) . '</span>')
            ->editColumn('start_date', fn ($row) => $row->start_date ? Carbon::parse($row->start_date)->format('Y-m-d') : '-')
            ->editColumn('end_date', fn ($row) => $row->end_date ? Carbon::parse($row->end_date)->format('Y-m-d') : '-')
            ->addColumn('scope_label', fn () => '<span class="badge bg-light text-dark border">All Outlets (Global Cutoff)</span>')
            ->addColumn('status_text', fn ($row) => $row->is_closed
                ? '<span class="badge bg-danger">Closed</span>'
                : '<span class="badge bg-success">Open</span>')
            ->editColumn('closed_at', fn ($row) => $row->closed_at ? Carbon::parse($row->closed_at)->format('Y-m-d H:i') : '-')
            ->addColumn('action', function ($row) {
                $canEdit = Gate::allows('accounting-fiscal-period-edit') || Gate::allows('accounting-fiscal-period-close');
                $canClose = Gate::allows('accounting-fiscal-period-close');
                $canReopen = Gate::allows('accounting-fiscal-period-reopen') || Gate::allows('accounting-fiscal-period-close');

                $buttons = [];

                if (!$row->is_closed && $canEdit) {
                    $startDate = $row->start_date ? Carbon::parse($row->start_date)->format('Y-m-d') : '';
                    $endDate = $row->end_date ? Carbon::parse($row->end_date)->format('Y-m-d') : '';

                    $buttons[] = '<button type="button" class="btn-action-edit table-edit-btn js-edit-fiscal-period" title="Edit Fiscal Period"'
                        . ' data-id="' . (int) $row->id . '"'
                        . ' data-name="' . e((string) $row->name) . '"'
                        . ' data-start_date="' . e($startDate) . '"'
                        . ' data-end_date="' . e($endDate) . '">'
                        . '<i class="fa fa-edit"></i></button>';
                }

                if (!$row->is_closed && $canClose) {
                    $url = route('accounting.setup.fiscal-periods.close', $row->id);
                    $csrf = csrf_field();

                    $buttons[] = '<form class="d-inline-block m-0" method="POST" action="' . $url . '">'
                        . $csrf
                        . '<button type="submit" class="btn-action-delete delete-confirm-btn" title="Close Fiscal Period" onclick="return confirm(\'Close this fiscal period across all outlets?\')">'
                        . '<i class="fa fa-lock"></i></button>'
                        . '</form>';
                }

                if ($row->is_closed && $canReopen) {
                    $url = route('accounting.setup.fiscal-periods.reopen', $row->id);
                    $csrf = csrf_field();

                    $buttons[] = '<form class="d-inline-block m-0" method="POST" action="' . $url . '">'
                        . $csrf
                        . '<button type="submit" class="btn-action-amber" title="Reopen Fiscal Period" onclick="return confirm(\'Reopen this fiscal period across all outlets?\')">'
                        . '<i class="fa fa-unlock"></i></button>'
                        . '</form>';
                }

                if (empty($buttons)) {
                    return '-';
                }

                return '<div class="action-buttons">' . implode('', $buttons) . '</div>';
            })
            ->rawColumns(['name', 'scope_label', 'status_text', 'action'])
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
            Column::make('name')->title('Period Name'),
            Column::make('start_date')->title('Start Date'),
            Column::make('end_date')->title('End Date'),
            Column::computed('scope_label')->title('Outlet Scope')->searchable(false),
            Column::computed('status_text')->title('Status')->searchable(false),
            Column::make('closed_at')->title('Closed At'),
            Column::computed('action')->title('Action')->searchable(false)->exportable(false)->printable(false)->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'FiscalPeriods_' . date('YmdHis');
    }
}
