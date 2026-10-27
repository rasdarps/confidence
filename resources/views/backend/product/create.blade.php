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
                        <a href="{{ route('products.index') }}">
                            <button type="button" class="btn btn-primary modal_btn">View Products</button>
                        </a>
                    </div>
                    
                    <div class="form-div card-body">
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
                            
                            <div class="row mt-4">
                                <div class="col-12 text-center">
                                    <button type="submit" class="btn btn-success mt-3">
                                        <i class="fas fa-save"></i> Submit
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
<script>
    $(document).ready(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $("#myForm").on('submit', function(e) {
            e.preventDefault();

            $('.error-text').html('');
            $('.is-invalid').removeClass('is-invalid');

            if (!validateProductForm()) {
                return;
            }

            ajax(
                $(this).attr('action'),
                new FormData(this),
                {
                    element: 'form-div',
                    showLoading: true,
                    showGlobalError: true,
                    scrollToError: true,
                    success: function(response) {
                        if (response.status === 'success' || response.status === 200) {
                            $('#myForm')[0].reset();
                            $('#category_id').val('').trigger('change');
                            $('#unit_id').val('').trigger('change');

                            Notiflix.Notify.success(response.message || 'Product created successfully!');
                        } else {
                            Notiflix.Notify.warning(response.message || 'Failed to create product');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('Product creation error:', error);
                    }
                }
            );
        });

        function validateProductForm() {
            let isValid = true;

            clearAllErrors({ errorSelector: '.error-text', inputErrorClass: 'is-invalid' });

            const productName = $('input[name="name"]').val();
            if (!productName || productName.trim() === '') {
                showFieldError('name', 'Please enter a product name');
                isValid = false;
            }

            const categoryId = $('#category_id').val();
            if (!categoryId) {
                showFieldError('category_id', 'Please select a category');
                isValid = false;
            }

            const unitId = $('#unit_id').val();
            if (!unitId) {
                showFieldError('unit_id', 'Please select a unit');
                isValid = false;
            }

            const lowStockThreshold = $('input[name="stock_level"]').val();
            if (lowStockThreshold && lowStockThreshold < 0) {
                showFieldError('stock_level', 'Low stock threshold cannot be negative');
                isValid = false;
            }

            if (!isValid) {
                Notiflix.Notify.warning('Please correct the errors before submitting');
                scrollToFirstError('is-invalid');
            }

            return isValid;
        }

        function showFieldError(fieldName, errorMessage) {
            const field = $('#' + fieldName + ', [name="' + fieldName + '"]').first();
            field.addClass('is-invalid');

            let errorElement = field.parent().find('.error-text');
            if (errorElement.length === 0) {
                errorElement = $('<small class="error-text text-danger"></small>');
                field.after(errorElement);
            }
            errorElement.html(errorMessage);
        }

        function clearAllErrors(options) {
            const defaults = {
                errorSelector: '.error-text',
                inputErrorClass: 'is-invalid'
            };
            const settings = {...defaults, ...options};

            $(settings.errorSelector).html('');
            $('.' + settings.inputErrorClass).removeClass(settings.inputErrorClass);
        }

        function scrollToFirstError(errorClass) {
            const firstError = $('.' + errorClass).first();
            if (firstError.length) {
                $('html, body').animate({
                    scrollTop: firstError.offset().top - 100
                }, 500);
                firstError.focus();
            }
        }

        $('input, select, textarea').on('change blur', function() {
            $(this).removeClass('is-invalid');
            $(this).parent().find('.error-text').html('');
        });
    });
    </script>
@endsection