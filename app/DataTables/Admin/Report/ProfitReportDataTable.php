<?php

namespace App\DataTables\Admin\Report;

use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class ProfitReportDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', 'profitreport.action')

            ->addColumn('customer_name', function($row) {
                return $row->payment && $row->payment->customer
                    ? $row->payment->customer->name
                    : '';
            })

            ->addColumn('created_by_name', function($row) {
                if (!Gate::allows('user-audit-view')) {
                    return '';
                }
                $viewer = auth()->user();
                $viewerIsSuperAdmin = $viewer && strtolower((string) ($viewer->user_type ?? '')) === 'super admin';
                $createdByUser = $row->createdBy;

                if (!$viewerIsSuperAdmin && $createdByUser && strtolower((string) ($createdByUser->user_type ?? '')) === 'super admin') {
                    return '';
                }

                return $createdByUser ? $createdByUser->name : '';
            })

            ->addColumn('cancelled_by_name', function($row) {
                if (!Gate::allows('user-audit-view')) {
                    return '';
                }
                $viewer = auth()->user();
                $viewerIsSuperAdmin = $viewer && strtolower((string) ($viewer->user_type ?? '')) === 'super admin';
                $cancelledByUser = $row->cancelledBy;

                if (!$viewerIsSuperAdmin && $cancelledByUser && strtolower((string) ($cancelledByUser->user_type ?? '')) === 'super admin') {
                    return '';
                }

                return $cancelledByUser ? $cancelledByUser->name : '';
            })

            ->addColumn('sales_total', function($row) {
                return number_format((float) ($row->sales_total ?? 0), 2);
            })

            ->addColumn('cost_total', function($row) {
                return number_format((float) ($row->cost_total ?? 0), 2);
            })

            ->addColumn('profit_total', function($row) {
                return number_format((float) ($row->profit_total ?? 0), 2);
            })

            ->addColumn('cancelled_status', function($row) {
                if ($row->cancelled_at) {
                    return '<span class="badge bg-danger" title="' . e($row->cancellation_reason) . '">Cancelled</span>';
                }

                return '<span class="badge bg-success">Active</span>';
            })

             ->editColumn('date', function($row)
            {
                return  Carbon::parse($row->date)->format('d-m-Y');
            })

              ->editColumn('created_at', function($row)
            {
                return  Carbon::parse($row->created_at)->format('d-m-Y');
            })
            ->editColumn('updated_at', function($row) {
                return $row->updated_at
                    ? Carbon::parse($row->updated_at)->format('d-m-Y')
                    : '';
            })

            
            ->rawColumns(['sales_total','cost_total','profit_total','cancelled_status'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Sale $model): QueryBuilder
    {
        $salesSub = DB::table('payments')
            ->selectRaw('sale_id, SUM(total_amount) as sales_total')
            ->groupBy('sale_id');

        $costSub = DB::table('sale_details as idt')
            ->selectRaw(
                'idt.sale_id, SUM(idt.selling_qty * COALESCE((
                    SELECT pd.unit_cost_price
                    FROM purchase_details pd
                    WHERE pd.product_id = idt.product_id
                    ORDER BY pd.date DESC, pd.id DESC
                    LIMIT 1
                ), 0)) as cost_total'
            )
            ->groupBy('idt.sale_id');

        $query = $model->newQuery()
            ->leftJoinSub($salesSub, 'sales_agg', function ($join) {
                $join->on('sales_agg.sale_id', '=', 'sales.id');
            })
            ->leftJoinSub($costSub, 'costs_agg', function ($join) {
                $join->on('costs_agg.sale_id', '=', 'sales.id');
            })
            ->select(
                'sales.*',
                DB::raw('COALESCE(sales_agg.sales_total, 0) as sales_total'),
                DB::raw('COALESCE(costs_agg.cost_total, 0) as cost_total'),
                DB::raw('(COALESCE(sales_agg.sales_total, 0) - COALESCE(costs_agg.cost_total, 0)) as profit_total')
            )
            ->with(['payment.customer', 'createdBy', 'cancelledBy'])
            ->orderBy('sales.date', 'desc');

            // Apply filters
        if ($invoiceId = $this->request()->get('sale_id')) {
            $query->where('sales.id', $invoiceId);
        }

        if ($customerId = $this->request()->get('customer_id')) {
            $query->whereHas('payment.customer', function ($q) use ($customerId) {
                $q->where('id', $customerId);
            });
        }

        if ($createdBy = $this->request()->get('created_by')) {
            $query->where('sales.created_by', $createdBy);
        }

        if ($fromDate = $this->request()->get('from_date')) {
            $query->whereDate('sales.date', '>=', $fromDate);
        }

        if ($toDate = $this->request()->get('to_date')) {
            $query->whereDate('sales.date', '<=', $toDate);
        }

        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('profitreport-table')
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

                        var resolveIndex = function (columnDataName) {
                            return api.columns().indexes().toArray().find(function (idx) {
                                return (api.settings()[0].aoColumns[idx] || {}).data === columnDataName;
                            });
                        };

                        var salesColIndex = resolveIndex("sales_total");
                        var costColIndex = resolveIndex("cost_total");
                        var profitColIndex = resolveIndex("profit_total");

                        if (typeof salesColIndex === "undefined" || typeof costColIndex === "undefined" || typeof profitColIndex === "undefined") {
                            return;
                        }

                        var totalSales = api.column(salesColIndex, { page: "current" }).data().reduce(function (a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);

                        var totalCost = api.column(costColIndex, { page: "current" }).data().reduce(function (a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);

                        var totalProfit = api.column(profitColIndex, { page: "current" }).data().reduce(function (a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);

                        var formatValue = function (value) {
                            return Number(value).toLocaleString("en-US", {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        };

                        $(api.column(salesColIndex).footer()).html(formatValue(totalSales));
                        $(api.column(costColIndex).footer()).html(formatValue(totalCost));
                        $(api.column(profitColIndex).footer()).html(formatValue(totalProfit));
                    }')
                    ->parameters([
                        'dom'          => "<'row'l>Bfrtip",  //row counter button
                        'buttons'      => [
                            'colvis',
                            ['extend' => 'excel', 'footer' => true],
                            ['extend' => 'csv', 'footer' => true],
                            'reset',
                            'reload'
                        ],
                    ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        $columns = [
            Column::make('date'),
            Column::make('sale_no'),
            Column::make('customer_name')->title('Customer Name'),
            Column::make('sales_total')->title('Net Sales (Gh¢)'),
            Column::make('cost_total')->title('Cost (Gh¢)'),
            Column::make('profit_total')->title('Profit (Gh¢)'),
            Column::make('cancelled_status')->title('Status')
                ->searchable(true)->printable(true)->exportable(true),
        ];

        if (Gate::allows('user-audit-view')) {
            $columns[] = Column::make('created_by_name')->title('Created By');
            $columns[] = Column::make('cancelled_by_name')->title('Cancelled By')
                ->searchable(true)->printable(true)->exportable(true);
        }

        return $columns;
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'ProfitReport_' . date('YmdHis');
    }
}

