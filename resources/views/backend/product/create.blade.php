@extends('layout.main')
@section('title') {{'Create Product'}} @endsection

@push('styles')
<style>
    .card {
        box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease-in-out;
        border-radius: 15px;
        overflow: hidden;
    }

    .card:hover {
        box-shadow: 0 8px 25px 0 rgba(0, 0, 0, 0.3);
        transform: translateY(-5px);
        border-radius: 20px;
        background: linear-gradient(145deg, #f8f9fa, #e9ecef);
    }

    .card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
        transition: left 0.5s;
        z-index: 1;
    }

    .card:hover::before {
        left: 100%;
    }

    .card-body {
        position: relative;
        z-index: 2;
    }

    .is-invalid {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }

    .form-control:focus,
    .select2-container--default .select2-selection--single:focus,
    .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: #0F766E;
        box-shadow: 0 0 0 0.2rem rgba(15, 118, 110, 0.2);
    }
</style>

@endpush

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 col-xl-10 mx-auto">
                <div class="card">
                    <div class="card-header with-border d-flex align-items-center justify-content-between">
                        <span class="card-title" style="font-size:20px;">Create | Product</span>
                        <a href="{{ route('products.index') }}" class="btn btn-modern-header">
                            <i class="fas fa-list me-1 mr-1"></i> View Products
                        </a>
                    </div>
                    
                    <div class="form-div card-body">
                        <div class="alert alert-info d-flex align-items-center mb-3">
                            <i class="fas fa-boxes fa-lg mr-2 me-2"></i>
                            <div>
                                <strong>Central Master Catalog:</strong> Products created here are registered globally across the enterprise. Initial stock is received through Central Purchases and distributed to retail outlets via Stock Transfers.
                            </div>
                        </div>

                        <form action="{{ route('products.store') }}" method="POST" id="myForm" enctype="multipart/form-data">
                            @csrf
                            <div class="alert alert-danger alert-dismissible fade show" id="errorAlert" style="display: none;">
                                <button type="button" class="close h-100" data-dismiss="alert" aria-label="Close">
                                    <span><i class="mdi mdi-close"></i></span>
                                </button>
                                <ul id="errors"></ul>
                            </div>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name"><strong>Product Name <span class="text-danger">*</span></strong></label>
                                        <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}" placeholder="Enter product name">
                                        <span class="text-danger error-text" data-field="name"></span>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="category_id"><strong>Category <span class="text-danger">*</span></strong></label>
                                        <select name="category_id" id="category_id" class="form-control select2" aria-label="Select category">
                                            <option value="">Select Category</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted d-block mt-1">
                                            Category not listed?
                                            <a href="{{ route('categories.create') }}" class="text-muted text-decoration-underline">Create category</a>
                                        </small>
                                        <span class="text-danger error-text" data-field="category_id"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="unit_id"><strong>Unit <span class="text-danger">*</span></strong></label>
                                        <select name="unit_id" id="unit_id" class="form-control select2" aria-label="Select unit">
                                            <option value="">Select Unit</option>
                                            @foreach($units as $unit)
                                                <option value="{{ $unit->id }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>{{ $unit->name }}</option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted d-block mt-1">
                                            Unit not listed?
                                            <a href="{{ route('units.create') }}" class="text-muted text-decoration-underline">Create unit</a>
                                        </small>
                                        <span class="text-danger error-text" data-field="unit_id"></span>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="stock_level"><strong>Low Stock Threshold</strong></label>
                                        <input type="number" id="stock_level" name="stock_level" class="form-control" value="{{ old('stock_level', 1) }}" min="0" placeholder="Enter low stock threshold">
                                        <small class="text-muted">Alert for restock when stock reaches this level. Default is 1</small>
                                        <span class="text-danger error-text" data-field="stock_level"></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-4 pt-3 border-top">
                                <div class="col-12 text-center">
                                    <a href="{{ route('products.index') }}" class="btn btn-modern-cancel mr-2 me-2">
                                        <i class="fas fa-times me-1 mr-1"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-modern-primary">
                                        <i class="fas fa-check-circle me-1 mr-1"></i> Create Product
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script type="text/javascript">
    $(document).ready(function() {
        if ($.fn.select2) {
            $('.select2').select2({
                width: '100%'
            });
        }

        // Handle form submission
        $('#myForm').on('submit', function(e) {
            e.preventDefault();

            // Client-side validations with Notiflix failure pop-ups for all inputs
            var name = $('#name').val() ? $('#name').val().trim() : '';
            if (!name) {
                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Notify.failure('Please enter a product name');
                } else {
                    alert('Please enter a product name');
                }
                $('#name').addClass('is-invalid').focus();
                return;
            }

            var categoryId = $('#category_id').val();
            if (!categoryId) {
                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Notify.failure('Please select a category');
                } else {
                    alert('Please select a category');
                }
                if ($('#category_id').data('select2')) {
                    $('#category_id').select2('open');
                } else {
                    $('#category_id').focus();
                }
                return;
            }

            var unitId = $('#unit_id').val();
            if (!unitId) {
                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Notify.failure('Please select a unit');
                } else {
                    alert('Please select a unit');
                }
                if ($('#unit_id').data('select2')) {
                    $('#unit_id').select2('open');
                } else {
                    $('#unit_id').focus();
                }
                return;
            }

            var stockLevel = $('#stock_level').val();
            if (stockLevel === '' || isNaN(stockLevel) || parseFloat(stockLevel) < 0) {
                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Notify.failure('Please enter a valid low stock threshold (0 or greater)');
                } else {
                    alert('Please enter a valid low stock threshold (0 or greater)');
                }
                $('#stock_level').addClass('is-invalid').focus();
                return;
            }

            // Show loading
            if (typeof Notiflix !== 'undefined') {
                Notiflix.Loading.standard('Creating product, please wait...');
            }

            var $btn = $(this).find('button[type="submit"]');
            $btn.prop('disabled', true);

            $.ajax({
                url: $(this).attr('action'),
                type: $(this).attr('method'),
                data: new FormData(this),
                contentType: false,
                processData: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (typeof Notiflix !== 'undefined') {
                        Notiflix.Loading.remove();
                    }
                    $btn.prop('disabled', false);

                    if (response.status === 'success' || response.status === 200) {
                        if (typeof Notiflix !== 'undefined') {
                            Notiflix.Notify.success(response.message || 'Product created successfully!');
                        } else {
                            alert(response.message || 'Product created successfully!');
                        }

                        $('#myForm')[0].reset();
                        $('#category_id').val('').trigger('change');
                        $('#unit_id').val('').trigger('change');
                        $('#stock_level').val(1);
                        $('.is-invalid').removeClass('is-invalid');
                        $('#errorAlert').hide();
                        $('#errors').empty();

                        if (response.redirect) {
                            setTimeout(function() {
                                window.location.href = response.redirect;
                            }, 1000);
                        }
                    } else {
                        var warnMsg = response.message || 'Failed to create product';
                        if (typeof Notiflix !== 'undefined') {
                            Notiflix.Notify.failure(warnMsg);
                        } else {
                            alert('Error: ' + warnMsg);
                        }
                    }
                },
                error: function(xhr, status, error) {
                    if (typeof Notiflix !== 'undefined') {
                        Notiflix.Loading.remove();
                    }
                    $btn.prop('disabled', false);
                    $('#errors').empty();

                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        $('#errorAlert').show();
                        $.each(xhr.responseJSON.errors, function(field, messages) {
                            $('#errors').append('<li>' + messages[0] + '</li>');
                            if (typeof Notiflix !== 'undefined') {
                                Notiflix.Notify.failure(messages[0]);
                            } else {
                                alert('Validation Error: ' + messages[0]);
                            }
                            $('#' + field).addClass('is-invalid');
                        });
                        $('html, body').animate({
                            scrollTop: $('#errorAlert').offset().top - 100
                        }, 500);
                    } else {
                        var errMsg = (xhr.responseJSON && xhr.responseJSON.message)
                            ? xhr.responseJSON.message
                            : 'An error occurred. Please try again later.';
                        $('#errorAlert').show();
                        $('#errors').append('<li>' + errMsg + '</li>');
                        if (typeof Notiflix !== 'undefined') {
                            Notiflix.Notify.failure(errMsg);
                        } else {
                            alert(errMsg);
                        }
                    }
                }
            });
        });

        // Real-time error clearance on input
        $('input, select').on('input change', function() {
            $(this).removeClass('is-invalid');
        });
    });
</script>
@endsection