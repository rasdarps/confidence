{{-- filepath: c:\xampp\htdocs\rictPOS\resources\views\backend\product\index.blade.php --}}
@php
use Illuminate\Support\Facades\Crypt;
@endphp

@extends('layout.main')
@section('title') {{'View Product'}} @endsection

@section('content')

<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header with-border d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
                        <span class="card-title mb-0" style="font-size:18px; font-weight: 600; color: #034141;">
                            <i class="fas fa-boxes mr-1 me-1" style="color: #0F766E;"></i> Products Management
                        </span>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            @if(auth()->user()->isCentral() && auth()->user()->can('product-create'))
                                <a href="{{route('products.create')}}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1 shadow-sm px-3" style="font-size: 13px; height: 34px; border-radius: 6px;">
                                    <i class="fas fa-plus"></i> <span>Create Product</span>
                                </a>
                                <button type="button" class="btn btn-sm btn-success d-inline-flex align-items-center gap-1 shadow-sm px-3" data-bs-toggle="modal" data-bs-target="#productImportModal" style="font-size: 13px; height: 34px; border-radius: 6px;">
                                    <i class="fas fa-file-excel"></i> <span>Import Excel</span>
                                </button>
                            @else
                                <span class="badge bg-secondary py-2 px-3" style="font-size: 12px;"><i class="fas fa-store me-1"></i> {{ auth()->user()->outlet->name ?? 'Outlet Branch' }}: Catalog View</span>
                            @endif
                            <button type="button" class="btn btn-sm btn-dark d-inline-flex align-items-center gap-1 shadow-sm px-3" id="print-barcodes-btn" style="display: none; font-size: 13px; height: 34px; border-radius: 6px;">
                                <i class="fas fa-barcode"></i> <span>Print Labels</span>
                            </button>
                            <small id="selected-products-hint" class="text-muted ml-2" style="display:none; font-size: 12px;"></small>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <div class="alert alert-info d-flex align-items-center mb-3">
                            <i class="fas fa-boxes fa-lg mr-3 me-2"></i>
                            <div>
                                <strong>Central Product Catalog:</strong> Master products, barcodes, and units are centrally managed at Headquarters. Stock is held across Central Warehouse and retail outlets, and moved between stations via stock transfers.
                            </div>
                        </div>

                        <!-- Filter Section -->
                        <div class="filter-section mb-3 p-3 rounded" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                            <div class="row align-items-end g-2">
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group mb-0">
                                        <label class="small text-muted font-weight-bold mb-1">Category</label>
                                        <select id="filter-category" class="form-control select2">
                                            <option value="">All Categories</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                @if(auth()->user()->isCentral() && isset($outlets))
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group mb-0">
                                        <label class="small text-muted font-weight-bold mb-1">Outlet Station</label>
                                        <select id="filter-outlet" class="form-control select2">
                                            <option value="">All Outlets (Enterprise)</option>
                                            @foreach($outlets as $outlet)
                                                <option value="{{ $outlet->id }}">{{ $outlet->name }} {{ $outlet->is_central ? '(Central)' : '' }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                @endif
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group mb-0">
                                        <label class="small text-muted font-weight-bold mb-1">Stock Status</label>
                                        <select id="filter-stock" class="form-control" style="height: 38px;">
                                            <option value="">All Statuses</option>
                                            <option value="in_stock">In Stock</option>
                                            <option value="low_stock">Low Stock</option>
                                            <option value="out_of_stock">Out of Stock</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-dark flex-fill d-inline-flex align-items-center justify-content-center gap-1 shadow-sm" id="apply-filters" style="background:#034141; border-color:#034141; height: 38px; font-size: 13px; font-weight: 500; border-radius: 6px;">
                                            <i class="fas fa-filter"></i> Filter
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary flex-fill d-inline-flex align-items-center justify-content-center gap-1" id="btn-reset-filters" title="Reset all filters" style="height: 38px; font-size: 13px; font-weight: 500; border-radius: 6px;">
                                            <i class="fas fa-undo-alt"></i> Reset
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Filter Summary -->
                        <div id="filter-summary" class="filter-summary" style="display: none;">
                            <div class="d-flex align-items-center flex-wrap gap-2 flex-grow-1">
                                <span class="filter-summary-label">
                                    <i class="fas fa-filter text-teal me-1" style="color: #0F766E;"></i>
                                    Active Filters:
                                </span>
                                <span id="filter-summary-text" class="d-flex flex-wrap gap-1 align-items-center"></span>
                            </div>
                            <button type="button" class="btn-clear-all" id="clear-filters">
                                <i class="fas fa-times-circle"></i> Clear All
                            </button>
                        </div>
                        
                        <!-- Products Table -->
                        <div class="table-responsive">
                            {!! $dataTable->table() !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="productViewModal" tabindex="-1" role="dialog" aria-labelledby="productViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 12px; overflow: hidden; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.18);">
            <div class="modal-header" style="background: linear-gradient(135deg, #034141 0%, #0F766E 100%); color: #fff; padding: 14px 20px;">
                <h5 class="modal-title font-weight-bold d-flex align-items-center mb-0" id="productViewModalLabel" style="color: #fff; font-size: 16px;">
                    <i class="fas fa-boxes mr-2 me-2"></i> Product Master Overview
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="filter: brightness(0) invert(1); opacity: 0.85;"></button>
            </div>
            <div class="modal-body p-3" id="product-details-content">
                <div class="text-center py-5">
                    <div class="spinner-border" role="status" style="color: #0F766E;">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading product details...</p>
                </div>
            </div>
            <div class="modal-footer py-2 px-3" style="background: #f8fafc;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="productImportModal" tabindex="-1" aria-labelledby="productImportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="productImportModalLabel">
                    <i class="fas fa-file-excel"></i> Import Products from Excel
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="productImportForm" enctype="multipart/form-data" novalidate>
                @csrf
                <div class="modal-body">
                    <p class="mb-2">Upload an Excel or CSV file with these columns:</p>
                    <div class="mb-3">
                        <code>name</code>, <code>category_name</code>, <code>unit_name</code>, <code>stock_level</code>
                    </div>
                    <div class="alert alert-info mb-3">
                        Category and unit names must already exist in the system. Barcode is generated automatically.
                    </div>
                    <div class="form-group">
                        <label for="product_import_file"><strong>Select file</strong></label>
                        <input type="file" name="file" id="product_import_file" class="form-control" accept=".xls,.xlsx,.csv">
                        <small class="text-muted">Maximum file size: 5MB</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-upload"></i> Import Now
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
{{ $dataTable->scripts() }}
    <script>
    // ============ DEFINE MISSING FUNCTION FIRST ============
    window.addFilterSummary = function() {
        console.log('DataTable initialized - setting up filters');
        // Initialize all toggle functions after DataTable loads
        if (typeof initializeToggles === 'function') {
            initializeToggles();
            initializeDeleteButtons();
        }
        if (typeof initializeFeaturedToggles === 'function') {
            initializeFeaturedToggles();
        }
        if (typeof initializeBulkActions === 'function') {
            initializeBulkActions();
        }
        if (typeof updateBulkActionButtons === 'function') {
            updateBulkActionButtons();
        }
        updateFilterSummary();
    };
    
    $(document).ready(function() {
        var selectedProductIds = new Set();

        // Initialize Select2
       
        
        // Global AJAX setup
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $('#productImportForm').on('submit', function(e) {
            e.preventDefault();

            $('#productImportResult').remove();

            const fileInput = $('#product_import_file')[0];
            if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                Notiflix.Notify.failure('Please select a file before importing.');
                return;
            }

            var formData = new FormData(this);

            Notiflix.Loading.standard('Importing products, please wait...');

            $.ajax({
                url: '{{ route('products.import') }}',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'Accept': 'application/json'
                },
                success: function(response) {
                    Notiflix.Loading.remove();

                    const summary = response.summary || {};
                    const failedRows = Array.isArray(summary.errors) ? summary.errors : [];
                    const hasSkippedRows = (summary.failed || 0) > 0;

                    if (response.status === 'success') {
                        let html = `<strong>${response.message || 'Products imported successfully'}</strong>`;
                        html += `<div class="mt-2">Imported: ${summary.imported || 0} | Skipped: ${summary.failed || 0}</div>`;

                        if (hasSkippedRows) {
                            html += '<div class="mt-3"><strong>Skipped rows:</strong><ul class="mb-0">';
                            failedRows.forEach(function(item) {
                                const rowMessages = Array.isArray(item.messages) ? item.messages.join(' ') : '';
                                html += `<li>Row ${item.row}: ${rowMessages}</li>`;
                            });
                            html += '</ul></div>';
                        }

                        if (!hasSkippedRows) {
                            $('#productImportForm')[0].reset();
                            $('#productImportModal').modal('hide');
                            Notiflix.Notify.success(response.message || 'Products imported successfully');
                            if (window.LaravelDataTables && window.LaravelDataTables['products-table']) {
                                window.LaravelDataTables['products-table'].ajax.reload();
                            }
                        } else {
                            $('#productImportModal').find('.modal-body').prepend(
                                `<div class="alert alert-warning mt-0" id="productImportResult">${html}</div>`
                            );
                            Notiflix.Notify.warning('Import completed with some skipped rows. Review the details in the modal.');
                            if (window.LaravelDataTables && window.LaravelDataTables['products-table']) {
                                window.LaravelDataTables['products-table'].ajax.reload();
                            }
                        }
                    } else {
                        Notiflix.Notify.warning(response.message || 'Import completed with warnings');
                        $('#productImportModal').find('.modal-body').prepend(
                            `<div class="alert alert-warning mt-0" id="productImportResult">${response.message || 'Import completed with warnings'}</div>`
                        );
                    }
                },
                error: function(xhr) {
                    Notiflix.Loading.remove();

                    let message = 'Failed to import products';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                        if (xhr.responseJSON.errors) {
                            const firstError = Object.values(xhr.responseJSON.errors).flat()[0];
                            if (firstError) {
                                message = firstError;
                            }
                        }
                    }

                    Notiflix.Notify.failure(message);
                    $('#productImportModal').find('.modal-body').prepend(
                        `<div class="alert alert-danger mt-0" id="productImportResult">${message}</div>`
                    );
                }
            });
        });

        $('#productImportModal').on('hidden.bs.modal', function() {
            $('#productImportResult').remove();
            $('#productImportForm')[0].reset();
        });
        
        // ============ DEFINE ALL FUNCTIONS GLOBALLY ============
        
        // Update filter summary
        window.updateFilterSummary = function() {
            var filters = [];
            
            // Get current filter values
            var categoryId = $('#filter-category').val();
            var outletId = $('#filter-outlet').val();
            var stock = $('#filter-stock').val();
            
            // Get selected text for display
            var categoryName = $('#filter-category option:selected').text();
            var outletName = $('#filter-outlet option:selected').text();
            var stockText = $('#filter-stock option:selected').text();
            
            // Build filter badges
            if (categoryId && categoryId !== '') {
                filters.push('<span class="badge" style="background:#0F766E; color:#fff;"><i class="fas fa-sitemap me-1"></i> Category: ' + categoryName + '</span>');
            }
            if (outletId && outletId !== '') {
                filters.push('<span class="badge" style="background:#034141; color:#fff;"><i class="fas fa-warehouse me-1"></i> Station: ' + outletName + '</span>');
            }
            if (stock && stock !== '') {
                var stockBg = '#10B981';
                var stockIcon = 'fas fa-check-circle';
                if (stock == 'low_stock') {
                    stockBg = '#F59E0B';
                    stockIcon = 'fas fa-exclamation-triangle';
                } else if (stock == 'out_of_stock') {
                    stockBg = '#EF4444';
                    stockIcon = 'fas fa-times-circle';
                }
                filters.push('<span class="badge text-white" style="background:' + stockBg + ';"><i class="' + stockIcon + ' me-1"></i> Stock: ' + stockText + '</span>');
            }
            
            // Update the summary display
            if (filters.length > 0) {
                $('#filter-summary-text').html(filters.join(' '));
                $('#filter-summary').show();
            } else {
                $('#filter-summary').hide();
            }
        };
        
        // Apply filters
        window.applyFilters = function() {

                var table = $('#product-table');
                if (!table) {
                console.error('DataTable not initialized yet');
                return;
            }

            table.on('preXhr.dt', function(e,settings,data){
                data.category_id = $('#filter-category').val() || '';
                data.stock_filter = $('#filter-stock').val() || '';
                data.outlet_id = $('#filter-outlet').val() || '';
            });
            
          
          console.log("Sdf");
          
            
            // Reload table with new URL
            table.DataTable().ajax.reload();
            
            // Update filter summary
            updateFilterSummary();
        };
        
        window.initializeToggles = function() {};
        
        // Initialize delete buttons
        window.initializeDeleteButtons = function() {
            $('.delete-product').off('click').on('click', function(e) {
                e.stopPropagation();
                var id = $(this).data('id');
                var name = $(this).data('name');
                
                Notiflix.Confirm.show(
                    'Delete Product',
                    `Are you sure you want to delete "${name}"? This action cannot be undone.`,
                    'Yes, Delete',
                    'Cancel',
                    function() {
                        Notiflix.Loading.pulse('Deleting product...');
                        var deleteUrl = '{{ route("products.destroy", "__ID__") }}'.replace('__ID__', encodeURIComponent(id));
                        
                        $.ajax({
                            url: deleteUrl,
                            type: 'DELETE',
                            data: { _token: '{{ csrf_token() }}' },
                            success: function(response) {
                                Notiflix.Loading.remove();
                                if (response.status === 'success') {
                                    Notiflix.Notify.success('Product deleted successfully');
                                    window.LaravelDataTables['products-table'].ajax.reload();
                                } else {
                                    Notiflix.Notify.failure(response.message || 'Failed to delete product');
                                }
                            },
                            error: function() {
                                Notiflix.Loading.remove();
                                Notiflix.Notify.failure('Failed to delete product');
                            }
                        });
                    }
                );
            });
        };

         // Initialize bulk actions
        window.initializeBulkActions = function() {
            // Select all checkbox
            $('#select-all').off('change').on('change', function() {
                var shouldCheck = $(this).is(':checked');
                $('.product-checkbox:not(:disabled)').each(function() {
                    var id = String($(this).val());
                    $(this).prop('checked', shouldCheck);
                    if (shouldCheck) {
                        selectedProductIds.add(id);
                    } else {
                        selectedProductIds.delete(id);
                    }
                });
                updateBulkActionButtons();
            });
            
            // Individual checkbox change
            $(document).off('change', '.product-checkbox').on('change', '.product-checkbox', function() {
                var id = String($(this).val());
                if ($(this).is(':checked')) {
                    selectedProductIds.add(id);
                } else {
                    selectedProductIds.delete(id);
                }
                updateBulkActionButtons();
            });

            // Print barcode labels button
            $('#print-barcodes-btn').off('click').on('click', function() {
                var selectedIds = Array.from(selectedProductIds);

                if (selectedIds.length === 0) {
                    Notiflix.Notify.warning('Please select products to print labels');
                    return;
                }

                var printUrl = '{{ route("products.print-barcodes") }}?ids=' + encodeURIComponent(selectedIds.join(','));
                window.open(printUrl, '_blank');
            });

            syncBulkSelections();
        };

        window.syncBulkSelections = function() {
            $('.product-checkbox').each(function() {
                var id = String($(this).val());
                var isDisabled = $(this).is(':disabled');

                if (isDisabled) {
                    selectedProductIds.delete(id);
                    $(this).prop('checked', false);
                    return;
                }

                $(this).prop('checked', selectedProductIds.has(id));
            });
        };
        
        // Update bulk action button visibility
        window.updateBulkActionButtons = function() {
            syncBulkSelections();

            var selectedCount = selectedProductIds.size;
            var enabledOnPage = $('.product-checkbox:not(:disabled)').length;
            var checkedOnPage = $('.product-checkbox:not(:disabled):checked').length;
            $('#select-all').prop('checked', enabledOnPage > 0 && checkedOnPage === enabledOnPage);

            if (selectedCount > 0) {
                $('#print-barcodes-btn').show().html(`<i class="fas fa-barcode"></i> Print Labels (${selectedCount})`);
                $('#selected-products-hint').show().text(`${selectedCount} product(s) selected`);
            } else {
                $('#print-barcodes-btn').hide();
                $('#selected-products-hint').hide().text('');
            }
        };
        
        // Add CSS for filter badges
        $('<style>')
            .prop('type', 'text/css')
            .html(`
                .filter-summary {
                    background: linear-gradient(135deg, #f0fdfa 0%, #f8fafc 100%);
                    padding: 6px 14px;
                    border-radius: 8px;
                    margin-bottom: 16px;
                    border: 1px solid #ccfbf1;
                    border-left: 3px solid #0F766E;
                    box-shadow: 0 1px 4px rgba(15, 118, 110, 0.05);
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    flex-wrap: wrap;
                    gap: 8px;
                }
                .filter-summary-label {
                    font-size: 12px;
                    color: #034141;
                    font-weight: 600;
                    margin-right: 4px;
                }
                .filter-summary .badge {
                    padding: 4px 10px;
                    font-size: 11px;
                    font-weight: 500;
                    border-radius: 12px;
                    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
                    display: inline-flex;
                    align-items: center;
                    gap: 4px;
                }
                .btn-clear-all {
                    background: #ffffff;
                    color: #b91c1c;
                    border: 1px solid #fca5a5;
                    padding: 3px 10px;
                    border-radius: 14px;
                    font-size: 11px;
                    font-weight: 600;
                    cursor: pointer;
                    display: inline-flex;
                    align-items: center;
                    gap: 4px;
                    line-height: 1.4;
                    transition: all 0.15s ease-in-out;
                }
                .btn-clear-all:hover {
                    background: #dc2626;
                    color: #ffffff;
                    border-color: #dc2626;
                    transform: translateY(-1px);
                    box-shadow: 0 2px 6px rgba(220, 38, 38, 0.2);
                }
            `)
            .appendTo('head');
        
        // View product details
        $(document).on('click', '.view-product', function(e) {
            e.stopPropagation();
            var productId = $(this).data('id');
            if (productId) {
                loadProductDetails(productId);
            }
        });      
        
        // Load product details for quick view
        window.loadProductDetails = function(id) {
            if (!id) {
                console.error('No product ID provided');
                return;
            }
            
            Notiflix.Loading.pulse('Loading product details...');
            
            var url = "{{ route('products.show', ':id') }}";
            url = url.replace(':id', id);
            
            $.ajax({
                url: url,
                type: 'GET',
                success: function(response) {
                    Notiflix.Loading.remove();
                    if (response.status === 'success') {
                        $('#product-details-content').html(response.html);
                        $('#productViewModal').modal({
                            backdrop: 'static',
                            keyboard: true
                        }).modal('show');
                    } else {
                        Notiflix.Notify.failure(response.message || 'Failed to load product details');
                    }
                },
                error: function(xhr) {
                    Notiflix.Loading.remove();
                    console.error('AJAX Error:', xhr);
                    
                    if (xhr.status === 404) {
                        Notiflix.Notify.failure('Product not found');
                    } else if (xhr.status === 500) {
                        Notiflix.Notify.failure('Server error. Please try again.');
                    } else {
                        Notiflix.Notify.failure('Failed to load product details. Please try again.');
                    }
                }
            });
        };
        
        // ============ EVENT HANDLERS ============
        
        // Apply filters button
        $('#apply-filters').on('click', function() {
            applyFilters();
        });
        
        // Clear filters button
        $('#clear-filters, #btn-reset-filters').on('click', function() {
            $('#filter-category').val('').trigger('change');
            $('#filter-outlet').val('').trigger('change');
            $('#filter-stock').val('');
            applyFilters();
        });
        
        // Make product row clickable for quick view
        $(document).on('click', '#products-table tbody tr', function(e) {
            // Don't trigger if clicking on buttons, checkboxes, or switches
            if ($(e.target).is('button') || 
                $(e.target).is('input') || 
                $(e.target).closest('button').length || 
                $(e.target).closest('.custom-control').length ||
                $(e.target).closest('.btn-group').length) {
                return;
            }
            
            var productId = $(this).find('.view-product').data('id');
            
            if (!productId) {
                productId = $(this).find('[data-id]').first().data('id');
            }
            
            if (productId) {
                console.log('Loading product details for ID:', productId);
                loadProductDetails(productId);
            } else {
                console.warn('No product ID found for row:', this);
            }
        });
        
        // Keyboard shortcuts
        $(document).on('keydown', function(e) {
            // Ctrl + N: Create new product
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                window.location.href = '{{ route("products.create") }}';
            }
            // Ctrl + F: Focus on search
            if (e.ctrlKey && e.key === 'f') {
                e.preventDefault();
                $('.dataTables_filter input').focus();
            }
        });
        
        // Initialize DataTable event handlers after table is loaded
        if (window.LaravelDataTables && window.LaravelDataTables['products-table']) {
            window.LaravelDataTables['products-table'].on('draw.dt', function() {
                initializeToggles();
                if (typeof initializeFeaturedToggles === 'function') {
                    initializeFeaturedToggles();
                }
                initializeDeleteButtons();
                initializeBulkActions();
                updateBulkActionButtons();
                updateFilterSummary();
            });
        }
        
        // Initial setup
        setTimeout(function() {
            initializeToggles();
            if (typeof initializeFeaturedToggles === 'function') {
                initializeFeaturedToggles();
            }
            initializeDeleteButtons();
            initializeBulkActions();
            updateBulkActionButtons();
            updateFilterSummary();
        }, 500);
    });
    </script>
@endsection