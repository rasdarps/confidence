<?php

namespace App\DataTables\Admin\Report\Sales;

use App\Models\SaleReceiptEvent;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class ReceiptAuditDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('event_label', function ($event) {
                return match ($event->event_type) {
                    'original' => 'Original Print',
                    'reprint' => 'Reprint',
                    'delivery_verified' => 'Delivery Verified',
                    default => ucwords(str_replace('_', ' ', $event->event_type)),
                };
            })
            ->addColumn('printed_by', fn ($event) => $event->event_type === 'original' ? ($event->operator_name ?? '-') : '-')
            ->addColumn('reprinted_by', fn ($event) => $event->event_type === 'reprint' ? ($event->operator_name ?? '-') : '-')
            ->addColumn('reprint_note', fn ($event) => $event->event_type === 'reprint' ? ($event->reason ?: '-') : '-')
            ->addColumn('delivery_status', function ($event) {
                return $event->delivered_at
                    ? '<span class="badge bg-success">Delivered</span>'
                    : '<span class="badge bg-secondary">Pending</span>';
            })
            ->addColumn('verified_by', fn ($event) => $event->event_type === 'delivery_verified' ? ($event->operator_name ?? '-') : '-')
            ->addColumn('verified_at', function ($event) {
                return $event->event_type === 'delivery_verified' && $event->created_at
                    ? $event->created_at->format('d-m-Y h:i A')
                    : '-';
            })
            ->editColumn('created_at', function ($event) {
                return in_array($event->event_type, ['original', 'reprint'], true) && $event->created_at
                    ? $event->created_at->format('d-m-Y h:i A')
                    : '-';
            })
            ->filterColumn('sale_no', fn ($query, $keyword) => $query->where('sales.sale_no', 'like', "%{$keyword}%"))
            ->filterColumn('operator_name', fn ($query, $keyword) => $query->where('users.name', 'like', "%{$keyword}%"))
            ->rawColumns(['delivery_status'])
            ->setRowId('id');
    }

    public function query(SaleReceiptEvent $model): QueryBuilder
    {
        $printCounts = DB::table('sale_receipt_events')
            ->selectRaw('sale_id, COUNT(*) as total_print_count')
            ->whereIn('event_type', ['original', 'reprint'])
            ->groupBy('sale_id');

        $query = $model->newQuery()
            ->join('sales', 'sales.id', '=', 'sale_receipt_events.sale_id')
            ->leftJoin('users', 'users.id', '=', 'sale_receipt_events.operator_id')
            ->leftJoinSub($printCounts, 'print_counts', function ($join) {
                $join->on('print_counts.sale_id', '=', 'sales.id');
            })
            ->select([
                'sale_receipt_events.*',
                'sales.sale_no',
                'sales.delivered_at',
                'users.name as operator_name',
                DB::raw('COALESCE(print_counts.total_print_count, 0) as total_print_count'),
            ]);

        $viewer = auth()->user();
        $viewerIsSuperAdmin = $viewer && (
            strtolower((string) ($viewer->user_type ?? '')) === 'super admin'
            || DB::table('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('model_has_roles.model_type', get_class($viewer))
                ->where('model_has_roles.model_id', $viewer->id)
                ->whereRaw('LOWER(roles.name) = ?', ['super admin'])
                ->exists()
        );

        if (! $viewerIsSuperAdmin) {
            $query->where(function ($visibilityQuery) {
                $visibilityQuery->whereNull('users.id')
                    ->orWhere(function ($operatorQuery) {
                        $operatorQuery->whereRaw("LOWER(COALESCE(users.user_type, '')) <> ?", ['super admin'])
                            ->whereDoesntHave('operator.roles', function ($roleQuery) {
                                $roleQuery->whereRaw('LOWER(name) = ?', ['super admin']);
                            });
                    });
            });
        }

        if ($saleNo = $this->request()->get('sale_no')) {
            $query->where('sales.sale_no', 'like', '%' . $saleNo . '%');
        }
        if ($operatorId = $this->request()->get('operator_id')) {
            $query->where('sale_receipt_events.operator_id', $operatorId);
        }
        if ($eventType = $this->request()->get('event_type')) {
            $query->where('sale_receipt_events.event_type', $eventType);
        }
        if ($deliveryStatus = $this->request()->get('delivery_status')) {
            $deliveryStatus === 'delivered'
                ? $query->whereNotNull('sales.delivered_at')
                : $query->whereNull('sales.delivered_at');
        }
        if ($fromDate = $this->request()->get('from_date')) {
            $query->whereDate('sale_receipt_events.created_at', '>=', $fromDate);
        }
        if ($toDate = $this->request()->get('to_date')) {
            $query->whereDate('sale_receipt_events.created_at', '<=', $toDate);
        }

        return $query->orderByDesc('sale_receipt_events.created_at');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('receipt-audit-table')
            ->columns($this->getColumns())
            ->minifiedAjax(request()->getRequestUri())
            ->orderBy(9, 'desc')
            ->selectStyleSingle()
            ->parameters([
                'dom' => "<'row'l>Bfrtip",
                'buttons' => ['colvis', 'excel', 'csv', 'reset', 'reload'],
                'pageLength' => 25,
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::make('sale_no')->title('Invoice No')->name('sales.sale_no'),
            Column::make('total_print_count')->title('Print Count')->searchable(false),
            Column::make('event_label')->title('Event')->name('sale_receipt_events.event_type'),
            Column::make('printed_by')->title('Printed By')->name('users.name'),
            Column::make('reprinted_by')->title('Reprinted By')->name('users.name'),
            Column::make('reprint_note')->title('Reprint Note')->name('sale_receipt_events.reason'),
            Column::make('delivery_status')->title('Delivery Status')->name('sales.delivered_at')->searchable(false),
            Column::make('verified_by')->title('Verified By')->name('users.name'),
            Column::make('verified_at')->title('Verified At')->name('sale_receipt_events.created_at')->searchable(false),
            Column::make('created_at')->title('Print Time')->name('sale_receipt_events.created_at'),
        ];
    }

    protected function filename(): string
    {
        return 'ReceiptAudit_' . date('YmdHis');
    }
}