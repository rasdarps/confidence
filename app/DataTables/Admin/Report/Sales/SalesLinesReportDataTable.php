<?php

namespace App\DataTables\Admin\Report\Sales;

use App\Models\SaleDetail;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class SalesLinesReportDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->editColumn('sale_date', function ($row) {
                return $row->sale_date ? Carbon::parse($row->sale_date)->format('d-m-Y') : 'N/A';
            })
            ->editColumn('selling_qty', function ($row) {
                return number_format((float) $row->selling_qty, 2);
            })
            ->editColumn('unit_selling_price', function ($row) {
                return number_format((float) $row->unit_selling_price, 2);
            })
            ->editColumn('selling_price', function ($row) {
                return number_format((float) $row->selling_price, 2);
            })
            ->setRowId('id');
    }

    public function query(SaleDetail $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->leftJoin('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->leftJoin('payments', 'payments.sale_id', '=', 'sales.id')
            ->leftJoin('customers', 'customers.id', '=', 'payments.customer_id')
            ->leftJoin('products', 'products.id', '=', 'sale_details.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'sale_details.category_id')
            ->select([
                'sale_details.id',
                'sale_details.sale_id',
                'sale_details.product_id',
                'sale_details.category_id',
                'sale_details.selling_qty',
                'sale_details.unit_selling_price',
                'sale_details.selling_price',
                'sales.sale_no',
                'sales.date as sale_date',
                'customers.name as customer_name',
                'products.name as product_name',
                'categories.name as category_name',
            ])
            ->orderByDesc('sales.date')
            ->orderByDesc('sale_details.id');

        if ($saleId = $this->request()->get('sale_id')) {
            $query->where('sale_details.sale_id', $saleId);
        }

        if ($customerId = $this->request()->get('customer_id')) {
            $query->where('payments.customer_id', $customerId);
        }

        if ($productId = $this->request()->get('product_id')) {
            $query->where('sale_details.product_id', $productId);
        }

        if ($categoryId = $this->request()->get('category_id')) {
            $query->where('sale_details.category_id', $categoryId);
        }

        if ($fromDate = $this->request()->get('from_date')) {
            $query->whereDate('sales.date', '>=', $fromDate);
        }

        if ($toDate = $this->request()->get('to_date')) {
            $query->whereDate('sales.date', '<=', $toDate);
        }

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('sales-lines-report-table')
            ->columns($this->getColumns())
            ->minifiedAjax(request()->getRequestUri())
            ->orderBy(0, 'desc')
            ->selectStyleSingle()
            ->footerCallback('function (row, data, start, end, display) {
                var api = this.api();

                var intVal = function (value) {
                    if (typeof value === "string") {
                        return value.replace(/[^0-9.-]/g, "") * 1;
                    }

                    if (typeof value === "number") {
                        return value;
                    }

                    return 0;
                };

                var amountColIndex = api.columns().indexes().toArray().find(function (idx) {
                    return (api.settings()[0].aoColumns[idx] || {}).data === "selling_price";
                });

                if (typeof amountColIndex === "undefined") {
                    return;
                }

                var totalAmount = api.column(amountColIndex, { page: "current" }).data().reduce(function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0);

                var formatValue = function (value) {
                    return Number(value).toLocaleString("en-US", {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                };

                $(api.column(amountColIndex).footer()).html(formatValue(totalAmount));
            }')
            ->parameters([
                'dom' => "<'row'l>Bfrtip",
                'buttons' => [
                    'colvis',
                    ['extend' => 'excel', 'footer' => true],
                    ['extend' => 'csv', 'footer' => true],
                    'reset',
                    'reload',
                ],
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::make('sale_date')->title('Date'),
            Column::make('sale_no')->title('Sale No'),
            Column::make('customer_name')->title('Customer'),
            Column::make('category_name')->title('Category'),
            Column::make('product_name')->title('Product'),
            Column::make('selling_qty')->title('Qty')->searchable(false),
            Column::make('unit_selling_price')->title('Unit Price (Gh¢)')->searchable(false),
            Column::make('selling_price')->title('Line Amount (Gh¢)')->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'SalesLinesReport_' . date('YmdHis');
    }
}
