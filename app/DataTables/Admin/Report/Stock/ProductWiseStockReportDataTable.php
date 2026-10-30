<?php

namespace App\DataTables\Admin\Report\Stock;

use App\Models\StockLedger;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class ProductWiseStockReportDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('product_name', function ($row) {
                return $row->product->name ?? '';
            })
            ->addColumn('outlet_name', function ($row) {
                if (! $row->outlet) {
                    return '<span class="badge bg-secondary">N/A</span>';
                }
                $badgeClass = $row->outlet->is_central ? 'bg-primary' : 'bg-info';
                return '<span class="badge ' . $badgeClass . '">' . e($row->outlet->name) . '</span>';
            })
            ->addColumn('created_by_name', function ($row) {
                return $row->createdBy->name ?? '';
            })
            ->editColumn('movement_type', function ($row) {
                return ucwords(str_replace('_', ' ', (string) $row->movement_type));
            })
            ->editColumn('movement_at', function ($row) {
                return $row->movement_at ? Carbon::parse($row->movement_at)->format('d-m-Y') : '';
            })
            ->editColumn('qty_in', function ($row) {
                return number_format((float) $row->qty_in, 2);
            })
            ->editColumn('qty_out', function ($row) {
                return number_format((float) $row->qty_out, 2);
            })
            ->editColumn('delta_qty', function ($row) {
                return number_format((float) $row->delta_qty, 2);
            })
            ->editColumn('balance_before', function ($row) {
                return number_format((float) $row->balance_before, 2);
            })
            ->editColumn('balance_after', function ($row) {
                return number_format((float) $row->balance_after, 2);
            })
            ->rawColumns(['outlet_name'])
            ->setRowId('id');
    }

    public function query(StockLedger $model): QueryBuilder
    {
        $user = auth()->user();
        $outletId = ($user && $user->outlet_id)
            ? (int) $user->outlet_id
            : ($this->request()->filled('outlet_id') ? (int) $this->request()->get('outlet_id') : null);

        $query = $model->newQuery()
            ->with(['product', 'outlet', 'createdBy'])
            ->orderBy('movement_at', 'desc')
            ->orderBy('id', 'desc');

        $productId = $this->request()->get('product_id');
        if (! $productId) {
            return $query->whereRaw('1 = 0');
        }

        $query->where('product_id', $productId);

        if ($outletId) {
            $query->where('outlet_id', $outletId);
        }

        if ($movementType = $this->request()->get('movement_type')) {
            $query->where('movement_type', $movementType);
        }

        if ($createdBy = $this->request()->get('created_by')) {
            $query->where('created_by', $createdBy);
        }

        if ($fromDate = $this->request()->get('from_date')) {
            $query->whereDate('movement_at', '>=', $fromDate);
        }

        if ($toDate = $this->request()->get('to_date')) {
            $query->whereDate('movement_at', '<=', $toDate);
        }

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('productwisestockreport-table')
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

                var qtyInTotal = api.column(4, { page: "current" }).data().reduce(function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0);

                var qtyOutTotal = api.column(5, { page: "current" }).data().reduce(function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0);

                var deltaTotal = api.column(6, { page: "current" }).data().reduce(function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0);

                var formatValue = function (value) {
                    return Number(value).toLocaleString("en-US", {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                };

                $(api.column(3).footer()).html("Page Totals");
                $(api.column(4).footer()).html(formatValue(qtyInTotal));
                $(api.column(5).footer()).html(formatValue(qtyOutTotal));
                $(api.column(6).footer()).html(formatValue(deltaTotal));
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
            Column::make('movement_at')->title('Date'),
            Column::make('product_name')->title('Product')->searchable(false)->orderable(false),
            Column::make('outlet_name')->title('Outlet / Location')->searchable(false)->orderable(false),
            Column::make('movement_type')->title('Type'),
            Column::make('qty_in')->title('In'),
            Column::make('qty_out')->title('Out'),
            Column::make('delta_qty')->title('Delta Qty'),
            Column::make('balance_before')->title('Opening'),
            Column::make('balance_after')->title('Closing'),
            Column::make('reference_type')->title('Reference Type'),
            Column::make('reason')->title('Reason'),
            Column::make('notes')->title('Notes'),
        ];
    }

    protected function filename(): string
    {
        return 'ProductWiseStockTrail_' . date('YmdHis');
    }
}
