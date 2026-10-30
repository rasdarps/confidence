<?php

namespace App\DataTables\Admin\Report\Sales;

use App\Models\PaymentDetail;
use App\Support\PaymentStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class SalesCreditMovementDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->editColumn('payment_date', function ($row) {
                return $row->payment_date ? Carbon::parse($row->payment_date)->format('d-m-Y') : 'N/A';
            })
            ->addColumn('outlet_name', function ($row) {
                $name = $row->outlet_name ?? 'Central Warehouse';
                $isCentral = (bool) ($row->outlet_is_central ?? false);
                if ($isCentral) {
                    return '<span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="ri-building-4-line me-1"></i>' . e($name) . ' <small>(Central)</small></span>';
                }
                return '<span class="badge bg-info-subtle text-info border border-info-subtle"><i class="ri-store-2-line me-1"></i>' . e($name) . '</span>';
            })
            ->editColumn('paid_status', function ($row) {
                return match ($row->paid_status) {
                    PaymentStatus::FULL_CREDIT => 'Full Credit',
                    PaymentStatus::PARTIAL_PAYMENT => 'Partial Payment',
                    default => ucfirst(str_replace('_', ' ', (string) $row->paid_status)),
                };
            })
            ->editColumn('total_amount', function ($row) {
                return number_format((float) $row->total_amount, 2);
            })
            ->editColumn('current_paid_amount', function ($row) {
                return number_format((float) $row->current_paid_amount, 2);
            })
            ->editColumn('due_amount', function ($row) {
                return number_format((float) $row->due_amount, 2);
            })
            ->rawColumns(['outlet_name'])
            ->setRowId('id');
    }

    public function query(PaymentDetail $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->from('payment_details')
            ->leftJoin('payments', 'payments.sale_id', '=', 'payment_details.sale_id')
            ->leftJoin('sales', 'sales.id', '=', 'payments.sale_id')
            ->leftJoin('outlets', 'outlets.id', '=', 'sales.outlet_id')
            ->leftJoin('customers', 'customers.id', '=', 'payments.customer_id')
            ->select([
                'payment_details.id',
                'payments.sale_id',
                'payments.customer_id',
                'payments.paid_status',
                'payments.total_amount',
                'payment_details.current_paid_amount',
                'payments.due_amount',
                'sales.sale_no',
                'sales.outlet_id',
                'outlets.name as outlet_name',
                'outlets.is_central as outlet_is_central',
                'payment_details.date as payment_date',
                'customers.name as customer_name',
            ])
            ->whereIn('payments.paid_status', [PaymentStatus::FULL_CREDIT, PaymentStatus::PARTIAL_PAYMENT])
            ->where('payments.due_amount', '>', 0)
            ->orderByDesc('payment_details.date')
            ->orderByDesc('payment_details.id');

        $outletId = (auth()->check() && auth()->user()->outlet_id)
            ? (int) auth()->user()->outlet_id
            : ($this->request()->filled('outlet_id') ? (int) $this->request()->get('outlet_id') : null);

        if ($outletId) {
            $query->where('sales.outlet_id', $outletId);
        }

        if ($saleId = $this->request()->get('sale_id')) {
            $query->where('payments.sale_id', $saleId);
        }

        if ($customerId = $this->request()->get('customer_id')) {
            $query->where('payments.customer_id', $customerId);
        }

        if ($status = $this->request()->get('paid_status')) {
            $normalizedStatus = PaymentStatus::normalize($status);

            if (in_array($normalizedStatus, [PaymentStatus::FULL_CREDIT, PaymentStatus::PARTIAL_PAYMENT], true)) {
                $query->where('payments.paid_status', $normalizedStatus);
            }
        }

        if ($fromDate = $this->request()->get('from_date')) {
            $query->whereDate('payment_details.date', '>=', $fromDate);
        }

        if ($toDate = $this->request()->get('to_date')) {
            $query->whereDate('payment_details.date', '<=', $toDate);
        }

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('sales-credit-movement-table')
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

                var paidColIndex = api.columns().indexes().toArray().find(function (idx) {
                    return (api.settings()[0].aoColumns[idx] || {}).data === "current_paid_amount";
                });

                if (typeof paidColIndex === "undefined") {
                    return;
                }

                var totalPaid = api.column(paidColIndex, { page: "current" }).data().reduce(function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0);

                var formatValue = function (value) {
                    return Number(value).toLocaleString("en-US", {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                };

                $(api.column(paidColIndex).footer()).html(formatValue(totalPaid));
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
            Column::make('payment_date')->title('Payment Date'),
            Column::make('sale_no')->title('Sale No'),
            Column::computed('outlet_name')->title('Outlet / Branch')->searchable(false)->orderable(false),
            Column::make('customer_name')->title('Customer'),
            Column::make('paid_status')->title('Credit Type'),
            Column::make('total_amount')->title('Invoice Amount (Gh¢)')->searchable(false),
            Column::make('current_paid_amount')->title('Current Paid Amount (Gh¢)')->searchable(false),
            Column::make('due_amount')->title('Due Amount (Gh¢)')->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'SalesCreditMovementReport_' . date('YmdHis');
    }
}
