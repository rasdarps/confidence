<?php

namespace App\DataTables\Admin\Settings;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class UserDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('roles', function ($row) {
                return $row->getRoleNames()->implode(', ');
            })
            ->addColumn('outlet', function ($row) {
                return $row->outlet
                    ? '<span class="badge bg-info text-dark">' . e($row->outlet->name) . '</span>'
                    : '<span class="badge bg-secondary">Central / HQ</span>';
            })
            ->addColumn('edit_button', function ($row) {
                $encryptedId = Crypt::encrypt($row->id);
                $editUrl = route('users.edit', $encryptedId);
                $deleteUrl = route('users.destroy', $encryptedId);
                $csrf = csrf_field();
                $method = method_field('DELETE');

                $showUrl = route('users.show', $encryptedId);

                $buttons = [];

                if (Gate::allows('user-list')) {
                    $buttons[] = '
                    <a href="'.$showUrl.'" class="btn btn-sm btn-info text-nowrap" style="display:inline-flex;align-items:center;gap:6px;width:auto;" title="View">
                        <i class="fas fa-eye" style="color:#fff;"></i>
                    </a>';
                }

                if (Gate::allows('user-edit')) {
                    $buttons[] = '
                    <form class="d-inline-block m-0" action="'.$editUrl.'" method="GET">
                        '.$csrf.'
                        <button style="border:none; background:green; cursor:pointer; display:inline-flex; align-items:center; gap:6px; width:auto;" class="table-edit-btn btn btn-sm btn-success text-nowrap">
                            <i class="fas fa-edit" style="color:#fff; justify-content:center;"></i>
                        </button>
                    </form>';
                }

                if (Gate::allows('user-delete')) {
                    $buttons[] = '
                    <form class="d-inline-block m-0" action="'.$deleteUrl.'" method="POST">
                        '.$csrf.'
                        '.$method.'
                        <button type="button" style="border:none; background:red; cursor:pointer; display:inline-flex; align-items:center; gap:6px; width:auto;" class="btn-delete delete-confirm-btn btn btn-sm btn-danger text-nowrap">
                            <i class="fas fa-trash" style="color:#fff; justify-content:center;"></i>
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
            ->rawColumns(['edit_button', 'outlet'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(User $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->with('outlet')
            ->where('user_type', '!=', 'Super Admin');

        if (auth()->check()) {
            $query->where('id', '!=', auth()->id());
        }

        return $query->orderBy('id', 'DESC');
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('users-table')
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
            Column::make('username'),
            Column::make('email'),
            Column::make('roles')->searchable(false),
            Column::make('outlet')->title('Branch / Warehouse')->searchable(false),
            Column::make('created_at'),
            Column::make('updated_at'),
        ];

        if (Gate::allows('user-list') || Gate::allows('user-edit') || Gate::allows('user-delete')) {
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
        return 'User_' . date('YmdHis');
    }
}
