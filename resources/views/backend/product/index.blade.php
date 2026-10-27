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
                    <div class="card-header with-border d-flex justify-content-between align-items-center">
                        <span class="card-title" style="font-size:20px;">
                            <i class="fas fa-boxes"></i> Products Management
                        </span>
                        <div>
                            <a href="{{route('products.create')}}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Create New Product
                            </a>
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#productImportModal">
                                <i class="fas fa-file-excel"></i> Import Excel
                            </button>
                            <button type="button" class="btn btn-dark" id="print-barcodes-btn" style="display: none;">
                                <i class="fas fa-barcode"></i> Print Labels
                            </button>
                            <small id="selected-products-hint" class="text-muted ml-2" style="display:none;"></small>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <!-- Filter Section -->
                        <div class="filter-section">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Category</label>
                                        <select id="filter-category" class="form-control select2">
                                            <option value="">All</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Stock Status</label>
                                        <select id="filter-stock" class="form-control">
                                            <option value="">All</option>
                                            <option value="in_stock">In Stock</option>
                                            <option value="low_stock">Low Stock</option>
                                            <option value="out_of_stock">Out of Stock</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    
                                        <label>Filter Records</label>
                                        <button type="button" class="btn btn-dark btn-filter btn-block" id="apply-filters">
                                            <i class="fas fa-filter"></i> Filter
                                        </button>
                                    
                                </div>
                            </div>
                        </div>
                        
                        <!-- Filter Summary -->
                        <div id="filter-summary" class="filter-summary">
                            <i class="fas fa-info-circle"></i> Active Filters:
                            <span id="filter-summary-text"></span>
                            <button class="btn btn-sm btn-link" id="clear-filters">Clear All</button>
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
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="productViewModalLabel">
                    <i class="fas fa-box"></i> Product Details
                </h5>
                <a type="button" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </a>
            </div>
            <div class="modal-body" id="product-details-content">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-2">Loading product details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Close
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
            var stock = $('#filter-stock').val();
            
            // Get selected text for display
            var categoryName = $('#filter-category option:selected').text();
            var stockText = $('#filter-stock option:selected').text();
            
            // Build filter badges
            if (categoryId && categoryId !== '') {
                filters.push('<span class="badge badge-primary mr-1 mb-1">Category: ' + categoryName + '</span>');
            }
            if (stock && stock !== '') {
                var stockClass = '';
                if (stock == 'in_stock') stockClass = 'badge-success';
                else if (stock == 'low_stock') stockClass = 'badge-warning';
                else if (stock == 'out_of_stock') stockClass = 'badge-danger';
                filters.push('<span class="badge ' + stockClass + ' mr-1 mb-1">Stock: ' + stockText + '</span>');
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
                    background: #f8f9fa;
                    padding: 10px 15px;
                    border-radius: 4px;
                    margin-bottom: 20px;
                    border-left: 3px solid #007bff;
                    display: flex;
                    align-items: center;
                    flex-wrap: wrap;
                    gap: 8px;
                }
                .filter-summary .badge {
                    padding: 5px 8px;
                    font-size: 12px;
                    margin-right: 5px;
                }
                .filter-summary .btn-link {
                    padding: 0;
                    margin-left: 10px;
                }
                .badge-primary { background-color: #007bff; color: white; }
                .badge-success { background-color: #28a745; color: white; }
                .badge-danger { background-color: #dc3545; color: white; }
                .badge-warning { background-color: #ffc107; color: #212529; }
                .badge-info { background-color: #17a2b8; color: white; }
                .badge-secondary { background-color: #6c757d; color: white; }
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
        $('#clear-filters').on('click', function() {
            $('#filter-category').val('').trigger('change');
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