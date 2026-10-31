<?php

namespace App\DataTables\Admin\Report\Stock;

use App\Models\StockLedger;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class StockReportDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('product_name', function ($row) {
                return $row->product->name ?? '';
            })
            ->addColumn('category_name', function ($row) {
                return $row->product && $row->product->category ? $row->product->category->name : '';
            })
            ->addColumn('outlet_name', function ($row) {
                $outlet = $row->outlet;
                $name = $outlet->name ?? 'Central Warehouse';
                $isCentral = (bool) ($outlet->is_central ?? false);
                if ($isCentral) {
                    return '<span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="ri-building-4-line me-1"></i>' . e($name) . ' <small>(Central)</small></span>';
                }
                return '<span class="badge bg-info-subtle text-info border border-info-subtle"><i class="ri-store-2-line me-1"></i>' . e($name) . '</span>';
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

                return $createdByUser->name ?? '';
            })
            ->editColumn('movement_type', function ($row) {
                return ucwords(str_replace('_', ' ', (string) $row->movement_type));
            })
            ->editColumn('movement_at', function ($row) {
                return $row->movement_at ? Carbon::parse($row->movement_at)->format('d-m-Y') : '';
            })
            ->editColumn('qty_in', function ($row) {
                $qty = (float) $row->qty_in;
                if ($qty <= 0) return '<span class="text-muted">0</span>';
                $product = $row->product;
                if ($product && $product->has_sub_unit && ($product->pieces_per_pack ?: 1) > 1) {
                    $formatted = $product->formatQuantity($qty);
                    $subName = $product->subUnit?->name ?? 'pcs';
                    $totalPieces = (float) $qty == (int) $qty ? (int) $qty : number_format($qty, 2);
                    return '<strong class="text-success">' . e($formatted) . '</strong><br><small class="text-muted">(' . $totalPieces . ' total ' . e($subName) . ')</small>';
                }
                return '<strong class="text-success">' . ($product ? e($product->formatQuantity($qty)) : number_format($qty, 2)) . '</strong>';
            })
            ->editColumn('qty_out', function ($row) {
                $qty = (float) $row->qty_out;
                if ($qty <= 0) return '<span class="text-muted">0</span>';
                $product = $row->product;
                if ($product && $product->has_sub_unit && ($product->pieces_per_pack ?: 1) > 1) {
                    $formatted = $product->formatQuantity($qty);
                    $subName = $product->subUnit?->name ?? 'pcs';
                    $totalPieces = (float) $qty == (int) $qty ? (int) $qty : number_format($qty, 2);
                    return '<strong class="text-danger">' . e($formatted) . '</strong><br><small class="text-muted">(' . $totalPieces . ' total ' . e($subName) . ')</small>';
                }
                return '<strong class="text-danger">' . ($product ? e($product->formatQuantity($qty)) : number_format($qty, 2)) . '</strong>';
            })
            ->editColumn('delta_qty', function ($row) {
                $delta = (float) $row->delta_qty;
                if ($delta == 0) return '<span class="text-muted">0</span>';
                $product = $row->product;
                $sign = $delta > 0 ? '+' : '';
                $colorClass = $delta > 0 ? 'text-success' : 'text-danger';
                if ($product && $product->has_sub_unit && ($product->pieces_per_pack ?: 1) > 1) {
                    $formatted = $product->formatQuantity($delta);
                    $subName = $product->subUnit?->name ?? 'pcs';
                    $totalPieces = (float) $delta == (int) $delta ? (int) $delta : number_format($delta, 2);
                    return '<strong class="' . $colorClass . '">' . ($delta > 0 ? '+' : '') . e($formatted) . '</strong><br><small class="text-muted">(' . ($delta > 0 ? '+' : '') . $totalPieces . ' ' . e($subName) . ')</small>';
                }
                $formatted = $product ? $product->formatQuantity(abs($delta)) : number_format(abs($delta), 2);
                return '<strong class="' . $colorClass . '">' . $sign . e($formatted) . '</strong>';
            })
            ->editColumn('balance_before', function ($row) {
                $bal = (float) $row->balance_before;
                $product = $row->product;
                if ($product && $product->has_sub_unit && ($product->pieces_per_pack ?: 1) > 1) {
                    $formatted = $product->formatQuantity($bal);
                    $subName = $product->subUnit?->name ?? 'pcs';
                    $totalPieces = (float) $bal == (int) $bal ? (int) $bal : number_format($bal, 2);
                    return '<strong>' . e($formatted) . '</strong><br><small class="text-muted">(' . $totalPieces . ' total ' . e($subName) . ')</small>';
                }
                return '<strong>' . ($product ? e($product->formatQuantity($bal)) : number_format($bal, 2)) . '</strong>';
            })
            ->editColumn('balance_after', function ($row) {
                $bal = (float) $row->balance_after;
                $product = $row->product;
                if ($product && $product->has_sub_unit && ($product->pieces_per_pack ?: 1) > 1) {
                    $formatted = $product->formatQuantity($bal);
                    $subName = $product->subUnit?->name ?? 'pcs';
                    $totalPieces = (float) $bal == (int) $bal ? (int) $bal : number_format($bal, 2);
                    return '<strong>' . e($formatted) . '</strong><br><small class="text-muted">(' . $totalPieces . ' total ' . e($subName) . ')</small>';
                }
                return '<strong>' . ($product ? e($product->formatQuantity($bal)) : number_format($bal, 2)) . '</strong>';
            })
            ->rawColumns(['outlet_name', 'qty_in', 'qty_out', 'delta_qty', 'balance_before', 'balance_after'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(StockLedger $model): QueryBuilder
    {
        $user = auth()->user();
        $outletId = ($user && $user->outlet_id)
            ? (int) $user->outlet_id
            : ($this->request()->filled('outlet_id') ? (int) $this->request()->get('outlet_id') : null);

        $query = $model->newQuery()
            ->with(['product.category', 'product.unit', 'product.subUnit', 'outlet', 'createdBy'])
            ->orderBy('movement_at', 'desc')
            ->orderBy('id', 'desc');

        if ($outletId) {
            $query->where('outlet_id', $outletId);
        }

        if ($productId = $this->request()->get('product_id')) {
            $query->where('product_id', $productId);
        }

        if ($categoryId = $this->request()->get('category_id')) {
            $query->whereHas('product', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
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
            ->setTableId('stockreport-table')
            ->columns($this->getColumns())
            ->minifiedAjax(request()->getRequestUri())
            ->orderBy(0, 'desc')
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

                var qtyInTotal = api.column(5, { page: "current" }).data().reduce(function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0);

                var qtyOutTotal = api.column(6, { page: "current" }).data().reduce(function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0);

                var deltaTotal = api.column(7, { page: "current" }).data().reduce(function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0);

                var formatValue = function (value) {
                    return Number(value).toLocaleString("en-US", {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                };

                $(api.column(4).footer()).html("Page Totals");
                $(api.column(5).footer()).html(formatValue(qtyInTotal));
                $(api.column(6).footer()).html(formatValue(qtyOutTotal));
                $(api.column(7).footer()).html(formatValue(deltaTotal));
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
        $columns = [
            Column::make('movement_at')->title('Date'),
            Column::make('product_name')->title('Product')->searchable(false)->orderable(false),
            Column::make('category_name')->title('Category')->searchable(false)->orderable(false),
            Column::make('outlet_name')->title('Outlet / Branch')->searchable(false)->orderable(false),
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

        if (Gate::allows('user-audit-view')) {
            $columns[] = Column::make('created_by_name')->title('Created By')->searchable(false)->orderable(false);
        }

        return $columns;
    }

    protected function filename(): string
    {
        return 'StockLedger_' . date('YmdHis');
    }
}
