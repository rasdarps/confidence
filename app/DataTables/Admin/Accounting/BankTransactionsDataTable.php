<?php

namespace App\DataTables\Admin\Accounting;

use App\Models\BankTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class BankTransactionsDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('bank_name', fn ($row) => optional($row->bankAccount)->name)
            ->editColumn('amount', fn ($row) => number_format((float) $row->amount, 2))
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

                $editButton = '';
                if ($canEdit && (string) $row->type !== 'transfer_in') {
                    $editButton = '<button type="button" class="btn btn-sm btn-primary js-edit-bank-transaction text-nowrap" style="display:inline-flex;align-items:center;gap:6px;width:auto;"'
                    . ' data-id="' . (int) $row->id . '"'
                    . ' data-bank_account_id="' . (int) $row->bank_account_id . '"'
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
                    ? '<button type="button" class="btn btn-sm btn-danger js-delete-bank-transaction text-nowrap" style="display:inline-flex;align-items:center;gap:6px;width:auto;" data-id="' . (int) $row->id . '"><i class="fa fa-trash"></i></button>'
                    : '';

                $actions = $editButton . $deleteButton;
                return $actions ? '<div class="d-inline-flex align-items-center flex-nowrap gap-2 action-buttons">' . $actions . '</div>' : '-';
            })
            ->rawColumns(['actions'])
            ->setRowId('id');
    }

    public function query(BankTransaction $model): QueryBuilder
    {
        return $model->newQuery()->with('bankAccount')->latest('transaction_date')->latest('id');
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
            Column::computed('bank_name')->title('Bank'),
            Column::make('type')->title('Type'),
            Column::make('amount')->title('Amount'),
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
