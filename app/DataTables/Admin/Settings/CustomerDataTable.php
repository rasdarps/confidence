<?php

namespace App\DataTables\Admin\Settings;

use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class CustomerDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->editColumn('name', function($row) {
                $isCash = (int) $row->id === 1 || strtolower((string) $row->name) === 'cash_sales';
                if ($isCash) {
                    return e($row->name) . ' <span class="badge bg-secondary ms-1">Default Walk-In</span>';
                }
                return e($row->name);
            })
            ->addColumn('registration_origin', function($row) {
                if ((int) $row->id === 1 || strtolower((string) $row->name) === 'cash_sales') {
                    return '<span class="badge bg-secondary">System Default</span>';
                }
                $outlet = optional(optional($row->createdBy)->outlet);
                if ($outlet && $outlet->name) {
                    $badgeClass = $outlet->is_central ? 'bg-primary' : 'bg-info';
                    return '<span class="badge ' . $badgeClass . '">' . e($outlet->name) . '</span>';
                }
                return '<span class="badge bg-secondary">Central HQ</span>';
            })
            ->addColumn('total_due_formatted', function($row) {
                $due = round((float) ($row->total_due ?? 0), 2);
                if ($due > 0) {
                    return '<span class="badge bg-danger text-white"> ' . number_format($due, 2) . '</span>';
                }
                return '<span class="text-success fw-bold"> 0.00</span>';
            })
            ->addColumn('edit_button', function($row) {
                    $editUrl = route('customers.edit', Crypt::encrypt($row->id));
                    $deleteUrl = route('customers.destroy', Crypt::encrypt($row->id));
                    $csrf = csrf_field();
                    $method = method_field('DELETE');

                    $buttons = [];
                    $isCash = (int) $row->id === 1 || strtolower((string) $row->name) === 'cash_sales';

                    if (Gate::allows('customer-edit')) {
                        $buttons[] = '
                        <form class="d-inline-block m-0" action="'.$editUrl.'" method="GET">
                            '.$csrf.'
                            <button class="btn-action-edit table-edit-btn" title="Edit Customer">
                                <i class="fas fa-edit"></i>
                            </button>
                        </form>';
                    }

                    if (Gate::allows('customer-delete') && !$isCash) {
                        $buttons[] = '
                        <form class="d-inline-block m-0" action="'.$deleteUrl.'" method="POST">
                            '.$csrf.'
                            '.$method.'
                            <button type="button" class="btn-action-delete delete-confirm-btn btn-delete" title="Delete Customer">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </form>';
                    }

                    if (empty($buttons)) {
                        return '<span class="text-muted small">System Locked</span>';
                    }

                    return '<div class="d-inline-flex align-items-center flex-nowrap gap-2 action-buttons">' . implode('', $buttons) . '</div>';
                })
            
            ->editColumn('created_at', function($row)
            {
                return  Carbon::parse($row->created_at)->format('jS F, Y g:i A');
            })
            ->editColumn('updated_at', function($row) {
                return $row->updated_at
                    ? Carbon::parse($row->updated_at)->format('jS F, Y g:i A')
                    : '';
            })

            ->rawColumns(['name', 'registration_origin', 'total_due_formatted', 'edit_button'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Customer $model): QueryBuilder
    {
        return $model->newQuery()
            ->with(['createdBy.outlet:id,name,is_central'])
            ->withSum('payments as total_due', 'due_amount')
            ->orderBy('name', 'asc');
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('customer-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax(request()->getRequestUri())
                    //->dom('Bfrtip')
                    ->orderBy(1)
                    ->selectStyleSingle()
                    ->parameters([
                        'dom'          => "<'row'l>Bfrtip",  //row counter button
                        'buttons'      => ['colvis', 'excel', 'csv', 'reset', 'reload'],
                    ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        $columns = [
            Column::make('name')->title('Customer Name'),
            Column::make('registration_origin')->title('Registration Origin')->searchable(false),
            Column::make('mobile_no')->title('Mobile No'),
            Column::make('email')->title('Email'),
            Column::make('total_due_formatted')->title('Total Due (Gh¢)')->searchable(false),
            Column::make('address')->title('Address'),
        ];

        if (Gate::allows('customer-edit') || Gate::allows('customer-delete')) {
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
        return 'Customer_' . date('YmdHis');
    }
}
