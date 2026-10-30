<?php

namespace App\DataTables\Admin\Settings;

use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class ProductPriceDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', 'productprice.action')
            ->addColumn('scope', function () {
                return '<span class="badge badge-soft-info" style="color:#0F766E; background-color:#E6FFFA; border:1px solid #B2F5EA;"><i class="fas fa-building me-1"></i> Enterprise (Central)</span>';
            })
            ->addColumn('created_by_name', function ($row) {
                if (!Gate::allows('user-audit-view')) {
                    return '';
                }
                $viewer = auth()->user();
                $viewerIsSuperAdmin = $viewer && strtolower((string) ($viewer->user_type ?? '')) === 'super admin';
                $createdByUser = $row->createdBy;

                if (!$viewerIsSuperAdmin && $createdByUser && strtolower((string) ($createdByUser->user_type ?? '')) === 'super admin') {
                    return '';
                }

                return optional($createdByUser)->name ?? 'N/A';
            })
            ->addColumn('edit_button', function($row) {
                $user = auth()->user();
                if (!$user || !$user->isCentral()) {
                    return '<span class="badge bg-secondary text-white" title="Master prices managed centrally by HQ"><i class="fas fa-lock me-1"></i> Central Managed</span>';
                }

                if (!Gate::allows('product-price-edit')) {
                    return '<span class="text-muted">No actions</span>';
                }

                $editUrl = route('product-prices.edit', Crypt::encrypt($row->id));
                $csrf = csrf_field();

                return '
                    <form class="d-inline-block m-0" action="'.$editUrl.'" method="GET">
                        '.$csrf.'
                        <button style="border:none; background:#0F766E; cursor:pointer; display:inline-flex; align-items:center; gap:6px; width:auto;" class="table-edit-btn btn btn-sm btn-success text-nowrap" title="Edit Master Price">
                            <i class="fas fa-edit" style="color:#fff; justify-content:center;"></i>
                        </button>
                    </form>
                ';
            })
            ->editColumn('cost_price', function($row) {
                return number_format((float) ($row->cost_price ?? 0), 2);
            })
            ->editColumn('selling_price', function($row) {
                return number_format((float) ($row->selling_price ?? 0), 2);
            })
            ->editColumn('created_at', function($row)
            {
                return Carbon::parse($row->created_at)->format('jS F, Y g:i A');
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
    public function query(Product $model): QueryBuilder
    {
        return $model->newQuery()
            ->with(['createdBy'])
            ->latest('created_at');
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('productprice-table')
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
            Column::make('name')->title('Product Name'),
            Column::make('cost_price')->title('Cost Price'),
            Column::make('selling_price')->title('Selling Price'),
            Column::make('scope')->title('Catalog Scope')->searchable(false)->orderable(false),
            Column::make('created_at')->title('Created At'),
        ];

        if (Gate::allows('user-audit-view')) {
            array_splice($columns, 4, 0, [Column::make('created_by_name')->title('Created By')]);
        }

        if (Gate::allows('product-price-edit')) {
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
        return 'ProductPrice_' . date('YmdHis');
    }
}
