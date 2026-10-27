<?php

namespace App\DataTables\Admin\Settings;

use App\Models\CashReconciliation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class CashReconciliationDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('cashier_name', function ($row) {
                return $row->user->name ?? '';
            })
            ->addColumn('approved_by_name', function ($row) {
                if (!Gate::allows('user-audit-view')) {
                    return '';
                }
                $viewer = auth()->user();
                $viewerIsSuperAdmin = $viewer && strtolower((string) ($viewer->user_type ?? '')) === 'super admin';
                $approvedByUser = $row->approvedBy;

                if (!$viewerIsSuperAdmin && $approvedByUser && strtolower((string) ($approvedByUser->user_type ?? '')) === 'super admin') {
                    return '';
                }

                return $approvedByUser->name ?? '';
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

                return $createdByUser->name ?? '';
            })
            ->addColumn('action_button', function ($row) {
                $showUrl = route('cash-reconciliations.show', Crypt::encryptString((string) $row->id));
                $editUrl = route('cash-reconciliations.edit', Crypt::encryptString((string) $row->id));
                $canShow = Gate::allows('cash-reconciliation-show');
                $canEdit = Gate::allows('cash-reconciliation-edit') && in_array($row->status, ['submitted', 'reopened'], true);

                $buttons = [];

                if ($canShow) {
                    $buttons[] = '<a class="btn btn-sm btn-primary text-nowrap" style="display:inline-flex;align-items:center;gap:6px;width:auto;" href="' . $showUrl . '"><i class="fas fa-eye"></i></a>';
                }

                if ($canEdit) {
                    $buttons[] = '<a class="btn btn-sm btn-success text-nowrap" style="display:inline-flex;align-items:center;gap:6px;width:auto;" href="' . $editUrl . '"><i class="fas fa-edit"></i></a>';
                }

                if (empty($buttons)) {
                    return '<span class="text-muted">No actions</span>';
                }

                return '<div class="d-inline-flex align-items-center flex-nowrap gap-2 action-buttons">' . implode('', $buttons) . '</div>';
            })
            ->editColumn('business_date', function ($row) {
                return $row->business_date ? Carbon::parse($row->business_date)->format('d-m-Y') : '';
            })
            ->editColumn('expected_cash_sales', function ($row) {
                return number_format((float) $row->expected_cash_sales, 2);
            })
            ->editColumn('expected_cash_expenses', function ($row) {
                return number_format((float) $row->expected_cash_expenses, 2);
            })
            ->editColumn('expected_closing_cash', function ($row) {
                return number_format((float) $row->expected_closing_cash, 2);
            })
            ->editColumn('physical_counted_cash', function ($row) {
                return number_format((float) $row->physical_counted_cash, 2);
            })
            ->editColumn('variance', function ($row) {
                $value = (float) $row->variance;
                $class = $value < 0 ? 'text-danger' : ($value > 0 ? 'text-success' : 'text-primary');
                return '<span class="' . $class . '">' . number_format($value, 2) . '</span>';
            })
            ->editColumn('variance_type', function ($row) {
                if ($row->variance_type === 'shortage') {
                    return '<span class="badge bg-danger">Shortage</span>';
                }

                if ($row->variance_type === 'overage') {
                    return '<span class="badge bg-success">Overage</span>';
                }

                return '<span class="badge bg-primary">Balanced</span>';
            })
            ->editColumn('status', function ($row) {
                if ($row->status === 'approved') {
                    return '<span class="badge bg-success">Approved</span>';
                }

                if ($row->status === 'reopened') {
                    return '<span class="badge bg-warning text-dark">Reopened</span>';
                }

                return '<span class="badge bg-info text-dark">Submitted</span>';
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
            ->rawColumns(['variance', 'variance_type', 'status', 'accounting_posting_status', 'action_button'])
            ->setRowId('id');
    }

    public function query(CashReconciliation $model): QueryBuilder
    {        
        $query = $model->newQuery()
            ->with(['user', 'createdBy', 'approvedBy'])
            ->orderBy('business_date', 'desc')
            ->orderBy('id', 'desc');
            
        /** @var \App\Models\User|null $currentUser */
        $currentUser = auth()->user();
        if ($currentUser) {
            $viewerIsSuperAdmin = strtolower((string) ($currentUser->user_type ?? '')) === 'super admin';
            $viewerCanSeeAllCashiers = $viewerIsSuperAdmin
                || $currentUser->hasRole('Manager')
                || $currentUser->hasRole('Supervisor');

            if (! $viewerCanSeeAllCashiers) {
                $query->where('user_id', $currentUser->id);
            }
        }

        if ($fromDate = $this->request()->get('from_date')) {
            $query->whereDate('business_date', '>=', $fromDate);
        }

        if ($toDate = $this->request()->get('to_date')) {
            $query->whereDate('business_date', '<=', $toDate);
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

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('cash-reconciliation-table')
            ->columns($this->getColumns())
            ->minifiedAjax(request()->getRequestUri())
            ->orderBy(0, 'desc')
            ->selectStyleSingle()
            ->parameters([
                'dom' => "<'row'l>Bfrtip",
                'buttons' => ['colvis', 'excel', 'csv', 'reset', 'reload'],
            ]);
    }

    public function getColumns(): array
    {
        $columns = [
            Column::make('business_date')->title('Date'),
            Column::make('cashier_name')->title('Cashier'),
            Column::make('expected_cash_sales')->title('Expected Sales'),
            Column::make('expected_cash_expenses')->title('Cash Expenses'),
            Column::make('expected_closing_cash')->title('Expected Closing'),
            Column::make('physical_counted_cash')->title('Physical Cash'),
            Column::make('variance')->title('Variance'),
            Column::make('variance_type')->title('Type'),
            Column::make('status')->title('Status'),
            Column::make('accounting_posting_status')->title('Accounting Status')->searchable(false),
        ];

        if (Gate::allows('user-audit-view')) {
            $columns[] = Column::make('approved_by_name')->title('Approved By');
        }

        if (Gate::allows('cash-reconciliation-show') || Gate::allows('cash-reconciliation-edit')) {
            $columns[] = Column::make('action_button')->title('Action')->searchable(false)->printable(false)->exportable(false);
        }

        return $columns;
    }

    protected function filename(): string
    {
        return 'CashReconciliation_' . date('YmdHis');
    }
}
