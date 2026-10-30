<?php

namespace App\DataTables\Admin\Settings;

use App\Models\ExpenseCategory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class ExpenseCategoryDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('account_name', function ($row) {
                if ($row->account) {
                    return '<span class="badge bg-light text-dark border">[' . e($row->account->code) . '] ' . e($row->account->name) . '</span>';
                }
                return '<span class="badge bg-secondary">Default (5200 Operating Expenses)</span>';
            })
            ->addColumn('edit_button', function ($row) {
                $editUrl = route('expense-categories.edit', Crypt::encrypt($row->id));
                $deleteUrl = route('expense-categories.destroy', Crypt::encrypt($row->id));
                $csrf = csrf_field();
                $method = method_field('DELETE');

                $buttons = [];

                if (Gate::allows('expense-category-edit')) {
                    $buttons[] = '
                    <form class="d-inline-block m-0" action="' . $editUrl . '" method="GET">
                        ' . $csrf . '
                        <button class="btn-action-edit table-edit-btn">
                            <i class="fas fa-edit"></i>
                        </button>
                    </form>';
                }

                if (Gate::allows('expense-category-delete')) {
                    $buttons[] = '
                    <form class="d-inline-block m-0" action="' . $deleteUrl . '" method="POST">
                        ' . $csrf . '
                        ' . $method . '
                        <button type="button" class="btn-action-delete delete-confirm-btn btn-delete">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </form>';
                }

                if (empty($buttons)) {
                    return '<span class="text-muted">No actions</span>';
                }

                return '<div class="d-inline-flex align-items-center flex-nowrap gap-2 action-buttons">' . implode('', $buttons) . '</div>';
            })
            ->editColumn('created_at', function ($row) {
                return Carbon::parse($row->created_at)->format('jS F, Y g:i A');
            })
            ->editColumn('updated_at', function ($row) {
                return $row->updated_at
                    ? Carbon::parse($row->updated_at)->format('jS F, Y g:i A')
                    : '';
            })
            ->rawColumns(['edit_button', 'account_name'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(ExpenseCategory $model): QueryBuilder
    {
        return $model->newQuery()->with('account')->orderBy('name', 'asc');
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('expense-category-table')
            ->columns($this->getColumns())
            ->minifiedAjax(request()->getRequestUri())
            ->orderBy(0)
            ->selectStyleSingle()
            ->parameters([
                'dom' => "<'row'l>Bfrtip",
                'buttons' => ['colvis', 'excel', 'csv', 'reset', 'reload'],
            ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        $columns = [
            Column::make('name')->title('Category Name'),
            Column::make('account_name')->title('GL Expense Account')->searchable(false),
            Column::make('created_at')->title('Created At'),
            Column::make('updated_at')->title('Updated At'),
        ];

        if (Gate::allows('expense-category-edit') || Gate::allows('expense-category-delete')) {
            $columns[] = Column::make('edit_button')->title('Actions')
                ->searchable(false)->printable(false)->exportable(false);
        }

        return $columns;
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'ExpenseCategory_' . date('YmdHis');
    }
}
