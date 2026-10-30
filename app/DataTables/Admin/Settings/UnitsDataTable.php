<?php

namespace App\DataTables\Admin\Settings;

use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class UnitsDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', 'units.action')
            ->addColumn('scope', function($row) {
                return '<span class="badge bg-info text-white"><i class="fas fa-globe me-1"></i> Global Master</span>';
            })
            ->editColumn('products_count', function($row) {
                return '<span class="badge bg-light text-dark border">' . number_format($row->products_count ?? 0) . ' items</span>';
            })
            ->addColumn('edit_button', function($row) {
                    $user = auth()->user();
                    if (!$user || !$user->isCentral()) {
                        return '<span class="badge bg-secondary text-white" title="Managed centrally by HQ"><i class="fas fa-lock me-1"></i> Central Managed</span>';
                    }

                    $editUrl = route('units.edit', Crypt::encrypt($row->id));
                    $deleteUrl = route('units.destroy', Crypt::encrypt($row->id));
                    $csrf = csrf_field();
                    $method = method_field('DELETE');

                    $buttons = [];

                    if (Gate::allows('unit-edit')) {
                        $buttons[] = '
                        <form class="d-inline-block m-0" action="'.$editUrl.'" method="GET">
                            '.$csrf.'
                            <button class="btn-action-edit table-edit-btn" title="Edit Unit">
                                <i class="fas fa-edit"></i>
                            </button>
                        </form>';
                    }

                    if (Gate::allows('unit-delete')) {
                        $buttons[] = '
                        <form class="d-inline-block m-0" action="'.$deleteUrl.'" method="POST">
                            '.$csrf.'
                            '.$method.'
                            <button type="button" class="btn-action-delete delete-confirm-btn btn-delete" title="Delete Unit">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </form>';
                    }

                    if (empty($buttons)) {
                        return '<span class="text-muted">No actions</span>';
                    }

                    return '<div class="d-inline-flex align-items-center flex-nowrap gap-2 action-buttons">' . implode('', $buttons) . '</div>';
                })
            
            ->editColumn('created_at', function($row)
            {
                return  Carbon::parse($row->created_at)->format('jS F, Y g:i A');
            })
            ->editColumn('updated_at', function($row) {
                return $row->updated_at
                    ? Carbon::parse($row->updated_at)->format('jS F, Y g:i A')
                    : '';
            })

             ->rawColumns(['edit_button', 'scope', 'products_count'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Unit $model): QueryBuilder
    {
        return $model->newQuery()->withCount('products')->orderBy('name', 'asc');
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('units-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax(request()->getRequestUri())
                    //->dom('Bfrtip')
                    ->orderBy(0)
                    ->selectStyleSingle()
                    ->parameters([
                        'dom'          => "<'row'l>Bfrtip",  //row counter button
                        'buttons'      => ['excel', 'csv', 'reset', 'reload'],
                        'pageLength'   => 10,
                        'responsive'   => true,
                        'language'     => [
                            'search'     => 'Search Units:',
                            'lengthMenu' => 'Show _MENU_ entries',
                            'info'       => 'Showing _START_ to _END_ of _TOTAL_ units',
                        ],
                    ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        $columns = [
            Column::make('name')->title('Unit Name'),
            Column::make('products_count')->title('Linked Products')->searchable(false),
            Column::make('scope')->title('Catalog Scope')->searchable(false)->orderable(false),
            // Column::make('created_at')->title('Created At'),
            // Column::make('updated_at')->title('Updated at'),
        ];

        if (Gate::allows('unit-edit') || Gate::allows('unit-delete')) {
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
        return 'Units_' . date('YmdHis');
    }
}
