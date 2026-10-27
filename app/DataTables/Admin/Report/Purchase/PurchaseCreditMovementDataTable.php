<?php

namespace App\DataTables\Admin\Report\Purchase;

use App\Models\PurchasePaymentDetail;
use App\Support\PaymentStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class PurchaseCreditMovementDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->editColumn('payment_date', function ($row) {
                return $row->payment_date ? Carbon::parse($row->payment_date)->format('d-m-Y') : 'N/A';
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
            ->setRowId('id');
    }

    public function query(PurchasePaymentDetail $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->from('purchase_payment_details')
            ->leftJoin('purchase_payments', 'purchase_payments.purchase_id', '=', 'purchase_payment_details.purchase_id')
            ->leftJoin('purchases', 'purchases.id', '=', 'purchase_payments.purchase_id')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'purchase_payments.supplier_id')
            ->select([
                'purchase_payment_details.id',
                'purchase_payments.purchase_id',
                'purchase_payments.supplier_id',
                'purchase_payments.paid_status',
                'purchase_payments.total_amount',
                'purchase_payment_details.current_paid_amount',
                'purchase_payments.due_amount',
                'purchases.purchase_no',
                'purchase_payment_details.date as payment_date',
                'suppliers.name as supplier_name',
            ])
            ->whereIn('purchase_payments.paid_status', [PaymentStatus::FULL_CREDIT, PaymentStatus::PARTIAL_PAYMENT])
            ->where('purchase_payments.due_amount', '>', 0)
            ->orderByDesc('purchase_payment_details.date')
            ->orderByDesc('purchase_payment_details.id');

        if ($purchaseId = $this->request()->get('purchase_id')) {
            $query->where('purchase_payments.purchase_id', $purchaseId);
        }

        if ($supplierId = $this->request()->get('supplier_id')) {
            $query->where('purchase_payments.supplier_id', $supplierId);
        }

        if ($status = $this->request()->get('paid_status')) {
            $normalizedStatus = PaymentStatus::normalize($status);

            if (in_array($normalizedStatus, [PaymentStatus::FULL_CREDIT, PaymentStatus::PARTIAL_PAYMENT], true)) {
                $query->where('purchase_payments.paid_status', $normalizedStatus);
            }
        }

        if ($fromDate = $this->request()->get('from_date')) {
            $query->whereDate('purchase_payment_details.date', '>=', $fromDate);
        }

        if ($toDate = $this->request()->get('to_date')) {
            $query->whereDate('purchase_payment_details.date', '<=', $toDate);
        }

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('purchase-credit-movement-table')
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
            Column::make('purchase_no')->title('Purchase No'),
            Column::make('supplier_name')->title('Supplier'),
            Column::make('paid_status')->title('Credit Type'),
            Column::make('total_amount')->title('Invoice Amount (Gh¢)')->searchable(false),
            Column::make('current_paid_amount')->title('Current Paid Amount (Gh¢)')->searchable(false),
            Column::make('due_amount')->title('Due Amount (Gh¢)')->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'PurchaseCreditMovementReport_' . date('YmdHis');
    }
}
