<?php

namespace App\DataTables\Admin\Report\Stock;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class StockValueReportDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('cost_value', function ($row) {
                return number_format((float) $row->quantity * (float) $row->cost_price, 2);
            })
            ->addColumn('sell_value', function ($row) {
                return number_format((float) $row->quantity * (float) $row->selling_price, 2);
            })
            ->addColumn('profit', function ($row) {
                $cost_value = (float) $row->quantity * (float) $row->cost_price;
                $sell_value = (float) $row->quantity * (float) $row->selling_price;
                return number_format($sell_value - $cost_value, 2);
            })
            ->rawColumns(['cost_value', 'sell_value', 'profit'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Product $model): QueryBuilder
    {
        return $model->newQuery()
            ->select('id', 'name', 'quantity', 'cost_price', 'selling_price')
            ->orderBy('name', 'asc');
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('stockvaluereport-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax(request()->getRequestUri())
                    //->dom('Bfrtip')
                    ->orderBy(1)
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

                        var totalQty = api.column(1, { page: "current" }).data().reduce(function (a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);

                        var totalCostValue = api.column(3, { page: "current" }).data().reduce(function (a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);

                        var totalSellValue = api.column(5, { page: "current" }).data().reduce(function (a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);

                        var totalProfit = api.column(6, { page: "current" }).data().reduce(function (a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);

                        var formatValue = function (value) {
                            return Number(value).toLocaleString("en-US", {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        };

                        $(api.column(0).footer()).html("Totals");
                        $(api.column(1).footer()).html(formatValue(totalQty));
                        $(api.column(3).footer()).html(formatValue(totalCostValue));
                        $(api.column(5).footer()).html(formatValue(totalSellValue));
                        $(api.column(6).footer()).html(formatValue(totalProfit));
                    }')
                    ->parameters([
                        'dom'          => "<'row'l>Bfrtip",  //row counter button
                        'buttons'      => [
                            ['extend' => 'colvis'],
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
            Column::make('quantity')->title('Current Stock'),
            Column::make('cost_price')->title('Cost Price'),
            Column::make('cost_value')->title('Cost Value'),
            Column::make('selling_price')->title('Selling Price'),
            Column::make('sell_value')->title('Selling Value'),
            Column::make('profit')->title('Profit')

            
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'StockValueReport_' . date('YmdHis');
    }
}
