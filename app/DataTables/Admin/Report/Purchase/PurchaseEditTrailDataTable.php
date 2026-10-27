<?php

namespace App\DataTables\Admin\Report\Purchase;

use App\Models\PurchaseEditTrail;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class PurchaseEditTrailDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('purchase_no', function ($row) {
                return $row->purchase ? $row->purchase->purchase_no : 'N/A';
            })
            ->addColumn('edited_by_name', function ($row) {
                return $row->editedBy ? $row->editedBy->name : 'System';
            })
            ->editColumn('created_at', function ($row) {
                return $row->created_at ? Carbon::parse($row->created_at)->format('d M Y h:i A') : 'N/A';
            })
            ->editColumn('before_total', function ($row) {
                return number_format((float) $row->before_total, 2);
            })
            ->editColumn('after_total', function ($row) {
                return number_format((float) $row->after_total, 2);
            })
            ->editColumn('delta_total', function ($row) {
                $delta = (float) $row->delta_total;
                $sign = $delta > 0 ? '+' : '';
                return $sign . number_format($delta, 2);
            })
            ->addColumn('changes_summary', function ($row) {
                $changes = is_array($row->changes) ? $row->changes : [];

                if (empty($changes)) {
                    return '<span class="text-muted">No line-level changes captured</span>';
                }

                $parts = [];
                foreach ($changes as $change) {
                    $parts[] = sprintf(
                        '%s · Qty %s→%s · Price %s→%s',
                        e((string) ($change['product_name'] ?? 'Unknown Product')),
                        number_format((float) ($change['old_qty'] ?? 0), 2),
                        number_format((float) ($change['new_qty'] ?? 0), 2),
                        number_format((float) ($change['old_unit_cost_price'] ?? 0), 2),
                        number_format((float) ($change['new_unit_cost_price'] ?? 0), 2)
                    );
                }

                return implode('<br>', $parts);
            })
            ->rawColumns(['changes_summary'])
            ->setRowId('id');
    }

    public function query(PurchaseEditTrail $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->with(['purchase:id,purchase_no', 'editedBy:id,name'])
            ->orderByDesc('created_at');

        if ($purchaseId = $this->request()->get('purchase_id')) {
            $query->where('purchase_id', $purchaseId);
        }

        if ($editedBy = $this->request()->get('edited_by')) {
            $query->where('edited_by', $editedBy);
        }

        if ($fromDate = $this->request()->get('from_date')) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate = $this->request()->get('to_date')) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('purchase-edit-trail-table')
            ->columns($this->getColumns())
            ->minifiedAjax(request()->getRequestUri())
            ->orderBy(2, 'desc')
            ->selectStyleSingle()
            ->parameters([
                'dom' => "<'row'l>Bfrtip",
                'buttons' => [
                    'colvis',
                    'excel',
                    'csv',
                    'reset',
                    'reload',
                ],
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::make('purchase_no')->title('Purchase'),
            Column::make('edited_by_name')->title('Edited By'),
            Column::make('created_at')->title('Edited At'),
            Column::make('reason')->title('Reason'),
            Column::make('before_total')->title('Before Total'),
            Column::make('after_total')->title('After Total'),
            Column::make('delta_total')->title('Delta Total'),
            Column::make('changes_summary')->title('Changes')->searchable(false)->orderable(false),
        ];
    }

    protected function filename(): string
    {
        return 'PurchaseEditTrail_' . date('YmdHis');
    }
}
