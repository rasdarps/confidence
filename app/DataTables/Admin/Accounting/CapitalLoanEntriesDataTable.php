<?php

namespace App\DataTables\Admin\Accounting;

use App\Models\CapitalLoanEntry;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class CapitalLoanEntriesDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->editColumn('transaction_date', function ($row) {
                return $row->transaction_date ? Carbon::parse($row->transaction_date)->format('Y-m-d') : '';
            })
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
            ->addColumn('entry_type_label', function ($row) {
                $type = (string) $row->entry_type;
                $label = match ($type) {
                    'owner_capital' => 'Owner Capital',
                    'bank_loan' => 'Bank Loan',
                    'individual_loan' => 'Individual Loan',
                    default => ucfirst(str_replace('_', ' ', $type)),
                };
                $badge = $type === 'owner_capital' ? 'bg-info text-dark' : 'bg-warning text-dark';

                return '<span class="badge ' . $badge . '">' . e($label) . '</span>';
            })
            ->addColumn('receive_into_label', function ($row) {
                return $row->receive_into === 'bank'
                    ? (optional($row->bankAccount)->name ?? 'Bank')
                    : 'Cash On Hand (1000)';
            })
            ->editColumn('amount', function ($row) {
                return '<span class="fw-semibold">' . number_format((float) $row->amount, 2) . '</span>';
            })
            ->addColumn('journal_no', function ($row) {
                return optional($row->journal)->journal_no ?: '-';
            })
            ->addColumn('accounting_status_badge', function ($row) {
                $status = (string) ($row->accounting_status ?? 'unposted');

                if ($status === 'posted') {
                    return '<span class="badge bg-success">Posted</span>';
                }

                if ($status === 'reversed') {
                    return '<span class="badge bg-danger">Reversed</span>';
                }

                return '<span class="badge bg-warning text-dark">Unposted</span>';
            })
            ->addColumn('actions', function ($row) {
                $canEdit = Gate::allows('capital-loan-edit');
                $canDelete = Gate::allows('capital-loan-delete');

                $outletId = (int) (optional($row->journal)->outlet_id ?? 0);

                $actions = '';
                if ($canEdit) {
                    $actions .= '<button type="button" class="btn-action-edit table-edit-btn js-edit-capital-loan" title="Edit Funding Entry"'
                        . ' data-id="' . (int) $row->id . '"'
                        . ' data-outlet_id="' . $outletId . '"'
                        . ' data-transaction_date="' . e($row->transaction_date ? Carbon::parse($row->transaction_date)->format('Y-m-d') : '') . '"'
                        . ' data-entry_type="' . e((string) $row->entry_type) . '"'
                        . ' data-receive_into="' . e((string) $row->receive_into) . '"'
                        . ' data-bank_account_id="' . (int) ($row->bank_account_id ?? 0) . '"'
                        . ' data-lender_name="' . e((string) ($row->lender_name ?? '')) . '"'
                        . ' data-amount="' . (float) $row->amount . '"'
                        . ' data-reference_no="' . e((string) ($row->reference_no ?? '')) . '"'
                        . ' data-description="' . e((string) ($row->description ?? '')) . '">'
                        . '<i class="fa fa-edit"></i></button>';
                }

                if ($canDelete) {
                    $actions .= '<button type="button" class="btn-action-delete delete-confirm-btn btn-delete js-delete-capital-loan" title="Delete Funding Entry" data-id="' . (int) $row->id . '"><i class="fa fa-trash"></i></button>';
                }

                return $actions ? '<div class="action-buttons">' . $actions . '</div>' : '-';
            })
            ->rawColumns(['outlet_name', 'entry_type_label', 'amount', 'accounting_status_badge', 'actions'])
            ->setRowId('id');
    }

    public function query(CapitalLoanEntry $model): QueryBuilder
    {
        return $model->newQuery()
            ->with(['bankAccount', 'journal.outlet'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('capital-loan-entries-table')
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
            Column::make('entry_type_label')->title('Type')->searchable(false),
            Column::make('receive_into_label')->title('Received Into')->searchable(false),
            Column::make('lender_name')->title('Lender/Source'),
            Column::make('amount')->title('Amount')->addClass('text-end'),
            Column::make('reference_no')->title('Reference'),
            Column::make('journal_no')->title('Journal No')->searchable(false),
            Column::make('accounting_status_badge')->title('Posting Status')->searchable(false),
            Column::computed('actions')->title('Actions')->exportable(false)->printable(false)->searchable(false)->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'CapitalLoanEntries_' . date('YmdHis');
    }
}
