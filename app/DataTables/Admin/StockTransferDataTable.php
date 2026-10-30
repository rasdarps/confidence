<?php

namespace App\DataTables\Admin;

use App\Models\StockTransfer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class StockTransferDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('from_outlet_name', function ($row) {
                return $row->fromOutlet ? $row->fromOutlet->name : 'N/A';
            })
            ->addColumn('to_outlet_name', function ($row) {
                return $row->toOutlet ? $row->toOutlet->name : 'N/A';
            })
            ->editColumn('transfer_date', function ($row) {
                return $row->transfer_date ? Carbon::parse($row->transfer_date)->format('d-m-Y') : 'N/A';
            })
            ->addColumn('total_quantity', function ($row) {
                return number_format((float) $row->items->sum('qty_dispatched'), 2);
            })
            ->editColumn('status', function ($row) {
                return match ($row->status) {
                    'received' => '<span class="badge bg-success">Received</span>',
                    'dispatched' => '<span class="badge bg-warning text-dark">In Transit / Dispatched</span>',
                    'cancelled' => '<span class="badge bg-danger">Cancelled</span>',
                    default => '<span class="badge bg-secondary">' . ucfirst($row->status) . '</span>',
                };
            })
            ->addColumn('action', function ($row) {
                $encryptedId = Crypt::encrypt($row->id);
                $showUrl = route('stock-transfers.show', $encryptedId);
                $buttons = [];

                if (Gate::allows('stock-transfer-list')) {
                    $buttons[] = '<a href="' . $showUrl . '" class="btn btn-sm btn-info text-nowrap" title="View Transfer Details"><i class="fas fa-eye"></i> View</a>';
                }

                $user = auth()->user();
                $userOutletId = $user ? $user->assignedOutletId() : null;

                // Receive action: status is dispatched, and user is either central/admin or assigned to destination
                if ($row->status === 'dispatched' && Gate::allows('stock-transfer-receive')) {
                    if (!$userOutletId || $userOutletId === $row->to_outlet_id) {
                        $buttons[] = '<a href="' . $showUrl . '#receive-section" class="btn btn-sm btn-success text-nowrap" title="Receive Goods"><i class="fas fa-check-circle"></i> Receive</a>';
                    }
                }

                // Cancel action: status is dispatched, and user is central/admin or assigned to source
                if ($row->status === 'dispatched' && Gate::allows('stock-transfer-cancel')) {
                    if (!$userOutletId || $userOutletId === $row->from_outlet_id) {
                        $cancelUrl = route('stock-transfers.cancel', $encryptedId);
                        $csrf = csrf_field();
                        $buttons[] = '
                        <form class="d-inline-block m-0" action="' . $cancelUrl . '" method="POST" onsubmit="return confirm(\'Are you sure you want to cancel this transfer and return stock to source?\');">
                            ' . $csrf . '
                            <button type="submit" class="btn btn-sm btn-danger text-nowrap"><i class="fas fa-times-circle"></i> Cancel</button>
                        </form>';
                    }
                }

                return '<div class="d-inline-flex align-items-center flex-nowrap gap-2 action-buttons">' . implode(' ', $buttons) . '</div>';
            })
            ->rawColumns(['status', 'action'])
            ->setRowId('id');
    }

    public function query(StockTransfer $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->with(['fromOutlet', 'toOutlet', 'items'])
            ->orderByDesc('id');

        $user = auth()->user();
        if ($user && $user->assignedOutletId()) {
            $outletId = $user->assignedOutletId();
            // User only sees transfers where their outlet is either sender or receiver
            $query->where(function ($q) use ($outletId) {
                $q->where('from_outlet_id', $outletId)
                  ->orWhere('to_outlet_id', $outletId);
            });
        }

        if ($status = $this->request()->get('status')) {
            $query->where('status', $status);
        }

        if ($fromOutlet = $this->request()->get('from_outlet_id')) {
            $query->where('from_outlet_id', $fromOutlet);
        }

        if ($toOutlet = $this->request()->get('to_outlet_id')) {
            $query->where('to_outlet_id', $toOutlet);
        }

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('stock-transfers-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(0, 'desc')
            ->selectStyleSingle();
    }

    public function getColumns(): array
    {
        return [
            Column::make('DT_RowIndex')->title('#')->searchable(false)->orderable(false),
            Column::make('transfer_no')->title('Transfer #'),
            Column::make('transfer_date')->title('Date'),
            Column::make('from_outlet_name')->title('From Warehouse'),
            Column::make('to_outlet_name')->title('To Outlet'),
            Column::make('total_quantity')->title('Dispatched Qty')->searchable(false)->orderable(false),
            Column::make('status')->title('Status'),
            Column::computed('action')->title('Action')->exportable(false)->printable(false)->width(160)->addClass('text-center'),
        ];
    }
}

