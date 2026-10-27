<?php

namespace App\DataTables\Admin\Accounting;

use App\Models\AccountingAccount;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class AccountingAccountsDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->editColumn('type', fn ($row) => ucfirst((string) $row->type))
            ->addColumn('bank_flag', fn ($row) => $row->is_bank_account ? 'Yes' : 'No')
            ->addColumn('status_flag', fn ($row) => $row->is_active ? 'Active' : 'Inactive')
            ->setRowId('id');
    }

    public function query(AccountingAccount $model): QueryBuilder
    {
        return $model->newQuery()->orderBy('code');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('accounting-accounts-table')
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
            Column::make('code')->title('Code'),
            Column::make('name')->title('Name'),
            Column::make('type')->title('Type'),
            Column::computed('bank_flag')->title('Bank')->searchable(false),
            Column::computed('status_flag')->title('Status')->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'AccountingAccounts_' . date('YmdHis');
    }
}
