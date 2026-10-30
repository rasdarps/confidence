<?php

namespace App\DataTables\Admin\Settings;

use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class ProductDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        $user = auth()->user();
        $requestedOutletId = $this->request()->get('outlet_id');
        if ($user && $user->outlet_id) {
            $effectiveOutletId = (int) $user->outlet_id;
        } elseif (!empty($requestedOutletId) && $requestedOutletId !== 'all') {
            $effectiveOutletId = (int) $requestedOutletId;
        } else {
            $effectiveOutletId = null;
        }

        return (new EloquentDataTable($query))
         ->addColumn('checkbox', function($row) {
            $hasBarcode = !empty(trim((string) $row->barcode));
            $disabledAttr = $hasBarcode ? '' : ' disabled';
            $titleAttr = $hasBarcode ? '' : ' title="Barcode not available for this product"';

            return '<input type="checkbox" class="product-checkbox" value="' . $row->id . '" data-printable="' . ($hasBarcode ? '1' : '0') . '"' . $disabledAttr . $titleAttr . '>';
        })
        ->addColumn('product_info', function($row) {
            return '
                <div class="d-flex align-items-center">
                    <div>
                        <strong>' . e($row->name) . '</strong>
                    </div>
                </div>
            ';
        })
        ->addColumn('category', function($row) {
            return $row->category ? e($row->category->name) : '<span class="text-muted">Uncategorized</span>';
        })
        ->editColumn('barcode', function($row) {
            $barcode = trim((string) ($row->barcode ?? ''));

            if ($barcode === '') {
                return '<span class="text-muted">N/A</span>';
            }

            return '<span class="font-weight-bold">' . e($barcode) . '</span>';
        })
        ->editColumn('cost_price', function($row) {
            return '<span class="font-weight-bold">₵' . number_format((float) ($row->cost_price ?? 0), 2) . '</span>';
        })
        ->editColumn('selling_price', function($row) {
            return '<span class="font-weight-bold">₵' . number_format((float) ($row->selling_price ?? 0), 2) . '</span>';
        })
        ->addColumn('stock', function($row) use ($effectiveOutletId, $user) {
            if ($effectiveOutletId) {
                $qty = (float) $row->quantityForOutlet($effectiveOutletId);
                $stockRec = $row->relationLoaded('outletStocks') 
                    ? $row->outletStocks->firstWhere('outlet_id', $effectiveOutletId)
                    : $row->outletStocks()->where('outlet_id', $effectiveOutletId)->first();
                $threshold = (float) ($stockRec ? $stockRec->stock_level : 1);
            } else {
                $qty = (float) $row->quantity;
                $threshold = (float) $row->stock_level;
            }

            if ($qty <= 0) {
                $badgeClass = 'danger';
                $statusText = 'Out of Stock';
            } elseif ($qty <= $threshold) {
                $badgeClass = 'warning';
                $statusText = 'Low Stock';
            } else {
                $badgeClass = 'success';
                $statusText = 'In Stock';
            }
            
            $html = '<div><span class="badge badge-' . $badgeClass . '">' . $statusText . '</span><br>';
            $html .= '<strong class="text-dark">' . number_format($qty, 0) . ' units</strong>';

            if ($effectiveOutletId && $user && $user->isCentral()) {
                $html .= '<br><span class="text-muted" style="font-size:10px;">Total Enterprise: ' . number_format($row->quantity, 0) . '</span>';
            } elseif (!$effectiveOutletId) {
                $html .= '<br><span class="badge bg-light text-muted border" style="font-size:9px;">Enterprise Total</span>';
            }

            $html .= '</div>';
            return $html;
        })
        ->addColumn('actions', function($row) use ($user) {
            $viewUrl = route('products.show', $row->id);
            $encryptedId = Crypt::encrypt($row->id);
            $editUrl = route('products.edit', $encryptedId);

            $buttons = [];
            $isCentral = $user ? $user->isCentral() : false;

            if (Gate::allows('product-list')) {
                $buttons[] = '
                    <button type="button" class="btn-action-view view-product text-nowrap"
                            data-id="' . $row->id . '"
                            data-name="' . e($row->name) . '"
                            title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>';
            }

            if (Gate::allows('product-edit') && $isCentral) {
                $buttons[] = '
                    <a href="' . $editUrl . '" class="btn-action-edit table-edit-btn text-nowrap"
                       title="Edit Product">
                        <i class="fas fa-edit"></i>
                    </a>';
            }

            if (Gate::allows('product-delete') && $isCentral) {
                $buttons[] = '
                    <button type="button" class="btn-action-delete delete-product delete-confirm-btn btn-delete text-nowrap"
                            data-id="' . e($encryptedId) . '" data-name="' . e($row->name) . '" title="Delete Product">
                        <i class="fas fa-trash-alt"></i>
                    </button>';
            }

            if (empty($buttons)) {
                return '<span class="text-muted small">No actions</span>';
            }

            return '<div class="d-inline-flex align-items-center flex-nowrap gap-1 action-buttons" role="group">' . implode('', $buttons) . '</div>';
        })
        ->editColumn('created_at', function($row) {
            return Carbon::parse($row->created_at)->format('d M, Y');
        })
        ->filterColumn('product_info', function($query, $keyword) {
            $query->where('name', 'like', "%{$keyword}%");
        })
        ->rawColumns(['checkbox','product_info', 'category', 'barcode', 'cost_price', 'selling_price', 'stock', 'actions'])
        ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Product $model): QueryBuilder
    {
        $category_id = $this->request()->get('category_id');
        $stock_filter = $this->request()->get('stock_filter');
        $requestedOutletId = $this->request()->get('outlet_id');
        $user = auth()->user();

        if ($user && $user->outlet_id) {
            $effectiveOutletId = (int) $user->outlet_id;
        } elseif (!empty($requestedOutletId) && $requestedOutletId !== 'all') {
            $effectiveOutletId = (int) $requestedOutletId;
        } else {
            $effectiveOutletId = null;
        }

        $query = $model->newQuery()
            ->with(['category', 'unit', 'outletStocks.outlet'])
            ->orderBy('created_at', 'desc');
    
        // Filter by category
        if ($this->request()->filled('category_id')) {
            $query->where('category_id', $category_id);
        }
        
        // Filter by stock status via outletStocks scopes
        if ($stock_filter) {
            if ($stock_filter == 'in_stock') {
                $query->inStock($effectiveOutletId);
            } elseif ($stock_filter == 'low_stock') {
                $query->lowStock($effectiveOutletId);
            } elseif ($stock_filter == 'out_of_stock') {
                $query->outOfStock($effectiveOutletId);
            }
        }

        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('product-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax(request()->getRequestUri())
                    ->orderBy(7, 'desc')
                    ->selectStyleSingle()
                    ->parameters([
                        'dom'          => "<'row'l>Bfrtip",
                        'buttons'      => ['colvis', 'excel', 'csv', 'reset', 'reload'],
                        'language'     => [
                                                'search' => 'Search Products:',
                                                'lengthMenu' => 'Show _MENU_ products',
                                                'info' => 'Showing _START_ to _END_ of _TOTAL_ products',
                                                'emptyTable' => 'No products found',
                                                'zeroRecords' => 'No matching products found',
                                                'infoEmpty' => 'Showing 0 to 0 of 0 products',
                                                'infoFiltered' => '(filtered from _MAX_ total products)'
                                            ],
                        'drawCallback' => "function() {
                            initializeToggles();
                            initializeDeleteButtons();
                            if (typeof initializeFeaturedToggles === 'function') {
                                initializeFeaturedToggles();
                            }
                            if (typeof initializeBulkActions === 'function') {
                                initializeBulkActions();
                                updateBulkActionButtons();
                            }
                            updateFilterSummary();
                        }",
                        'initComplete' => "function() {
                            addFilterSummary();
                        }",
                    ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        $columns = [
            Column::make('checkbox')->title('<input type="checkbox" id="select-all">')->width(30)
                ->searchable(false)->orderable(false)->exportable(false)->printable(false),
            Column::make('product_info')->title('Product')->width(250),
            Column::make('category')->title('Category')->width(120),
            Column::make('barcode')->title('Barcode')->width(150),
            Column::make('cost_price')->title('Cost_Price')->width(120),
            Column::make('selling_price')->title('Selling_Price')->width(120),
            Column::make('stock')->title('Stock')->width(100),
            Column::make('created_at')->title('Added')->width(100),
        ];

        if (Gate::allows('product-list') || Gate::allows('product-edit') || Gate::allows('product-delete')) {
            $columns[] = Column::make('actions')->title('Actions')->width(120)
                ->searchable(false)->orderable(false)->exportable(false)->printable(false);
        }

        return $columns;
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Product_' . date('YmdHis');
    }
}
