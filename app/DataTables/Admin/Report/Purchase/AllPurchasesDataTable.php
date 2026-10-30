<?php

namespace App\DataTables\Admin\Report\Purchase;

use App\Models\Purchase;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class AllPurchasesDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
             ->addColumn('edit_button', function($row) {
                $printUrl = route('print.purchase', Crypt::encrypt($row->id));
                $editUrl = route('purchases.edit', Crypt::encrypt($row->id));

                $user = auth()->user();
                $isCentral = $user ? $user->isCentral() : true;

                $canPrint = Gate::allows('purchase-print');
                $canEdit = Gate::allows('purchase-edit') && $isCentral;
                $canCancel = Gate::allows('purchase-cancel') && $isCentral;
                $editEnabled = (bool) config('pos.purchase_edit_enabled', false);
                $editWindowHours = max(24, min(720, (int) config('pos.purchase_edit_window_hours', 24)));
                $isWithinEditWindow = Carbon::parse($row->created_at)->addHours($editWindowHours)->isFuture();

                $buttons = [];

                if ($canPrint) {
                    $buttons[] = '<a href="' . $printUrl . '" class="btn btn-sm btn-info text-nowrap" style="display:inline-flex;align-items:center;gap:6px;width:auto;">Print <i class="fa fa-print"></i></a>';
                }

                if ($canEdit && $editEnabled && !$row->cancelled_at && $isWithinEditWindow) {
                    $buttons[] = '<a href="' . $editUrl . '" class="btn btn-sm btn-primary text-nowrap" style="display:inline-flex;align-items:center;gap:6px;width:auto;"><i class="fas fa-edit"></i> Edit</a>';
                }

                if ($canCancel && !$row->cancelled_at) {
                    $encryptedId = Crypt::encrypt($row->id);
                    $buttons[] = '<button type="button"
                        class="btn btn-sm btn-warning btn-cancel-purchase text-nowrap"
                        style="display:inline-flex;align-items:center;gap:6px;width:auto;"
                        data-id="' . $encryptedId . '"
                        data-no="' . e($row->purchase_no) . '">
                        <i class="fas fa-ban"></i> Cancel
                    </button>';
                }

                if (empty($buttons)) {
                    return '<span class="text-muted">No actions</span>';
                }

                return '<div class="d-inline-flex align-items-center flex-nowrap gap-2 action-buttons">' . implode('', $buttons) . '</div>';
            })

            ->addColumn('cancelled_status', function($row) {
                if ($row->cancelled_at) {
                    return '<span class="badge bg-danger" title="' . e($row->cancellation_reason) . '">Cancelled</span>';
                }
                return '<span class="badge bg-success">Active</span>';
            })

              ->addColumn('created_by_name', function($row) {
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

            ->addColumn('cancelled_by_name', function($row) {
                if (!Gate::allows('user-audit-view')) {
                    return '';
                }
                $viewer = auth()->user();
                $viewerIsSuperAdmin = $viewer && strtolower((string) ($viewer->user_type ?? '')) === 'super admin';
                $cancelledByUser = $row->cancelledBy;

                if (!$viewerIsSuperAdmin && $cancelledByUser && strtolower((string) ($cancelledByUser->user_type ?? '')) === 'super admin') {
                    return '';
                }

                return $cancelledByUser ? $cancelledByUser->name : '';
            })

            ->addColumn('supplier_name', function($row) {
                return $row->purchase_payment && $row->purchase_payment->supplier
                    ? $row->purchase_payment->supplier->name
                    : '';
            })

            ->addColumn('amount', function($row) {
                return $row->purchase_payment 
                    ? number_format($row->purchase_payment->total_amount, 2) 
                    : number_format(0, 2);
            })

            ->addColumn('payment_status_label', function($row) {
                $status = (string) optional($row->purchase_payment)->paid_status;

                if ($status === 'full_payment') {
                    return '<span class="badge bg-success">Full Payment</span>';
                }

                if ($status === 'partial_payment') {
                    return '<span class="badge bg-warning text-dark">Partial Payment</span>';
                }

                if ($status === 'full_credit') {
                    return '<span class="badge bg-danger">Full Credit</span>';
                }

                return '<span class="badge bg-secondary">Unknown</span>';
            })

             ->editColumn('date', function($row)
            {
                return  Carbon::parse($row->date)->format('d-m-Y');
            })

              ->editColumn('created_at', function($row)
            {
                return  Carbon::parse($row->created_at)->format('d-m-Y');
            })
            ->editColumn('updated_at', function($row) {
                return $row->updated_at
                    ? Carbon::parse($row->updated_at)->format('d-m-Y')
                    : '';
            })

            ->addColumn('outlet_name', function() {
                return '<span class="badge bg-primary">Central Warehouse</span>';
            })
            ->rawColumns(['outlet_name', 'edit_button', 'cancelled_status', 'payment_status_label'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Purchase $model): QueryBuilder
    {
        $query = $model->newQuery()
                 ->with(['purchase_payment', 'supplier', 'createdBy', 'cancelledBy'])
                 ->orderBy('date', 'desc');

        // Apply filters
        if ($purchaseinvoiceId = $this->request()->get('purchase_id')) {
            $query->where('id', $purchaseinvoiceId);
        }

        if ($supplierId = $this->request()->get('supplier_id')) {
            $query->whereHas('purchase_payment.supplier', function ($q) use ($supplierId) {
                $q->where('id', $supplierId);
            });
        }

        if ($createdBy = $this->request()->get('created_by')) {
            $query->where('created_by', $createdBy);
        }

        if ($fromDate = $this->request()->get('from_date')) {
            $query->whereDate('date', '>=', $fromDate);
        }

        if ($toDate = $this->request()->get('to_date')) {
            $query->whereDate('date', '<=', $toDate);
        }

        if ($paymentStatus = $this->request()->get('payment_status')) {
            $query->whereHas('purchase_payment', function ($q) use ($paymentStatus) {
                $q->where('paid_status', $paymentStatus);
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
                    ->setTableId('allpurchases-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax(request()->getRequestUri())
                    //->dom('Bfrtip')
                    ->orderBy(1)
                    ->selectStyleSingle()
                    ->footerCallback('function (row, data, start, end, display) {
                        var api = this.api();

                        var intVal = function (value) {
                            if (typeof value === "string") {
                                return value.replace(/[^0-9.-]/g, "") * 1;
                            }

                            if (typeof value === "number") {
                                return value;
                            }

                            return 0;
                        };

                        var amountColIndex = api.columns().indexes().toArray().find(function (idx) {
                            return (api.settings()[0].aoColumns[idx] || {}).data === "amount";
                        });

                        if (typeof amountColIndex === "undefined") {
                            return;
                        }

                        var totalAmount = api.column(amountColIndex, { page: "current" }).data().reduce(function (a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);

                        var formatValue = function (value) {
                            return Number(value).toLocaleString("en-US", {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        };

                        $(api.column(amountColIndex).footer()).html(formatValue(totalAmount));
                    }')
                    ->parameters([
                        'dom'          => "<'row'l>Bfrtip",  //row counter button
                        'buttons'      => [
                            'colvis',
                            ['extend' => 'excel', 'footer' => true],
                            ['extend' => 'csv', 'footer' => true],
                            'reset',
                            'reload'
                        ],
                    ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        $columns = [
            Column::make('date'),
            Column::make('purchase_no'),
            Column::make('supplier_name')->title('Supplier Name'),
            Column::make('outlet_name')->title('Warehouse / Station')->searchable(false),
            Column::make('payment_status_label')->title('Payment Status')->searchable(false),
            Column::make('amount')->title('Amount (Gh¢)'),
        ];

        if (Gate::allows('user-audit-view')) {
            $columns[] = Column::make('created_by_name')->title('Created By');
            $columns[] = Column::make('cancelled_by_name')->title('Cancelled By')
                ->searchable(true)->printable(true)->exportable(true);
        }

        if (Gate::allows('purchase-print') || Gate::allows('purchase-edit') || Gate::allows('purchase-cancel')) {
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
        return 'AllPurchases_' . date('YmdHis');
    }
}
