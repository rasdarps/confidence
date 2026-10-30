<?php

namespace App\DataTables\Admin\Settings;

use App\Models\Outlet;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class OutletsDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->editColumn('type', function ($row) {
                if ($row->is_central) {
                    return '<span class="badge bg-primary">Central Warehouse</span>';
                }
                return '<span class="badge bg-info text-dark">Retail Outlet</span>';
            })
            ->editColumn('is_active', function ($row) {
                return $row->is_active
                    ? '<span class="badge bg-success">Active</span>'
                    : '<span class="badge bg-secondary">Inactive</span>';
            })
            ->addColumn('action', function ($row) {
                $editUrl = route('outlets.edit', Crypt::encrypt($row->id));
                $deleteUrl = route('outlets.destroy', Crypt::encrypt($row->id));
                $csrf = csrf_field();
                $method = method_field('DELETE');

                $buttons = [];
                if (Gate::allows('outlet-edit')) {
                    $buttons[] = '
                    <a href="' . $editUrl . '" class="btn-action-edit table-edit-btn" title="Edit Outlet">
                        <i class="fas fa-edit"></i>
                    </a>';
                }

                if (Gate::allows('outlet-delete') && !$row->is_central) {
                    $buttons[] = '
                    <form class="d-inline-block m-0" action="' . $deleteUrl . '" method="POST">
                        ' . $csrf . '
                        ' . $method . '
                        <button type="button" class="btn-action-delete delete-confirm-btn btn-delete" title="Deactivate Outlet">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </form>';
                }

                return '<div class="d-inline-flex align-items-center flex-nowrap gap-2 action-buttons">' . implode('', $buttons) . '</div>';
            })
            ->rawColumns(['type', 'is_active', 'action']);
    }

    public function query(Outlet $model): QueryBuilder
    {
        return $model->newQuery()->orderBy('is_central', 'desc')->orderBy('name', 'asc');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('outlets-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(1)
            ->selectStyleSingle();
    }

    public function getColumns(): array
    {
        return [
            Column::make('DT_RowIndex')->title('#')->searchable(false)->orderable(false),
            Column::make('name')->title('Outlet Name'),
            Column::make('code')->title('Code'),
            Column::make('type')->title('Type'),
            Column::make('phone')->title('Phone'),
            Column::make('address')->title('Address'),
            Column::make('is_active')->title('Status'),
            Column::computed('action')->title('Action')->exportable(false)->printable(false)->width(120)->addClass('text-center'),
        ];
    }
}

