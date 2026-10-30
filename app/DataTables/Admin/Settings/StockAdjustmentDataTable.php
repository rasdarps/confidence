<?php

namespace App\DataTables\Admin\Settings;

use App\Models\StockAdjustment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class StockAdjustmentDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('outlet_name', function ($row) {
                if (! $row->outlet) {
                    return '<span class="badge bg-secondary">Central</span>';
                }
                $badgeClass = $row->outlet->is_central ? 'bg-primary' : 'bg-info';
                return '<span class="badge ' . $badgeClass . '">' . e($row->outlet->name) . '</span>';
            })
            ->addColumn('created_by_name', function ($row) {
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
            ->addColumn('type_badge', function ($row) {
                if ($row->adjustment_type === 'increase') {
                    return '<span class="badge bg-success">Increase</span>';
                }
                if ($row->adjustment_type === 'mixed') {
                    return '<span class="badge bg-warning text-dark">Mixed</span>';
                }
                return '<span class="badge bg-danger">Decrease</span>';
            })
            ->addColumn('accounting_posting_status', function ($row) {
                $status = (string) ($row->accounting_status ?? 'unposted');

                if ($status === 'posted') {
                    return '<span class="badge bg-success">Posted</span>';
                }

                if ($status === 'reversed') {
                    return '<span class="badge bg-danger">Reversed</span>';
                }

                return '<span class="badge bg-warning text-dark">Unposted</span>';
            })
            ->addColumn('action_button', function ($row) {
                $showUrl = route('stock-adjustments.show', Crypt::encrypt($row->id));
                $printUrl = route('stock-adjustments.print', Crypt::encrypt($row->id));
                $editUrl = route('stock-adjustments.edit', Crypt::encrypt($row->id));
                $deleteUrl = route('stock-adjustments.destroy', Crypt::encrypt($row->id));
                $csrf = csrf_field();
                $method = method_field('DELETE');

                $buttons = [];

                if (Gate::allows('stock-adjustment-list')) {
                    $buttons[] = '<a class="btn btn-sm btn-primary text-nowrap" style="display:inline-flex;align-items:center;gap:6px;width:auto;" href="' . $showUrl . '"><i class="fas fa-eye"></i></a>';
                }

                if (Gate::allows('stock_adjustment-print')) {
                    $buttons[] = '<a class="btn btn-sm btn-secondary text-nowrap" style="display:inline-flex;align-items:center;gap:6px;width:auto;" target="_blank" href="' . $printUrl . '"><i class="fas fa-print"></i></a>';
                }

                if (Gate::allows('stock-adjustment-edit')) {
                    $buttons[] = '<a class="btn btn-sm btn-success text-nowrap" style="display:inline-flex;align-items:center;gap:6px;width:auto;" href="' . $editUrl . '"><i class="fas fa-edit"></i></a>';
                }

                if (Gate::allows('stock-adjustment-delete')) {
                    $buttons[] = '
                    <form class="d-inline-block m-0" action="' . $deleteUrl . '" method="POST">
                        ' . $csrf . '
                        ' . $method . '
                        <button type="button" class="btn-delete delete-confirm-btn btn btn-sm btn-danger text-nowrap" style="display:inline-flex;align-items:center;gap:6px;width:auto;"><i class="fas fa-trash"></i></button>
                    </form>';
                }

                if (empty($buttons)) {
                    return '<span class="text-muted">No actions</span>';
                }

                return '<div class="d-inline-flex align-items-center flex-nowrap gap-2 action-buttons">' . implode('', $buttons) . '</div>';
            })
            ->editColumn('adjustment_date', function ($row) {
                return $row->adjustment_date
                    ? Carbon::parse($row->adjustment_date)->format('d-m-Y')
                    : '';
            })
            ->editColumn('total_qty', function ($row) {
                return number_format((float) $row->total_qty, 2);
            })
            ->editColumn('total_value', function ($row) {
                return number_format((float) $row->total_value, 2);
            })
            ->rawColumns(['outlet_name', 'type_badge', 'accounting_posting_status', 'action_button'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(StockAdjustment $model): QueryBuilder
    {
        $currentUser = auth()->user();
        $assignedOutletId = $currentUser ? $currentUser->assignedOutletId() : null;

        $query = $model->newQuery()
            ->with(['createdBy', 'outlet'])
            ->orderBy('adjustment_date', 'desc')
            ->orderBy('id', 'desc');

        if ($assignedOutletId) {
            $query->where('outlet_id', $assignedOutletId);
        } elseif ($this->request()->filled('outlet_id')) {
            $query->where('outlet_id', (int) $this->request()->get('outlet_id'));
        }

        $status = (string) $this->request()->get('accounting_status', '');
        if (in_array($status, ['unposted', 'posted', 'reversed'], true)) {
            $query->where('accounting_status', $status);
        } else {
            $isCashier = $currentUser
                && method_exists($currentUser, 'hasRole')
                && $currentUser->hasRole('Cashier');

            if ($isCashier) {
                $query->where('accounting_status', 'unposted');
            }
        }

        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('stock-adjustment-table')
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
        $columns = [
            Column::make('adjustment_date')->title('Date'),
            Column::make('reference_no')->title('Reference No'),
            Column::make('outlet_name')->title('Branch / Outlet'),
            Column::make('type_badge')->title('Type'),
            Column::make('accounting_posting_status')->title('Accounting Status')->searchable(false),
            Column::make('reason')->title('Reason'),
            Column::make('total_qty')->title('Total Qty'),
            Column::make('total_value')->title('Total Value'),
        ];

        if (Gate::allows('user-audit-view')) {
            array_splice($columns, 6, 0, [Column::make('created_by_name')->title('Created By')]);
        }

        if (Gate::allows('stock-adjustment-list') || Gate::allows('stock_adjustment-print') || Gate::allows('stock-adjustment-edit') || Gate::allows('stock-adjustment-delete')) {
            $columns[] = Column::make('action_button')->title('Actions')->searchable(false)->printable(false)->exportable(false);
        }

        return $columns;
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'StockAdjustment_' . date('YmdHis');
    }
}
