<?php

namespace App\DataTables\Admin\Accounting;

use App\Models\BankAccount;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class BankAccountsDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('gl_account', function ($row) {
                $code = optional($row->glAccount)->code;
                $name = optional($row->glAccount)->name;
                return trim(($code ? $code . ' - ' : '') . ($name ?? ''));
            })
            ->addColumn('scope_label', fn () => '<span class="badge bg-light text-dark border">All Outlets (Shared Treasury)</span>')
            ->editColumn('opening_balance', fn ($row) => number_format((float) $row->opening_balance, 2))
            ->editColumn('current_balance', fn ($row) => '<span class="fw-bold">' . number_format((float) $row->current_balance, 2) . '</span>')
            ->addColumn('status_text', fn ($row) => $row->is_active
                ? '<span class="badge bg-success">Active</span>'
                : '<span class="badge bg-secondary">Inactive</span>')
            ->addColumn('actions', function ($row) {
                $canEdit = Gate::allows('bank-account-edit');
                $canDelete = Gate::allows('bank-account-delete');

                $actions = '';
                if ($canEdit) {
                    $actions .= '<button type="button" class="btn-action-edit table-edit-btn js-edit-bank-account" title="Edit Bank Account"'
                        . ' data-id="' . (int) $row->id . '"'
                        . ' data-name="' . e((string) $row->name) . '"'
                        . ' data-bank_name="' . e((string) $row->bank_name) . '"'
                        . ' data-account_number="' . e((string) $row->account_number) . '"'
                        . ' data-currency="' . e((string) $row->currency) . '"'
                        . ' data-gl_account_id="' . (int) $row->gl_account_id . '"'
                        . ' data-is_active="' . ((int) ((bool) $row->is_active)) . '">'
                        . '<i class="fa fa-edit"></i></button>';
                }

                if ($canDelete) {
                    $actions .= '<button type="button" class="btn-action-delete delete-confirm-btn btn-delete js-delete-bank-account" title="Delete Bank Account" data-id="' . (int) $row->id . '"><i class="fa fa-trash"></i></button>';
                }

                return $actions ? '<div class="action-buttons">' . $actions . '</div>' : '-';
            })
            ->rawColumns(['scope_label', 'current_balance', 'status_text', 'actions'])
            ->setRowId('id');
    }

    public function query(BankAccount $model): QueryBuilder
    {
        return $model->newQuery()->with('glAccount')->orderBy('name');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('bank-accounts-table')
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
            Column::make('name')->title('Internal Name'),
            Column::make('bank_name')->title('Bank / Institution'),
            Column::make('account_number')->title('Account No.'),
            Column::computed('gl_account')->title('Linked GL'),
            Column::computed('scope_label')->title('Outlet Scope')->searchable(false),
            Column::make('opening_balance')->title('Opening Bal.')->addClass('text-end'),
            Column::make('current_balance')->title('Current Bal.')->addClass('text-end'),
            Column::computed('status_text')->title('Status')->searchable(false),
            Column::computed('actions')->title('Actions')->exportable(false)->printable(false)->searchable(false)->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'BankAccounts_' . date('YmdHis');
    }
}
