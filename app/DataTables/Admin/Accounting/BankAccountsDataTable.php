<?php

namespace App\DataTables\Admin\Accounting;

use App\Models\BankAccount;
use Illuminate\Support\Facades\Gate;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
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
            ->editColumn('current_balance', fn ($row) => number_format((float) $row->current_balance, 2))
            ->addColumn('status_text', fn ($row) => $row->is_active ? 'Active' : 'Inactive')
            ->addColumn('actions', function ($row) {
                $canEdit = Gate::allows('bank-account-edit');
                $canDelete = Gate::allows('bank-account-delete');

                $actions = '';
                if ($canEdit) {
                    $actions .= '<button type="button" class="btn btn-sm btn-primary js-edit-bank-account text-nowrap" style="display:inline-flex;align-items:center;gap:6px;width:auto;"'
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
                    $actions .= '<button type="button" class="btn btn-sm btn-danger js-delete-bank-account text-nowrap" style="display:inline-flex;align-items:center;gap:6px;width:auto;" data-id="' . (int) $row->id . '"><i class="fa fa-trash"></i></button>';
                }

                return $actions ? '<div class="d-inline-flex align-items-center flex-nowrap gap-2 action-buttons">' . $actions . '</div>' : '-';
            })
            ->rawColumns(['actions'])
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
            Column::make('name')->title('Name'),
            Column::make('bank_name')->title('Bank'),
            Column::make('account_number')->title('Number'),
            Column::computed('gl_account')->title('GL'),
            Column::make('current_balance')->title('Balance'),
            Column::computed('status_text')->title('Status')->searchable(false),
            Column::computed('actions')->title('Actions')->exportable(false)->printable(false)->searchable(false)->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'BankAccounts_' . date('YmdHis');
    }
}
