<?php

namespace App\DataTables\Admin\Report;

use App\Models\ExpenseVoucherItem;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class ExpenseReportDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('expense_date', function ($row) {
                return $row->voucher && $row->voucher->voucher_date
                    ? Carbon::parse($row->voucher->voucher_date)->format('d-m-Y')
                    : '';
            })
            ->addColumn('reference_no', function ($row) {
                return $row->voucher->reference_no ?? '';
            })
            ->addColumn('category_name', function ($row) {
                return $row->category->name ?? '';
            })
            ->addColumn('created_by_name', function ($row) {
                if (!Gate::allows('user-audit-view')) {
                    return '';
                }
                $viewer = auth()->user();
                $viewerIsSuperAdmin = $viewer && strtolower((string) ($viewer->user_type ?? '')) === 'super admin';
                $createdByUser = $row->voucher ? $row->voucher->createdBy : null;

                if (!$viewerIsSuperAdmin && $createdByUser && strtolower((string) ($createdByUser->user_type ?? '')) === 'super admin') {
                    return '';
                }

                return $createdByUser
                    ? $createdByUser->name
                    : '';
            })
            ->addColumn('updated_by_name', function ($row) {
                if (!Gate::allows('user-audit-view')) {
                    return '';
                }
                $viewer = auth()->user();
                $viewerIsSuperAdmin = $viewer && strtolower((string) ($viewer->user_type ?? '')) === 'super admin';
                $updatedByUser = $row->voucher ? $row->voucher->updatedBy : null;

                if (!$viewerIsSuperAdmin && $updatedByUser && strtolower((string) ($updatedByUser->user_type ?? '')) === 'super admin') {
                    return '';
                }

                return $updatedByUser
                    ? $updatedByUser->name
                    : '';
            })
            ->editColumn('amount', function ($row) {
                return number_format((float) $row->amount, 2);
            })
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(ExpenseVoucherItem $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->with(['voucher.createdBy', 'voucher.updatedBy', 'category'])
            ->whereHas('voucher')
            ->orderBy('id', 'desc');

        if ($category = $this->request()->get('category')) {
            $query->where('expense_category_id', $category);
        }

        if ($createdBy = $this->request()->get('created_by')) {
            $query->whereHas('voucher', function ($q) use ($createdBy) {
                $q->where('created_by', $createdBy);
            });
        }

        if ($fromDate = $this->request()->get('from_date')) {
            $query->whereHas('voucher', function ($q) use ($fromDate) {
                $q->whereDate('voucher_date', '>=', $fromDate);
            });
        }

        if ($toDate = $this->request()->get('to_date')) {
            $query->whereHas('voucher', function ($q) use ($toDate) {
                $q->whereDate('voucher_date', '<=', $toDate);
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
            ->setTableId('expensereport-table')
            ->columns($this->getColumns())
            ->minifiedAjax(request()->getRequestUri())
            ->orderBy(0, 'desc')
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

                var resolveIndex = function (columnDataName) {
                    return api.columns().indexes().toArray().find(function (idx) {
                        return (api.settings()[0].aoColumns[idx] || {}).data === columnDataName;
                    });
                };

                var amountColIndex = resolveIndex("amount");
                if (typeof amountColIndex === "undefined") {
                    return;
                }

                var pageTotal = api.column(amountColIndex, { page: "current" }).data().reduce(function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0);

                var grandTotal = api.column(amountColIndex).data().reduce(function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0);

                var formatValue = function (value) {
                    return Number(value).toLocaleString("en-US", {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                };

                $(api.column(amountColIndex).footer()).html(
                    "Page: " + formatValue(pageTotal) + "<br>Total: " + formatValue(grandTotal)
                );
            }')
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

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        $columns = [
            Column::make('expense_date')->title('Date'),
            Column::make('reference_no')->title('Reference No'),
            Column::make('category_name')->title('Category'),
            Column::make('amount')->title('Amount (Gh¢)'),
            Column::make('description')->title('Description'),
        ];

        if (Gate::allows('user-audit-view')) {
            $columns[] = Column::make('created_by_name')->title('Created By');
            $columns[] = Column::make('updated_by_name')->title('Updated By');
        }

        return $columns;
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'ExpenseReport_' . date('YmdHis');
    }
}
