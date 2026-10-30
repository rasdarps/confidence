<?php

namespace App\DataTables\Admin\Settings;

use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class SupplierDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('scope', function($row) {
                if ((int) $row->id === 1 || strtolower((string) $row->name) === 'cash_purchase') {
                    return '<span class="badge bg-secondary"><i class="fas fa-lock me-1"></i> System Default</span>';
                }
                return '<span class="badge bg-primary">Central Vendor</span>';
            })
            ->addColumn('edit_button', function($row) {
                    $editUrl = route('suppliers.edit', Crypt::encrypt($row->id));
                    $deleteUrl = route('suppliers.destroy', Crypt::encrypt($row->id));
                    $csrf = csrf_field();
                    $method = method_field('DELETE');

                    $buttons = [];

                    $user = auth()->user();
                    $isCentral = $user ? $user->isCentral() : true;
                    $isCashPurchase = (int) $row->id === 1 || strtolower((string) $row->name) === 'cash_purchase';

                    if (Gate::allows('supplier-edit') && $isCentral) {
                        $buttons[] = '
                        <form class="d-inline-block m-0" action="'.$editUrl.'" method="GET">
                            '.$csrf.'
                            <button class="btn-action-edit table-edit-btn" title="Edit Supplier">
                                <i class="fas fa-edit"></i>
                            </button>
                        </form>';
                    }

                    if (Gate::allows('supplier-delete') && $isCentral && !$isCashPurchase) {
                        $buttons[] = '
                        <form class="d-inline-block m-0" action="'.$deleteUrl.'" method="POST">
                            '.$csrf.'
                            '.$method.'
                            <button type="button" class="btn-action-delete delete-confirm-btn btn-delete" title="Delete Supplier">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </form>';
                    }

                    if (empty($buttons)) {
                        return '<span class="text-muted small">Read Only</span>';
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

            ->rawColumns(['scope', 'edit_button'])

            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Supplier $model): QueryBuilder
    {
        return $model->newQuery()->orderBy('name', 'desc');
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('supplier-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax(request()->getRequestUri())
                    //->dom('Bfrtip')
                    ->orderBy(1)
                    ->selectStyleSingle()
                    ->parameters([
                        'dom'          => "<'row'l>Bfrtip",  //row counter button
                        'buttons'      => ['colvis', 'excel', 'csv', 'reset', 'reload'],
                    ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        $columns = [
             Column::make('name'),
             Column::make('scope')->title('Procurement Scope')->searchable(false),
             Column::make('mobile_no'),
             Column::make('email'),
             Column::make('city'),
             Column::make('address'),
             Column::make('type'),
            //  Column::make('created_at'),
            //  Column::make('updated_at'),
        ];

        if (Gate::allows('supplier-edit') || Gate::allows('supplier-delete')) {
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
        return 'Supplier_' . date('YmdHis');
    }
}
