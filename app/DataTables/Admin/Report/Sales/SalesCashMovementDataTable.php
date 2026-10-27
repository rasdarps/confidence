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

class SalesCashMovementDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->editColumn('payment_date', function ($row) {
                return $row->payment_date ? Carbon::parse($row->payment_date)->format('d-m-Y') : 'N/A';
            })
            ->editColumn('paid_status', function ($row) {
                return match ($row->paid_status) {
                    PaymentStatus::FULL_PAYMENT => 'Full Payment',
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
            ->setRowId('id');
    }

    public function query(PaymentDetail $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->from('payment_details')
            ->leftJoin('payments', 'payments.sale_id', '=', 'payment_details.sale_id')
            ->leftJoin('sales', 'sales.id', '=', 'payments.sale_id')
            ->leftJoin('customers', 'customers.id', '=', 'payments.customer_id')
            ->select([
                'payment_details.id',
                'payment_details.sale_id',
                'payment_details.current_paid_amount',
                'payment_details.date as payment_date',
                'payments.customer_id',
                'payments.paid_status',
                'payments.total_amount',
                'payments.due_amount',
                'sales.sale_no',
                'customers.name as customer_name',
            ])
            ->whereIn('payments.paid_status', [PaymentStatus::FULL_PAYMENT, PaymentStatus::PARTIAL_PAYMENT])
            ->where('payment_details.current_paid_amount', '>', 0)
            ->orderByDesc('payment_details.date')
            ->orderByDesc('payment_details.id');

        if ($saleId = $this->request()->get('sale_id')) {
            $query->where('payment_details.sale_id', $saleId);
        }

        if ($customerId = $this->request()->get('customer_id')) {
            $query->where('payments.customer_id', $customerId);
        }

        if ($status = $this->request()->get('paid_status')) {
            $query->where('payments.paid_status', PaymentStatus::normalize($status));
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
            ->setTableId('sales-cash-movement-table')
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
            Column::make('customer_name')->title('Customer'),
            Column::make('paid_status')->title('Payment Type'),
            Column::make('total_amount')->title('Invoice Amount (Gh¢)')->searchable(false),
            Column::make('current_paid_amount')->title('Cash Amount (Gh¢)')->searchable(false),
            Column::make('due_amount')->title('Due Amount (Gh¢)')->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'SalesCashMovementReport_' . date('YmdHis');
    }
}
