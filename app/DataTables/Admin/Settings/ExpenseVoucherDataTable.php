<?php

namespace App\DataTables\Admin\Settings;

use App\Models\ExpenseVoucher;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class ExpenseVoucherDataTable extends DataTable
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
                if (!$row->outlet) {
                    return '<span class="badge bg-secondary">Central Warehouse</span>';
                }
                return '<span class="badge bg-light text-dark border">' . e($row->outlet->name) . '</span>' . ($row->outlet->is_central ? ' <span class="badge bg-secondary">Central</span>' : '');
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
            ->addColumn('items_count', function ($row) {
                return (string) ($row->items_count ?? 0);
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
            ->addColumn('edit_button', function ($row) {
                $showUrl = route('expense-vouchers.show', Crypt::encrypt($row->id));
                $printUrl = route('expense-vouchers.print', Crypt::encrypt($row->id));
                $editUrl = route('expense-vouchers.edit', Crypt::encrypt($row->id));
                $deleteUrl = route('expense-vouchers.destroy', Crypt::encrypt($row->id));
                $csrf = csrf_field();
                $method = method_field('DELETE');

                $buttons = [];

                if (Gate::allows('expense-list')) {
                    $buttons[] = '
                    <a style="border:none; background:#0d6efd; cursor:pointer; display:inline-flex; align-items:center; gap:6px; width:auto;" class="btn btn-sm btn-primary text-nowrap" href="' . $showUrl . '">
                        <i class="fas fa-eye" style="color:#fff; justify-content:center;"></i>
                    </a>';
                    $buttons[] = '
                    <a style="border:none; background:#6f42c1; cursor:pointer; display:inline-flex; align-items:center; gap:6px; width:auto;" class="btn btn-sm btn-secondary text-nowrap" href="' . $printUrl . '" target="_blank" rel="noopener">
                        <i class="fas fa-print" style="color:#fff; justify-content:center;"></i>
                    </a>';
                }

                if (Gate::allows('expense-edit')) {
                    $buttons[] = '
                    <form class="d-inline-block m-0" action="' . $editUrl . '" method="GET">
                        ' . $csrf . '
                        <button class="btn-action-edit table-edit-btn">
                            <i class="fas fa-edit"></i>
                        </button>
                    </form>';
                }

                if (Gate::allows('expense-delete')) {
                    $buttons[] = '
                    <form class="d-inline-block m-0" action="' . $deleteUrl . '" method="POST">
                        ' . $csrf . '
                        ' . $method . '
                        <button type="button" class="btn-action-delete delete-confirm-btn btn-delete">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </form>';
                }

                if (empty($buttons)) {
                    return '<span class="text-muted">No actions</span>';
                }

                return '<div class="d-inline-flex align-items-center flex-nowrap gap-2 action-buttons">' . implode('', $buttons) . '</div>';
            })
            ->editColumn('voucher_date', function ($row) {
                return $row->voucher_date
                    ? Carbon::parse($row->voucher_date)->format('d-m-Y')
                    : '';
            })
            ->editColumn('total_amount', function ($row) {
                return number_format((float) $row->total_amount, 2);
            })
            ->rawColumns(['accounting_posting_status', 'edit_button', 'outlet_name'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(ExpenseVoucher $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->with(['createdBy', 'outlet'])
            ->withCount('items')
            ->orderBy('voucher_date', 'desc')
            ->orderBy('id', 'desc');

        $currentUser = auth()->user();
        if ($currentUser && $currentUser->assignedOutletId()) {
            $query->where('outlet_id', $currentUser->assignedOutletId());
        } elseif ($outletId = $this->request()->get('outlet_id')) {
            $query->where('outlet_id', $outletId);
        }

        $status = (string) $this->request()->get('accounting_status', '');
        if (in_array($status, ['unposted', 'posted', 'reversed'], true)) {
            $query->where('accounting_status', $status);
        } else {
            $currentUser = auth()->user();
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
            ->setTableId('expense-voucher-table')
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
            Column::make('voucher_date')->title('Date'),
            Column::make('outlet_name')->title('Branch / Outlet')->searchable(false),
            Column::make('reference_no')->title('Reference No'),
            Column::make('items_count')->title('Lines'),
            Column::make('total_amount')->title('Total Amount'),
            Column::make('accounting_posting_status')->title('Accounting Status')->searchable(false),
            Column::make('notes')->title('Notes'),
        ];

        if (Gate::allows('user-audit-view')) {
            array_splice($columns, 4, 0, [Column::make('created_by_name')->title('Created By')]);
        }

        if (Gate::allows('expense-list') || Gate::allows('expense-edit') || Gate::allows('expense-delete')) {
            $columns[] = Column::make('edit_button')->title('Actions')
                ->searchable(false)->printable(false)->exportable(false);
        }

        return $columns;
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'ExpenseVoucher_' . date('YmdHis');
    }
}
