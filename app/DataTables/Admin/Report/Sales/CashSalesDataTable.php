<?php

namespace App\DataTables\Admin\Report\Sales;

use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class CashSalesDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('sale_no', function ($row) {
                return optional($row->sale)->sale_no ?? 'N/A';
            })
            ->addColumn('customer_name', function ($row) {
                return optional($row->customer)->name ?? 'N/A';
            })
            ->addColumn('sale_date', function ($row) {
                return optional($row->sale)->date
                    ? Carbon::parse($row->sale->date)->format('d-m-Y')
                    : 'N/A';
            })
            ->addColumn('due_amount_formatted', function ($row) {
                return number_format((float) $row->due_amount, 2);
            })
            ->addColumn('amount_formatted', function ($row) {
                return number_format((float) $row->total_amount, 2);
            })
            ->addColumn('status', function () {
                return '<span class="badge bg-success">Settled</span>';
            })
            ->rawColumns(['status'])
            ->setRowId('id');
    }

    public function query(Payment $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->visibleToSalesViewer()
            ->with(['customer:id,name', 'sale:id,sale_no,date'])
            ->where('due_amount', '<=', 0)
            ->orderByDesc('id');

        if ($saleId = $this->request()->get('sale_id')) {
            $query->where('sale_id', $saleId);
        }

        if ($customerId = $this->request()->get('customer_id')) {
            $query->where('customer_id', $customerId);
        }

        if ($fromDate = $this->request()->get('from_date')) {
            $query->whereHas('sale', function ($q) use ($fromDate) {
                $q->whereDate('date', '>=', $fromDate);
            });
        }

        if ($toDate = $this->request()->get('to_date')) {
            $query->whereHas('sale', function ($q) use ($toDate) {
                $q->whereDate('date', '<=', $toDate);
            });
        }

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('cash-sales-table')
            ->columns($this->getColumns())
            ->minifiedAjax(request()->getRequestUri())
            ->orderBy(0, 'desc')
            ->selectStyleSingle()
            ->parameters([
                'dom' => "<'row'l>Bfrtip",
                'buttons' => [
                    'colvis',
                    ['extend' => 'excel', 'footer' => false],
                    ['extend' => 'csv', 'footer' => false],
                    'reset',
                    'reload',
                ],
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::make('sale_no')->title('Sale No'),
            Column::make('customer_name')->title('Customer Name'),
            Column::make('sale_date')->title('Date')->searchable(false),
            Column::make('amount_formatted')->title('Amount (Gh¢)')->searchable(false),
            Column::make('due_amount_formatted')->title('Due Amount (Gh¢)')->searchable(false),
            Column::make('status')->title('Status')->searchable(false)->printable(false)->exportable(false),
        ];
    }

    protected function filename(): string
    {
        return 'CashSales_' . date('YmdHis');
    }
}
