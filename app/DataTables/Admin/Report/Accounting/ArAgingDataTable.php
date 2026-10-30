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

class ArAgingDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): QueryDataTable
    {
        $summaryQuery = clone $query;
        $summaryRow = DB::query()
            ->fromSub($summaryQuery, 'ar')
            ->selectRaw('
                ROUND(COALESCE(SUM(ar.current), 0), 2) as total_current,
                ROUND(COALESCE(SUM(ar.days_31_60), 0), 2) as total_31_60,
                ROUND(COALESCE(SUM(ar.days_61_90), 0), 2) as total_61_90,
                ROUND(COALESCE(SUM(ar.days_90_plus), 0), 2) as total_90_plus,
                ROUND(COALESCE(SUM(ar.balance), 0), 2) as total_balance
            ')
            ->first();

        return datatables()
            ->query($query)
            ->editColumn('customer_name', fn ($row) => $row->customer_name ?: ('Customer #' . $row->entity_id))
            ->editColumn('customer_phone', fn ($row) => $row->customer_phone ?: '—')
            ->editColumn('outlet_name', fn ($row) => $row->outlet_name ?: 'Central Warehouse')
            ->editColumn('current', fn ($row) => number_format((float) $row->current, 2))
            ->editColumn('days_31_60', fn ($row) => number_format((float) $row->days_31_60, 2))
            ->editColumn('days_61_90', fn ($row) => number_format((float) $row->days_61_90, 2))
            ->editColumn('days_90_plus', fn ($row) => number_format((float) $row->days_90_plus, 2))
            ->editColumn('balance', fn ($row) => number_format((float) $row->balance, 2))
            ->with([
                'summary' => [
                    'total_current' => (float) ($summaryRow->total_current ?? 0),
                    'total_31_60' => (float) ($summaryRow->total_31_60 ?? 0),
                    'total_61_90' => (float) ($summaryRow->total_61_90 ?? 0),
                    'total_90_plus' => (float) ($summaryRow->total_90_plus ?? 0),
                    'total_balance' => (float) ($summaryRow->total_balance ?? 0),
                ],
            ])
            ->setRowId('entity_id');
    }

    public function query(): QueryBuilder
    {
        $asOfDate = $this->request()->get('to_date') ?: now()->toDateString();
        $customerId = (int) $this->request()->get('customer_id', 0);
        $agingBucket = trim((string) $this->request()->get('aging_bucket', ''));

        $outletId = (auth()->check() && auth()->user()->outlet_id)
            ? (int) auth()->user()->outlet_id
            : ($this->request()->filled('outlet_id') ? (int) $this->request()->get('outlet_id') : null);

        $accountId = DB::table('accounting_accounts')->where('code', '1100')->value('id');
        $centralOutletName = (string) (Outlet::where('is_central', true)->value('name') ?? 'Central Warehouse');

        $baseQuery = DB::table('accounting_journal_lines as l')
            ->join('accounting_journals as j', 'j.id', '=', 'l.journal_id')
            ->leftJoin('customers as c', 'c.id', '=', 'l.entity_id')
            ->where('j.status', 'posted')
            ->whereDate('j.posting_date', '<=', $asOfDate)
            ->where('l.entity_type', 'customer')
            ->whereRaw("NOT EXISTS (SELECT 1 FROM sales s WHERE s.id = j.source_id AND j.source_type IN ('sale','sale_cancel') AND s.cancelled_at IS NOT NULL)");

        if (AccountingJournal::hasOutletColumn()) {
            $baseQuery->leftJoin('outlets as o', 'o.id', '=', 'j.outlet_id')
                ->selectRaw(
                    "l.entity_id,
                     MAX(c.name) as customer_name,
                     MAX(c.mobile_no) as customer_phone,
                     GROUP_CONCAT(DISTINCT COALESCE(o.name, " . DB::getPdo()->quote($centralOutletName) . ") ORDER BY o.name SEPARATOR ', ') as outlet_name,
                     MAX(j.posting_date) as last_date,
                     GREATEST(0, TIMESTAMPDIFF(DAY, MAX(j.posting_date), ?)) as days,
                     ROUND(SUM(l.debit - l.credit), 2) as balance",
                    [$asOfDate]
                );
        } else {
            $baseQuery->selectRaw(
                "l.entity_id,
                 MAX(c.name) as customer_name,
                 MAX(c.mobile_no) as customer_phone,
                 " . DB::getPdo()->quote($centralOutletName) . " as outlet_name,
                 MAX(j.posting_date) as last_date,
                 GREATEST(0, TIMESTAMPDIFF(DAY, MAX(j.posting_date), ?)) as days,
                 ROUND(SUM(l.debit - l.credit), 2) as balance",
                [$asOfDate]
            );
        }

        $baseQuery->groupBy('l.entity_id');

        if (!$accountId) {
            return $baseQuery->whereRaw('1 = 0');
        }

        $baseQuery->where('l.account_id', $accountId);

        if ($customerId > 0) {
            $baseQuery->where('l.entity_id', $customerId);
        }

        AccountingJournal::applyOutletFilter($baseQuery, $outletId, 'j');

        $outerQuery = DB::query()
            ->fromSub($baseQuery, 'x')
            ->selectRaw(
                'x.entity_id,
                 x.customer_name,
                 x.customer_phone,
                 x.outlet_name,
                 x.last_date,
                 x.days,
                 CASE WHEN x.days <= 30 THEN x.balance ELSE 0 END as current,
                 CASE WHEN x.days > 30 AND x.days <= 60 THEN x.balance ELSE 0 END as days_31_60,
                 CASE WHEN x.days > 60 AND x.days <= 90 THEN x.balance ELSE 0 END as days_61_90,
                 CASE WHEN x.days > 90 THEN x.balance ELSE 0 END as days_90_plus,
                 x.balance'
            )
            ->whereRaw('ABS(x.balance) > 0.0001');

        if ($agingBucket === '0_30') {
            $outerQuery->where('x.days', '<=', 30);
        } elseif ($agingBucket === '31_60') {
            $outerQuery->whereBetween('x.days', [31, 60]);
        } elseif ($agingBucket === '61_90') {
            $outerQuery->whereBetween('x.days', [61, 90]);
        } elseif ($agingBucket === '90_plus') {
            $outerQuery->where('x.days', '>', 90);
        }

        return $outerQuery;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('ar-aging-table')
            ->columns($this->getColumns())
            ->minifiedAjax(request()->getRequestUri())
            ->orderBy(8, 'desc')
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
            Column::make('customer_name')->title('Customer'),
            Column::make('customer_phone')->title('Phone'),
            Column::make('outlet_name')->title('Outlet(s)')->searchable(false)->orderable(false),
            Column::make('last_date')->title('Last Activity'),
            Column::make('current')->title('0-30 Days (Gh¢)')->searchable(false),
            Column::make('days_31_60')->title('31-60 Days (Gh¢)')->searchable(false),
            Column::make('days_61_90')->title('61-90 Days (Gh¢)')->searchable(false),
            Column::make('days_90_plus')->title('90+ Days (Gh¢)')->searchable(false),
            Column::make('balance')->title('Total Balance (Gh¢)')->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'ARAging_' . date('YmdHis');
    }
}
