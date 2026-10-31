<?php

namespace App\DataTables\Admin\Report\Stock;

use App\Models\Outlet;
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
            ->rawColumns(['outlet_location', 'quantity'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Product $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->with(['category', 'unit', 'subUnit', 'outletStocks'])
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
                    ->orderBy(0, 'asc')
                    ->selectStyleSingle()
                    ->footerCallback('function (row, data, start, end, display) {
                        var api = this.api();

                        var intVal = function (value) {
                            if (typeof value === "string") {
                                var match = value.match(/\(([\d,.]+)\s+total/);
                                if (match) {
                                    return parseFloat(match[1].replace(/,/g, "")) || 0;
                                }
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

                        var totalCurrentStock = api.column(4, { page: "current" }).data().reduce(function (a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);

                        var formatValue = function (value) {
                            return Number(value).toLocaleString("en-US", {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        };

                        $(api.column(3).footer()).html("Current Stock Total");
                        $(api.column(4).footer()).html(formatValue(totalCurrentStock));
                    }')
                    ->parameters([
                        'dom'          => "<'row'l>Bfrtip",
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
            Column::make('outlet_location')->title('Outlet / Location')->searchable(false)->orderable(false),
            Column::make('quantity')->title('Current Stock')->searchable(false)->orderable(false),
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
