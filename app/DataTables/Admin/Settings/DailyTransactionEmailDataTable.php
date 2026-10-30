<?php

namespace App\DataTables\Admin\Settings;

use App\Models\DailyTransactionEmail;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class DailyTransactionEmailDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('scope', function () {
                return '<span class="badge badge-soft-info" style="color: #0F766E; background-color: #E6FFFA; border: 1px solid #B2F5EA;"><i class="fas fa-building me-1"></i> Enterprise (All Outlets)</span>';
            })
            ->addColumn('sender_name', function ($row) {
                if (strtolower((string) $row->type) === 'auto') {
                    return '<span class="text-muted font-size-12"><i class="fas fa-cogs me-1"></i> System Cron</span>';
                }
                return $row->sender ? e($row->sender->name) : 'Authorized Staff';
            })
            ->addColumn('net_total', function ($row) {
                $net = (float) $row->sales_total - (float) $row->expenses_total - (float) $row->cash_shortage;
                $formatted = number_format($net, 2);
                if ($net >= 0) {
                    return '<span class="fw-bold" style="color: #0F766E;">+' . $formatted . '</span>';
                }
                return '<span class="fw-bold text-danger">' . $formatted . '</span>';
            })
            ->editColumn('business_date', function ($row) {
                return $row->business_date
                    ? Carbon::parse($row->business_date)->format('d-m-Y')
                    : '';
            })
            ->editColumn('sales_total', function ($row) {
                return number_format((float) $row->sales_total, 2);
            })
            ->editColumn('expenses_total', function ($row) {
                return number_format((float) $row->expenses_total, 2);
            })
            ->editColumn('cash_shortage', function ($row) {
                $shortage = (float) $row->cash_shortage;
                if ($shortage > 0) {
                    return '<span class="badge bg-danger text-white">Gh¢ ' . number_format($shortage, 2) . '</span>';
                }
                return '<span class="text-muted">Gh¢ 0.00</span>';
            })
            ->editColumn('sent_at', function ($row) {
                return $row->sent_at
                    ? Carbon::parse($row->sent_at)->format('d-m-Y g:i A')
                    : '';
            })
            ->editColumn('type', function ($row) {
                if (strtolower((string) $row->type) === 'auto') {
                    return '<span class="badge bg-info text-white"><i class="fas fa-robot me-1"></i> Auto</span>';
                }
                return '<span class="badge bg-secondary text-white"><i class="fas fa-user me-1"></i> Manual</span>';
            })
            ->rawColumns(['scope', 'type', 'cash_shortage', 'net_total', 'sender_name'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(DailyTransactionEmail $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->with(['sender'])
            ->orderByDesc('sent_at');

        if ($fromDate = $this->request()->get('from_date')) {
            $query->whereDate('business_date', '>=', $fromDate);
        }

        if ($toDate = $this->request()->get('to_date')) {
            $query->whereDate('business_date', '<=', $toDate);
        }

        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('daily-transaction-email-table')
            ->columns($this->getColumns())
            ->minifiedAjax(request()->getRequestUri())
            ->orderBy(0, 'desc')
            ->selectStyleSingle()
            ->parameters([
                'dom' => "<'row'l>Bfrtip",
                'buttons' => ['colvis', 'excel', 'csv', 'reset', 'reload'],
            ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::make('sent_at')->title('Sent Timestamp'),
            Column::make('business_date')->title('Business Date'),
            Column::make('scope')->title('Scope')->searchable(false)->orderable(false),
            Column::make('type')->title('Dispatch Type'),
            Column::make('recipient_email')->title('Recipient Email'),
            Column::make('sales_total')->title('Sales (Gh¢)'),
            Column::make('expenses_total')->title('Expenses (Gh¢)'),
            Column::make('cash_shortage')->title('Shortage (Gh¢)'),
            Column::make('net_total')->title('Net Flow (Gh¢)')->searchable(false)->orderable(false),
            Column::make('sender_name')->title('Dispatched By'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'DailyTransactionEmail_' . date('YmdHis');
    }
}
