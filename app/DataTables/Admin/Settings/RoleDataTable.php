<?php

namespace App\DataTables\Admin\Settings;

use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class RoleDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('edit_button', function ($row) {
                $encryptedId = Crypt::encrypt($row->id);
                $editUrl = route('roles.edit', $encryptedId);
                $deleteUrl = route('roles.destroy', $encryptedId);
                $csrf = csrf_field();
                $method = method_field('DELETE');

                $showUrl = route('roles.show', $encryptedId);

                $buttons = [];

                if (Gate::allows('role-list')) {
                    $buttons[] = '
                    <a href="'.$showUrl.'" class="btn-action-view" title="View Role">
                        <i class="fas fa-eye"></i>
                    </a>';
                }

                $isCentral = auth()->check() && auth()->user()->isCentral();

                if ($isCentral && Gate::allows('role-edit')) {
                    $buttons[] = '
                    <form class="d-inline-block m-0" action="'.$editUrl.'" method="GET">
                        '.$csrf.'
                        <button type="submit" class="btn-action-edit table-edit-btn" title="Edit Role">
                            <i class="fas fa-edit"></i>
                        </button>
                    </form>';
                }

                if ($isCentral && Gate::allows('role-delete')) {
                    $buttons[] = '
                    <form class="d-inline-block m-0" action="'.$deleteUrl.'" method="POST">
                        '.$csrf.'
                        '.$method.'
                        <button type="button" class="btn-action-delete delete-confirm-btn btn-delete" title="Delete Role">
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
            ->rawColumns(['edit_button'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Role $model): QueryBuilder
    {
        return $model->newQuery()
            ->where('name', '!=', 'Super Admin')
            ->orderBy('id', 'DESC');
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('roles-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax(request()->getRequestUri())
                    ->orderBy(1)
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
            Column::make('name'),
            Column::make('created_at'),
            Column::make('updated_at'),
        ];

        if (Gate::allows('role-list') || Gate::allows('role-edit') || Gate::allows('role-delete')) {
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
        return 'Role_' . date('YmdHis');
    }
}
