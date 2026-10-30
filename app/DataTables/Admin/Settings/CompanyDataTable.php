<?php

namespace App\DataTables\Admin\Settings;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class CompanyDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('profile_status', function($row) {
                return '<span class="badge bg-primary">Corporate Entity (HQ)</span>';
            })
            ->addColumn('edit_button', function($row) {
                    $editUrl = route('companies.edit', Crypt::encrypt($row->id));
                    $deleteUrl = route('companies.destroy', Crypt::encrypt($row->id));
                    $csrf = csrf_field();
                    $method = method_field('DELETE');

                    $buttons = [];

                    if (Gate::allows('company-edit')) {
                        $buttons[] = '
                        <form class="d-inline-block m-0" action="'.$editUrl.'" method="GET">
                            '.$csrf.'
                            <button class="btn-action-edit table-edit-btn" title="Edit Company">
                                <i class="fas fa-edit"></i>
                            </button>
                        </form>';
                    }

                    if (Gate::allows('company-delete') && Company::count() > 1) {
                        $buttons[] = '
                        <form class="d-inline-block m-0" action="'.$deleteUrl.'" method="POST">
                            '.$csrf.'
                            '.$method.'
                            <button type="button" class="btn-action-delete delete-confirm-btn btn-delete" title="Delete Company">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </form>';
                    }

                    if (empty($buttons)) {
                        return '<span class="text-muted">No actions</span>';
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

            ->rawColumns(['profile_status', 'edit_button'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Company $model): QueryBuilder
    {
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('company-table')
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
            Column::make('name')->title('Company Name'),
            Column::make('profile_status')->title('Entity Scope')->searchable(false),
            Column::make('email')->title('Company Email'),
            Column::make('phone')->title('Company Phone'),
            Column::make('tin_number')->title('Company TIN Number'),
            // Column::make('created_at'),
            // Column::make('updated_at'),
        ];

        if (Gate::allows('company-edit') || Gate::allows('company-delete')) {
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
        return 'Company_' . date('YmdHis');
    }
}
