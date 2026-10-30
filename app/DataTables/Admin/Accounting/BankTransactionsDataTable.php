<?php

namespace App\DataTables\Admin\Accounting;

use App\Models\BankTransaction;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class BankTransactionsDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('outlet_name', function ($row) {
                $outlet = optional(optional($row->journal)->outlet);
                if (!$outlet || !$outlet->id) {
                    return '<span class="text-muted">-</span>';
                }

                $badge = $outlet->is_central
                    ? '<span class="badge bg-info text-dark ms-1">Central</span>'
                    : '<span class="badge bg-secondary ms-1">Retail</span>';

                return e((string) $outlet->name) . $badge;
            })
            ->addColumn('bank_name', fn ($row) => optional($row->bankAccount)->name ?: '-')
            ->editColumn('type', function ($row) {
                $type = (string) $row->type;
                $label = ucwords(str_replace('_', ' ', $type));
                $inflowTypes = ['deposit', 'transfer_in', 'interest_income', 'receipt', 'reconciliation_adjustment'];
                $badgeClass = in_array($type, $inflowTypes, true) ? 'bg-success' : 'bg-danger';

                return '<span class="badge ' . $badgeClass . '">' . e($label) . '</span>';
            })
            ->editColumn('amount', fn ($row) => '<span class="fw-semibold">' . number_format((float) $row->amount, 2) . '</span>')
            ->editColumn('transaction_date', fn ($row) => optional($row->transaction_date)->format('Y-m-d'))
            ->addColumn('actions', function ($row) {
                $canEdit = Gate::allows('bank-transaction-edit');
                $canDelete = Gate::allows('bank-transaction-delete');

                $purchasePaymentId = 0;
                if ((string) $row->source_type === 'supplier_payment' && (int) ($row->source_id ?? 0) > 0) {
                    $purchasePaymentId = (int) (DB::table('purchase_payment_details as ppd')
                        ->join('purchase_payments as pp', 'pp.purchase_id', '=', 'ppd.purchase_id')
                        ->where('ppd.id', (int) $row->source_id)
                        ->value('pp.id') ?? 0);
                }

                $outletId = (int) (optional($row->journal)->outlet_id ?? 0);

                $editButton = '';
                if ($canEdit && (string) $row->type !== 'transfer_in') {
                    $editButton = '<button type="button" class="btn-action-edit table-edit-btn js-edit-bank-transaction" title="Edit Bank Transaction"'
                    . ' data-id="' . (int) $row->id . '"'
                    . ' data-bank_account_id="' . (int) $row->bank_account_id . '"'
                    . ' data-outlet_id="' . $outletId . '"'
                    . ' data-transaction_date="' . e(optional($row->transaction_date)->format('Y-m-d')) . '"'
                    . ' data-type="' . e((string) $row->type) . '"'
                    . ' data-amount="' . (float) $row->amount . '"'
                    . ' data-counterparty_bank_account_id="' . (int) ($row->counterparty_bank_account_id ?? 0) . '"'
                    . ' data-purchase_payment_id="' . $purchasePaymentId . '"'
                    . ' data-reference_no="' . e((string) ($row->reference_no ?? '')) . '"'
                    . ' data-description="' . e((string) ($row->description ?? '')) . '">'
                    . '<i class="fa fa-edit"></i></button>';
                }

                $deleteButton = $canDelete
                    ? '<button type="button" class="btn-action-delete delete-confirm-btn btn-delete js-delete-bank-transaction" title="Delete Bank Transaction" data-id="' . (int) $row->id . '"><i class="fa fa-trash"></i></button>'
                    : '';

                $actions = $editButton . $deleteButton;
                return $actions ? '<div class="action-buttons">' . $actions . '</div>' : '-';
            })
            ->rawColumns(['outlet_name', 'type', 'amount', 'actions'])
            ->setRowId('id');
    }

    public function query(BankTransaction $model): QueryBuilder
    {
        return $model->newQuery()
            ->with(['bankAccount', 'journal.outlet'])
            ->latest('transaction_date')
            ->latest('id');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('bank-transactions-table')
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
        return [
            Column::make('transaction_date')->title('Date'),
            Column::computed('outlet_name')->title('Outlet')->searchable(false),
            Column::computed('bank_name')->title('Bank Account'),
            Column::make('type')->title('Type'),
            Column::make('amount')->title('Amount')->addClass('text-end'),
            Column::make('reference_no')->title('Reference'),
            Column::make('description')->title('Description'),
            Column::computed('actions')->title('Actions')->exportable(false)->printable(false)->searchable(false)->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'BankTransactions_' . date('YmdHis');
    }
}
