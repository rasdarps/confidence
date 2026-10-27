<?php

namespace App\DataTables\Admin\Report;

use App\Models\StockAdjustmentItem;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class StockAdjustmentReportDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('adjustment_date', function ($row) {
                return $row->adjustment && $row->adjustment->adjustment_date
                    ? Carbon::parse($row->adjustment->adjustment_date)->format('d-m-Y')
                    : '';
            })
            ->addColumn('reference_no', function ($row) {
                return $row->adjustment->reference_no ?? '';
            })
            ->addColumn('adjustment_type', function ($row) {
                if ((float) $row->after_qty > (float) $row->before_qty) {
                    return 'Increase';
                }

                if ((float) $row->after_qty < (float) $row->before_qty) {
                    return 'Decrease';
                }

                return '';
            })
            ->addColumn('product_name', function ($row) {
                return $row->product->name ?? '';
            })
            ->addColumn('created_by_name', function ($row) {
                if (!Gate::allows('user-audit-view')) {
                    return '';
                }
                $viewer = auth()->user();
                $viewerIsSuperAdmin = $viewer && strtolower((string) ($viewer->user_type ?? '')) === 'super admin';
                $createdByUser = $row->adjustment ? $row->adjustment->createdBy : null;

                if (!$viewerIsSuperAdmin && $createdByUser && strtolower((string) ($createdByUser->user_type ?? '')) === 'super admin') {
                    return '';
                }

                return $createdByUser
                    ? $createdByUser->name
                    : '';
            })
            ->editColumn('qty', function ($row) {
                return number_format((float) $row->qty, 2);
            })
            ->editColumn('unit_cost', function ($row) {
                return number_format((float) $row->unit_cost, 2);
            })
            ->editColumn('amount', function ($row) {
                return number_format((float) $row->amount, 2);
            })
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(StockAdjustmentItem $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->with(['adjustment.createdBy', 'product'])
            ->whereHas('adjustment')
            ->orderBy('id', 'desc');

        if ($adjustmentType = $this->request()->get('adjustment_type')) {
            if ($adjustmentType === 'increase') {
                $query->whereColumn('after_qty', '>', 'before_qty');
            }

            if ($adjustmentType === 'decrease') {
                $query->whereColumn('after_qty', '<', 'before_qty');
            }
        }

        if ($createdBy = $this->request()->get('created_by')) {
            $query->whereHas('adjustment', function ($q) use ($createdBy) {
                $q->where('created_by', $createdBy);
            });
        }

        if ($productId = $this->request()->get('product_id')) {
            $query->where('product_id', $productId);
        }

        if ($fromDate = $this->request()->get('from_date')) {
            $query->whereHas('adjustment', function ($q) use ($fromDate) {
                $q->whereDate('adjustment_date', '>=', $fromDate);
            });
        }

        if ($toDate = $this->request()->get('to_date')) {
            $query->whereHas('adjustment', function ($q) use ($toDate) {
                $q->whereDate('adjustment_date', '<=', $toDate);
            });
        }

        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('stockadjustmentreport-table')
            ->columns($this->getColumns())
            ->minifiedAjax(request()->getRequestUri())
            ->orderBy(0, 'desc')
            ->selectStyleSingle()
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

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        $columns = [
            Column::make('adjustment_date')->title('Date'),
            Column::make('reference_no')->title('Reference No'),
            Column::make('adjustment_type')->title('Type'),
            Column::make('product_name')->title('Product'),
            Column::make('qty')->title('Qty'),
            Column::make('unit_cost')->title('Unit Cost'),
            Column::make('amount')->title('Amount (Gh¢)'),
            Column::make('description')->title('Description'),
        ];

        if (Gate::allows('user-audit-view')) {
            $columns[] = Column::make('created_by_name')->title('Created By');
        }

        return $columns;
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'StockAdjustmentReport_' . date('YmdHis');
    }
}
