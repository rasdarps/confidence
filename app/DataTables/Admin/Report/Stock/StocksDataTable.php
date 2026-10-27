<?php

namespace App\DataTables\Admin\Report\Stock;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class StocksDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', 'stocks.action')

            ->rawColumns(['action'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Product $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->with(['category', 'unit'])
            ->orderBy('products.name', 'asc');

        if ($categoryId = $this->request()->get('category_id')) {
            $query->where('products.category_id', $categoryId);
        }

        if ($unitId = $this->request()->get('unit_id')) {
            $query->where('products.unit_id', $unitId);
        }

        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('stocks-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax(request()->getRequestUri())
                    //->dom('Bfrtip')
                    ->orderBy(1)
                    ->selectStyleSingle()
                    ->footerCallback('function (row, data, start, end, display) {
                        var api = this.api();

                        var intVal = function (value) {
                            if (typeof value === "string") {
                                var textValue = $("<div>").html(value).text().trim();
                                var normalized = textValue.replace(/,/g, "");
                                var parsed = parseFloat(normalized);
                                return isNaN(parsed) ? 0 : parsed;
                            }

                            if (typeof value === "number") {
                                return value;
                            }

                            return 0;
                        };

                        var totalCurrentStock = api.column(3, { page: "current" }).data().reduce(function (a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);

                        var formatValue = function (value) {
                            return Number(value).toLocaleString("en-US", {
                                minimumFractionDigits: 0,
                                maximumFractionDigits: 0
                            });
                        };

                        $(api.column(2).footer()).html("Current Stock Total");
                        $(api.column(3).footer()).html(formatValue(totalCurrentStock));
                    }')
                    ->parameters([
                        'dom'          => "<'row'l>Bfrtip",  //row counter button
                        'buttons'      => [
                            'colvis',
                            ['extend' => 'excel', 'footer' => true],
                            ['extend' => 'csv', 'footer' => true],
                            'reset',
                            'reload',
                        ],
                    ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
           Column::make('name')->title('Product Name'),
            Column::make('category.name')->title('Category'),
            Column::make('unit.name')->title('Unit'),
            Column::make('quantity')->title('Current Stock'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Stocks_' . date('YmdHis');
    }
}
