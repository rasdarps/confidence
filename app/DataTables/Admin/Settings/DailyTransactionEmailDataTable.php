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
            ->addColumn('sender_name', function ($row) {
                return $row->sender ? $row->sender->name : 'System Auto Scheduler';
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
                return number_format((float) $row->cash_shortage, 2);
            })
            ->editColumn('sent_at', function ($row) {
                return $row->sent_at
                    ? Carbon::parse($row->sent_at)->format('d-m-Y g:i A')
                    : '';
            })
            ->editColumn('type', function ($row) {
                return strtoupper((string) $row->type);
            })
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
            Column::make('sent_at')->title('Sent At'),
            Column::make('business_date')->title('Business Date'),
            Column::make('type')->title('Type'),
            Column::make('recipient_email')->title('Recipient Email'),
            Column::make('sales_total')->title('Sales Total (Gh¢)'),
            Column::make('expenses_total')->title('Expenses Total (Gh¢)'),
            Column::make('cash_shortage')->title('Cash Shortage (Gh¢)'),
            Column::make('sender_name')->title('Sent By'),
            Column::make('notes')->title('Notes'),
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
