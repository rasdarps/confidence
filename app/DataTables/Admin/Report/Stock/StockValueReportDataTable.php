<?php

namespace App\DataTables\Admin\Report\Stock;

use App\Models\Outlet;
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
        $user = auth()->user();
        $outletId = ($user && $user->outlet_id)
            ? (int) $user->outlet_id
            : ($this->request()->filled('outlet_id') ? (int) $this->request()->get('outlet_id') : null);

        $selectedOutlet = $outletId ? Outlet::find($outletId) : null;

        return (new EloquentDataTable($query))
            ->addColumn('outlet_location', function () use ($outletId, $selectedOutlet) {
                if ($outletId && $selectedOutlet) {
                    $badgeClass = $selectedOutlet->is_central ? 'bg-primary' : 'bg-info';
                    return '<span class="badge ' . $badgeClass . '">' . e($selectedOutlet->name) . '</span>';
                }

                return '<span class="badge bg-secondary">All Locations (Combined)</span>';
            })
            ->addColumn('quantity', function ($row) use ($outletId) {
                $qty = $outletId ? (float) $row->quantityForOutlet($outletId) : (float) $row->quantity;
                if ($row->has_sub_unit && ($row->pieces_per_pack ?: 1) > 1) {
                    $formatted = $row->formatQuantity($qty);
                    $subName = $row->subUnit?->name ?? 'pcs';
                    $totalPieces = (float) $qty == (int) $qty ? (int) $qty : number_format($qty, 2);
                    return '<strong>' . e($formatted) . '</strong><br><small class="text-muted">(' . $totalPieces . ' total ' . e($subName) . ')</small>';
                }
                return '<strong>' . e($row->formatQuantity($qty)) . '</strong>';
            })
            ->editColumn('cost_price', function ($row) {
                $cost = (float) ($row->cost_price ?? 0);
                if ($row->has_sub_unit && ($row->pieces_per_pack ?: 1) > 1) {
                    $unitCost = (float) ($row->sub_unit_cost_price ?: ($cost / $row->pieces_per_pack));
                    $subName = $row->subUnit?->name ?? 'pc';
                    return '₵' . number_format($cost, 2) . '<br><small class="text-muted">(₵' . number_format($unitCost, 2) . '/' . e($subName) . ')</small>';
                }
                return '₵' . number_format($cost, 2);
            })
            ->addColumn('cost_value', function ($row) use ($outletId) {
                $qty = $outletId ? (float) $row->quantityForOutlet($outletId) : (float) $row->quantity;
                $unitCost = ($row->has_sub_unit && ($row->pieces_per_pack ?: 1) > 1)
                    ? (float) ($row->sub_unit_cost_price ?: ($row->cost_price / $row->pieces_per_pack))
                    : (float) $row->cost_price;
                return number_format($qty * $unitCost, 2);
            })
            ->editColumn('selling_price', function ($row) {
                $sell = (float) ($row->selling_price ?? 0);
                if ($row->has_sub_unit && ($row->pieces_per_pack ?: 1) > 1) {
                    $unitSell = (float) ($row->sub_unit_selling_price ?: ($sell / $row->pieces_per_pack));
                    $subName = $row->subUnit?->name ?? 'pc';
                    return '₵' . number_format($sell, 2) . '<br><small class="text-muted">(₵' . number_format($unitSell, 2) . '/' . e($subName) . ')</small>';
                }
                return '₵' . number_format($sell, 2);
            })
            ->addColumn('sell_value', function ($row) use ($outletId) {
                $qty = $outletId ? (float) $row->quantityForOutlet($outletId) : (float) $row->quantity;
                $unitSell = ($row->has_sub_unit && ($row->pieces_per_pack ?: 1) > 1)
                    ? (float) ($row->sub_unit_selling_price ?: ($row->selling_price / $row->pieces_per_pack))
                    : (float) $row->selling_price;
                return number_format($qty * $unitSell, 2);
            })
            ->addColumn('profit', function ($row) use ($outletId) {
                $qty = $outletId ? (float) $row->quantityForOutlet($outletId) : (float) $row->quantity;
                $unitCost = ($row->has_sub_unit && ($row->pieces_per_pack ?: 1) > 1)
                    ? (float) ($row->sub_unit_cost_price ?: ($row->cost_price / $row->pieces_per_pack))
                    : (float) $row->cost_price;
                $unitSell = ($row->has_sub_unit && ($row->pieces_per_pack ?: 1) > 1)
                    ? (float) ($row->sub_unit_selling_price ?: ($row->selling_price / $row->pieces_per_pack))
                    : (float) $row->selling_price;
                $cost_value = $qty * $unitCost;
                $sell_value = $qty * $unitSell;
                return number_format($sell_value - $cost_value, 2);
            })
            ->rawColumns(['outlet_location', 'quantity', 'cost_price', 'cost_value', 'selling_price', 'sell_value', 'profit'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Product $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->with(['outletStocks', 'unit', 'subUnit'])
            ->orderBy('name', 'asc');

        if ($productId = $this->request()->get('product_id')) {
            $query->where('products.id', $productId);
        } elseif ($productName = $this->request()->get('product_name')) {
            $query->where('products.name', $productName);
        }

        return $query;
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
                    ->orderBy(0)
                    ->selectStyleSingle()
                    ->footerCallback('function (row, data, start, end, display) {
                        var api = this.api();

                        var intVal = function (value) {
                            if (typeof value === "string") {
                                var match = value.match(/\(([\d,.]+)\s+total/);
                                if (match) {
                                    return parseFloat(match[1].replace(/,/g, "")) || 0;
                                }
                                return value.replace(/[^0-9.-]/g, "") * 1;
                            }

                            if (typeof value === "number") {
                                return value;
                            }

                            return 0;
                        };

                        var totalQty = api.column(2, { page: "current" }).data().reduce(function (a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);

                        var totalCostValue = api.column(4, { page: "current" }).data().reduce(function (a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);

                        var totalSellValue = api.column(6, { page: "current" }).data().reduce(function (a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);

                        var totalProfit = api.column(7, { page: "current" }).data().reduce(function (a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);

                        var formatValue = function (value) {
                            return Number(value).toLocaleString("en-US", {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        };

                        $(api.column(1).footer()).html("Totals");
                        $(api.column(2).footer()).html(formatValue(totalQty));
                        $(api.column(4).footer()).html(formatValue(totalCostValue));
                        $(api.column(6).footer()).html(formatValue(totalSellValue));
                        $(api.column(7).footer()).html(formatValue(totalProfit));
                    }')
                    ->parameters([
                        'dom'          => "<'row'l>Bfrtip",
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
            Column::make('outlet_location')->title('Outlet / Location')->searchable(false)->orderable(false),
            Column::make('quantity')->title('Current Stock')->searchable(false)->orderable(false),
            Column::make('cost_price')->title('Cost Price'),
            Column::make('cost_value')->title('Cost Value')->searchable(false)->orderable(false),
            Column::make('selling_price')->title('Selling Price'),
            Column::make('sell_value')->title('Selling Value')->searchable(false)->orderable(false),
            Column::make('profit')->title('Profit')->searchable(false)->orderable(false),
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
