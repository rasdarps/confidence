<?php

namespace App\DataTables\Admin\Report\Purchase;

use App\Models\PurchasePayment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class CashPurchasesDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('purchase_no', function ($row) {
                return optional($row->purchase)->purchase_no ?? 'N/A';
            })
            ->addColumn('supplier_name', function ($row) {
                return optional($row->supplier)->name ?? 'N/A';
            })
            ->addColumn('purchase_date', function ($row) {
                return optional($row->purchase)->date
                    ? Carbon::parse($row->purchase->date)->format('d-m-Y')
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

    public function query(PurchasePayment $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->with(['supplier:id,name', 'purchase:id,purchase_no,date'])
            ->where('due_amount', '<=', 0)
            ->orderByDesc('id');

        if ($purchaseId = $this->request()->get('purchase_id')) {
            $query->where('purchase_id', $purchaseId);
        }

        if ($supplierId = $this->request()->get('supplier_id')) {
            $query->where('supplier_id', $supplierId);
        }

        if ($fromDate = $this->request()->get('from_date')) {
            $query->whereHas('purchase', function ($q) use ($fromDate) {
                $q->whereDate('date', '>=', $fromDate);
            });
        }

        if ($toDate = $this->request()->get('to_date')) {
            $query->whereHas('purchase', function ($q) use ($toDate) {
                $q->whereDate('date', '<=', $toDate);
            });
        }

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('cash-purchases-table')
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
            Column::make('purchase_no')->title('Purchase No'),
            Column::make('supplier_name')->title('Supplier Name'),
            Column::make('purchase_date')->title('Date')->searchable(false),
            Column::make('amount_formatted')->title('Amount (Gh¢)')->searchable(false),
            Column::make('due_amount_formatted')->title('Due Amount (Gh¢)')->searchable(false),
            Column::make('status')->title('Status')->searchable(false)->printable(false)->exportable(false),
        ];
    }

    protected function filename(): string
    {
        return 'CashPurchases_' . date('YmdHis');
    }
}
