<?php

namespace App\DataTables\Admin\Report\Purchase;

use App\Models\Outlet;
use App\Models\PurchaseDetail;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class PurchaseLinesReportDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        $centralName = Outlet::where('is_central', true)->value('name') ?? 'Central Warehouse';

        return (new EloquentDataTable($query))
            ->editColumn('purchase_date', function ($row) {
                return $row->purchase_date ? Carbon::parse($row->purchase_date)->format('d-m-Y') : 'N/A';
            })
            ->addColumn('receiving_location', function () use ($centralName) {
                return '<span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="ri-building-4-line me-1"></i>' . e($centralName) . ' <small>(Central)</small></span>';
            })
            ->editColumn('buying_qty', function ($row) {
                return number_format((float) $row->buying_qty, 2);
            })
            ->editColumn('unit_cost_price', function ($row) {
                return number_format((float) $row->unit_cost_price, 2);
            })
            ->editColumn('buying_price', function ($row) {
                return number_format((float) $row->buying_price, 2);
            })
            ->rawColumns(['receiving_location'])
            ->setRowId('id');
    }

    public function query(PurchaseDetail $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->leftJoin('purchases', 'purchase_details.purchase_id', '=', 'purchases.id')
            ->leftJoin('purchase_payments', 'purchase_payments.purchase_id', '=', 'purchases.id')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'purchase_payments.supplier_id')
            ->leftJoin('products', 'products.id', '=', 'purchase_details.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'purchase_details.category_id')
            ->select([
                'purchase_details.id',
                'purchase_details.purchase_id',
                'purchase_details.product_id',
                'purchase_details.category_id',
                'purchase_details.buying_qty',
                'purchase_details.unit_cost_price',
                'purchase_details.buying_price',
                'purchases.purchase_no',
                'purchases.date as purchase_date',
                'suppliers.name as supplier_name',
                'products.name as product_name',
                'categories.name as category_name',
            ])
            ->orderByDesc('purchases.date')
            ->orderByDesc('purchase_details.id');

        if ($purchaseId = $this->request()->get('purchase_id')) {
            $query->where('purchase_details.purchase_id', $purchaseId);
        }

        if ($supplierId = $this->request()->get('supplier_id')) {
            $query->where('purchase_payments.supplier_id', $supplierId);
        }

        if ($productId = $this->request()->get('product_id')) {
            $query->where('purchase_details.product_id', $productId);
        }

        if ($categoryId = $this->request()->get('category_id')) {
            $query->where('purchase_details.category_id', $categoryId);
        }

        if ($fromDate = $this->request()->get('from_date')) {
            $query->whereDate('purchases.date', '>=', $fromDate);
        }

        if ($toDate = $this->request()->get('to_date')) {
            $query->whereDate('purchases.date', '<=', $toDate);
        }

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('purchase-lines-report-table')
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
                    return (api.settings()[0].aoColumns[idx] || {}).data === "buying_price";
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
            Column::make('purchase_date')->title('Date'),
            Column::make('purchase_no')->title('Purchase No'),
            Column::computed('receiving_location')->title('Receiving Location')->searchable(false)->orderable(false),
            Column::make('supplier_name')->title('Supplier'),
            Column::make('category_name')->title('Category'),
            Column::make('product_name')->title('Product'),
            Column::make('buying_qty')->title('Qty')->searchable(false),
            Column::make('unit_cost_price')->title('Unit Cost (Gh¢)')->searchable(false),
            Column::make('buying_price')->title('Line Amount (Gh¢)')->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'PurchaseLinesReport_' . date('YmdHis');
    }
}
