<?php

namespace App\DataTables\Admin\Report\Accounting;

use App\Models\AccountingJournal;
use App\Models\Outlet;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\QueryDataTable;
use Yajra\DataTables\Services\DataTable;

class GeneralLedgerDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): QueryDataTable
    {
        $summaryQuery = clone $query;
        $summaryRow = DB::query()
            ->fromSub($summaryQuery, 'gl')
            ->selectRaw('ROUND(COALESCE(SUM(gl.debit),0),2) as total_debit, ROUND(COALESCE(SUM(gl.credit),0),2) as total_credit')
            ->first();

        $totalDebit = (float) ($summaryRow->total_debit ?? 0);
        $totalCredit = (float) ($summaryRow->total_credit ?? 0);

        return datatables()
            ->query($query)
            ->editColumn('outlet_name', fn ($row) => $row->outlet_name ?: 'Central Warehouse')
            ->editColumn('account_label', fn ($row) => trim(($row->account_code ?? '') . ' - ' . ($row->account_name ?? '')))
            ->editColumn('source_type', fn ($row) => ucwords(str_replace('_', ' ', (string) $row->source_type)))
            ->editColumn('debit', fn ($row) => number_format((float) $row->debit, 2))
            ->editColumn('credit', fn ($row) => number_format((float) $row->credit, 2))
            ->with([
                'summary' => [
                    'total_debit' => $totalDebit,
                    'total_credit' => $totalCredit,
                    'net_movement' => round($totalDebit - $totalCredit, 2),
                ],
            ])
            ->setRowId('id');
    }

    public function query(): QueryBuilder
    {
        $accountId = (int) $this->request()->get('account_id', 0);
        $sourceType = trim((string) $this->request()->get('source_type', ''));
        $fromDate = $this->request()->get('from_date') ?: now()->startOfMonth()->toDateString();
        $toDate = $this->request()->get('to_date') ?: now()->toDateString();

        $outletId = (auth()->check() && auth()->user()->outlet_id)
            ? (int) auth()->user()->outlet_id
            : ($this->request()->filled('outlet_id') ? (int) $this->request()->get('outlet_id') : null);

        $centralOutletName = (string) (Outlet::where('is_central', true)->value('name') ?? 'Central Warehouse');

        $query = DB::table('accounting_journal_lines as l')
            ->join('accounting_journals as j', 'j.id', '=', 'l.journal_id')
            ->join('accounting_accounts as a', 'a.id', '=', 'l.account_id');

        if (AccountingJournal::hasOutletColumn()) {
            $query->leftJoin('outlets as o', 'o.id', '=', 'j.outlet_id')
                ->select(
                    'l.id',
                    'j.journal_no',
                    'j.posting_date',
                    'j.reference_no',
                    'j.source_type',
                    'a.code as account_code',
                    'a.name as account_name',
                    DB::raw("CONCAT(a.code, ' - ', a.name) as account_label"),
                    'l.line_description',
                    'l.debit',
                    'l.credit',
                    DB::raw('COALESCE(o.name, ' . DB::getPdo()->quote($centralOutletName) . ') as outlet_name')
                );
        } else {
            $query->select(
                'l.id',
                'j.journal_no',
                'j.posting_date',
                'j.reference_no',
                'j.source_type',
                'a.code as account_code',
                'a.name as account_name',
                DB::raw("CONCAT(a.code, ' - ', a.name) as account_label"),
                'l.line_description',
                'l.debit',
                'l.credit',
                DB::raw("COALESCE(
                    (SELECT o.name FROM sales s JOIN outlets o ON o.id = s.outlet_id WHERE s.id = j.source_id AND j.source_type IN ('sale','sale_cancel','sale_receipt') LIMIT 1),
                    (SELECT o.name FROM expense_vouchers ev JOIN outlets o ON o.id = ev.outlet_id WHERE ev.id = j.source_id AND j.source_type IN ('expense_voucher','expense_voucher_reversal') LIMIT 1),
                    (SELECT o.name FROM stock_adjustments sa JOIN outlets o ON o.id = sa.outlet_id WHERE sa.id = j.source_id AND j.source_type IN ('stock_adjustment','stock_adjustment_reversal') LIMIT 1),
                    (SELECT o.name FROM cash_reconciliations cr JOIN outlets o ON o.id = cr.outlet_id WHERE cr.id = j.source_id AND j.source_type IN ('cash_reconciliation','cash_reconciliation_reversal') LIMIT 1),
                    " . DB::getPdo()->quote($centralOutletName) . "
                ) as outlet_name")
            );
        }

        $query->where('j.status', 'posted')
            ->whereDate('j.posting_date', '>=', $fromDate)
            ->whereDate('j.posting_date', '<=', $toDate)
            ->whereRaw("NOT EXISTS (SELECT 1 FROM sales s WHERE s.id = j.source_id AND j.source_type IN ('sale','sale_cancel') AND s.cancelled_at IS NOT NULL)")
            ->whereRaw("NOT EXISTS (SELECT 1 FROM purchases p WHERE p.id = j.source_id AND j.source_type IN ('purchase','purchase_cancel') AND p.cancelled_at IS NOT NULL)")
            ->orderBy('j.posting_date')
            ->orderBy('l.id');

        if ($accountId > 0) {
            $query->where('l.account_id', $accountId);
        }

        if ($sourceType !== '') {
            $query->where('j.source_type', $sourceType);
        }

        AccountingJournal::applyOutletFilter($query, $outletId, 'j');

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('general-ledger-table')
            ->columns($this->getColumns())
            ->minifiedAjax(request()->getRequestUri())
            ->orderBy(0)
            ->selectStyleSingle()
            ->parameters([
                'dom' => "<'row'l>Bfrtip",
                'buttons' => [
                    'colvis',
                    ['extend' => 'excel', 'footer' => true],
                    ['extend' => 'csv', 'footer' => true],
                    'reset',
                    'reload',
                ],
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::make('posting_date')->title('Date'),
            Column::make('outlet_name')->title('Outlet')->searchable(false)->orderable(false),
            Column::make('account_label')->name('a.code')->title('Account'),
            Column::make('journal_no')->title('Journal'),
            Column::make('reference_no')->title('Reference'),
            Column::make('source_type')->title('Source'),
            Column::make('line_description')->title('Description'),
            Column::make('debit')->title('Debit (Gh¢)')->searchable(false),
            Column::make('credit')->title('Credit (Gh¢)')->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'GeneralLedger_' . date('YmdHis');
    }
}
