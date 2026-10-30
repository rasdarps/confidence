<?php

namespace App\DataTables\Admin\Report;

use App\Models\ProductPrice;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class ProductPriceHistoryDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', 'productpricehistory.action')
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
            ->editColumn('cost_price', function ($row) {
                return number_format((float) $row->cost_price, 2);
            })
            ->editColumn('selling_price', function ($row) {
                return number_format((float) $row->selling_price, 2);
            })
            ->editColumn('created_at', function ($row) {
                return Carbon::parse($row->created_at)->format('d-m-Y');
            })
            ->editColumn('updated_at', function ($row) {
                return $row->updated_at
                    ? Carbon::parse($row->updated_at)->format('d-m-Y')
                    : '';
            })
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(ProductPrice $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->select('product_prices.*')
            ->with(['product', 'createdBy'])
            ->latest('created_at');

        if ($productId = $this->request()->get('product_id')) {
            $query->where('product_prices.product_id', $productId);
        }

        if ($createdBy = $this->request()->get('created_by')) {
            $query->where('product_prices.created_by', $createdBy);
        }

        if ($fromDate = $this->request()->get('from_date')) {
            $query->whereDate('product_prices.created_at', '>=', $fromDate);
        }

        if ($toDate = $this->request()->get('to_date')) {
            $query->whereDate('product_prices.created_at', '<=', $toDate);
        }

        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('productpricehistory-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax(request()->getRequestUri())
                    ->orderBy(0, 'desc')
                    ->selectStyleSingle()
                    ->parameters([
                        'dom'          => "<'row'l>Bfrtip",
                        'buttons'      => ['colvis', 'excel', 'csv', 'reset', 'reload'],
                    ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        $columns = [
            Column::make('created_at')->title('Date Created'),
            Column::make('product.name')->title('Product Name')->orderable(false),
            Column::make('cost_price')->title('Cost Price'),
            Column::make('selling_price')->title('Selling Price'),
        ];

        if (Gate::allows('user-audit-view')) {
            $columns[] = Column::make('created_by_name')->title('Created By')->searchable(false)->orderable(false);
        }

        return $columns;
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'ProductPriceHistory_' . date('YmdHis');
    }
}
