<?php

namespace App\DataTables\Admin\Accounting;

use App\Models\AccountingAccount;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class AccountingAccountsDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->editColumn('type', function ($row) {
                $type = strtolower((string) $row->type);
                $badgeClass = match ($type) {
                    'asset' => 'bg-primary',
                    'liability' => 'bg-warning text-dark',
                    'equity' => 'bg-info text-dark',
                    'income' => 'bg-success',
                    'expense' => 'bg-danger',
                    default => 'bg-secondary',
                };

                return '<span class="badge ' . $badgeClass . '">' . e(ucfirst($type)) . '</span>';
            })
            ->addColumn('bank_flag', fn ($row) => $row->is_bank_account
                ? '<span class="badge bg-info text-dark">Bank GL</span>'
                : '<span class="text-muted">No</span>')
            ->addColumn('scope_label', fn () => '<span class="badge bg-light text-dark border">All Outlets (Global)</span>')
            ->addColumn('status_flag', fn ($row) => $row->is_active
                ? '<span class="badge bg-success">Active</span>'
                : '<span class="badge bg-secondary">Inactive</span>')
            ->addColumn('actions', function ($row) {
                if (!Gate::allows('accounting-coa-edit')) {
                    return '-';
                }

                return '<div class="action-buttons">'
                    . '<button type="button" class="btn-action-edit table-edit-btn js-edit-coa" title="Edit"'
                    . ' data-id="' . (int) $row->id . '"'
                    . ' data-code="' . e((string) $row->code) . '"'
                    . ' data-name="' . e((string) $row->name) . '"'
                    . ' data-type="' . e((string) $row->type) . '"'
                    . ' data-is_bank_account="' . ($row->is_bank_account ? 1 : 0) . '"'
                    . ' data-is_active="' . ($row->is_active ? 1 : 0) . '">'
                    . '<i class="fa fa-edit"></i></button>'
                    . '</div>';
            })
            ->rawColumns(['type', 'bank_flag', 'scope_label', 'status_flag', 'actions'])
            ->setRowId('id');
    }

    public function query(AccountingAccount $model): QueryBuilder
    {
        return $model->newQuery()
            ->when(request('type'), fn ($q, $type) => $q->where('type', $type))
            ->orderBy('code');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('accounting-accounts-table')
            ->columns($this->getColumns())
            ->minifiedAjax(request()->getRequestUri())
            ->orderBy(0, 'asc')
            ->selectStyleSingle()
            ->parameters([
                'dom' => "<'row'l>Bfrtip",
                'buttons' => ['colvis', 'excel', 'csv', 'reset', 'reload'],
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::make('code')->title('Code'),
            Column::make('name')->title('Account Name'),
            Column::make('type')->title('Type'),
            Column::computed('bank_flag')->title('Bank GL')->searchable(false),
            Column::computed('scope_label')->title('Outlet Scope')->searchable(false),
            Column::computed('status_flag')->title('Status')->searchable(false),
            Column::computed('actions')->title('Actions')->exportable(false)->printable(false)->searchable(false)->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'AccountingAccounts_' . date('YmdHis');
    }
}
