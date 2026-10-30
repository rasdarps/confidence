{{-- filepath: c:\xampp\htdocs\rictPOS\resources\views\backend\product\edit.blade.php --}}
@php
use Illuminate\Support\Facades\Crypt;
@endphp

@extends('layout.main')
@section('title') {{'Edit Product'}} @endsection

@section('content')

<div class="page-content">
<div class="container-fluid">

    <div class="row">
        <div class="col-12 col-xl-10 mx-auto">
            <div class="card">
                <div class="card-header with-border d-flex align-items-center justify-content-between">
                    <span class="card-title" style="font-size:20px;">Edit | Product</span>

                    <a href="{{ route('products.index') }}">
                        <button type="button" class="btn btn-primary modal_btn">
                            View Products
                        </button>
                    </a>
                </div>

                <div class="card-body">
                    <div class="alert alert-info d-flex align-items-center mb-3">
                        <i class="fas fa-boxes fa-lg mr-2 me-2"></i>
                        <div>
                            <strong>Central Master Catalog:</strong> Editing this product updates the master definition enterprise-wide. Changes to Name, Category, Unit, or Barcode immediately propagate to all branch stations and POS registers.
                        </div>
                    </div>

                    <form action="{{ route('products.update', Crypt::encrypt($product->id)) }}" method="POST" id="myForm" enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')

                        {{-- backend error alerts --}}
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
                                    <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $product->name) }}" placeholder="Enter product name">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="unit_id"><strong>Unit Name <span class="text-danger">*</span></strong></label>
                                    <select name="unit_id" id="unit_id" class="form-control select2" aria-label="Select unit">
                                        <option disabled value="">Select Unit</option>
                                        @foreach($units as $unit)
                                        <option value="{{ $unit->id }}" {{ (old('unit_id', $product->unit_id) == $unit->id) ? 'selected' : '' }}>{{ $unit->name }}</option>
                                       @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        {{-- row 1 ends --}}

                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="category_id"><strong>Category Name <span class="text-danger">*</span></strong></label>
                                    <select name="category_id" id="category_id" class="form-control select2" aria-label="Select category">
                                        <option disabled value="">Select Category</option>
                                        @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ (old('category_id', $product->category_id) == $category->id) ? 'selected' : '' }}>{{ $category->name }}</option>
                                       @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="barcode"><strong>Barcode</strong></label>
                                    <input type="text" id="barcode" name="barcode" class="form-control" value="{{ old('barcode', $product->barcode) }}" placeholder="Enter barcode (EAN-13 preferred)">
                                    <small class="text-muted">Master barcode used across all POS scanners and warehouse stations.</small>
                                </div>
                            </div>
                        </div>
                        {{-- row 2 ends --}}

                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="stock_level"><strong>Low Stock Threshold (Central Warehouse) <span class="text-danger">*</span></strong></label>
                                    <input type="number" id="stock_level" name="stock_level" class="form-control" value="{{ old('stock_level', $centralStockLevel ?? 1) }}" placeholder="Enter low stock threshold" min="0">
                                    <small class="text-muted">Restock alert threshold for Central Warehouse. Branch stations manage local thresholds independently.</small>
                                </div>
                            </div>
                        </div>
                        {{-- row 3 ends --}}

                        <div class="row mt-4">
                            <div class="col-12 text-center">
                                <button type="submit" class="btn btn-primary mt-3" style="background-color: #0F766E; border-color: #0F766E; padding: 8px 26px;">
                                    <i class="fas fa-save mr-1 me-1"></i> Update Product
                                </button>
                            </div>
                        </div>
                        {{-- row 4 ends --}}

                    </form>

                </div>
            </div>
        </div>
    </div>
</div>
</div>

@endsection 

@section('styles')
<style>
    .card {
        box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease-in-out;
        border-radius: 15px;
        overflow: hidden;
        transform: translateY(0);
    }

    .card:hover {
        box-shadow: 0 8px 25px 0 rgba(0, 0, 0, 0.3);
        transform: translateY(-5px);
        border-radius: 20px;
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

    .card {
        background: linear-gradient(145deg, #ffffff, #f0f0f0);
        border: none;
    }

    .card:hover {
        background: linear-gradient(145deg, #f8f9fa, #e9ecef);
    }

    .is-invalid {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }
    
    .invalid-feedback {
        display: block;
        width: 100%;
        margin-top: 0.25rem;
        font-size: 0.875em;
        color: #dc3545;
    }
    
    .form-control:focus,
    .select2-container--default .select2-selection--single:focus,
    .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: #0F766E;
        box-shadow: 0 0 0 0.2rem rgba(15, 118, 110, 0.2);
    }
    
    .form-control.is-invalid:focus {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }

    .select2 {
        width: 100% !important;
    }
</style>
@endsection

@section('scripts')
<script type="text/javascript">
    $(document).ready(function (){
       
        // Handle form submission
        $('#myForm').on('submit', function(e) {
            e.preventDefault();

            // Client-side validation
            if(!$('#name').val().trim()) {
                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Notify.failure('Please enter a product name');
                } else {
                    alert('Please enter a product name');
                }
                return;
            }

            if(!$('#unit_id').val()) {
                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Notify.failure('Please select a unit');
                } else {
                    alert('Please select a unit');
                }
                return;
            }

            if(!$('#category_id').val()) {
                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Notify.failure('Please select a category');
                } else {
                    alert('Please select a category');
                }
                return;
            }

            if(!$('#stock_level').val() || $('#stock_level').val() < 0) {
                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Notify.failure('Please enter a valid stock level');
                } else {
                    alert('Please enter a valid stock level');
                }
                return;
            }

            // Show loading
            if (typeof Notiflix !== 'undefined') {
                Notiflix.Loading.standard('Updating product, please wait...');
            } else {
                console.log('Updating product...');
            }

            // Create FormData and manually add _method for PATCH
            var formData = new FormData(this);
            
            // Ensure _method is set to PATCH
            formData.set('_method', 'PATCH');

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',  // Always POST for Laravel
                data: formData,
                contentType: false,
                processData: false,
                headers: {
                    'X-HTTP-Method-Override': 'PATCH'  // Additional header for PATCH
                },
                success: function(data) {
                    console.log('Success response:', data);
                    
                    if (typeof Notiflix !== 'undefined') {
                        Notiflix.Loading.remove();
                    }
                    
                    if (data.status === 200) {
                        if (typeof Notiflix !== 'undefined') {
                            Notiflix.Notify.success(data.message);
                        } else {
                            alert(data.message);
                        }
                        
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        }
                    } else {
                        if (typeof Notiflix !== 'undefined') {
                            Notiflix.Notify.failure(data.message);
                        } else {
                            alert('Error: ' + data.message);
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Error response:', xhr.responseText);
                    console.log('Status:', status);
                    console.log('Error:', error);
                    
                    if (typeof Notiflix !== 'undefined') {
                        Notiflix.Loading.remove();
                    }
                    
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
                        });
                        $('html, body').animate({
                            scrollTop: $('#errorAlert').offset().top - 100
                        }, 500);
                    } else {
                        $('#errorAlert').show();
                        $('#errors').append('<li>Server Error: ' + (xhr.responseText || 'An error occurred. Please try again later.') + '</li>');
                        
                        if (typeof Notiflix !== 'undefined') {
                            Notiflix.Notify.failure('An error occurred. Please try again later.');
                        } else {
                            alert('An error occurred: ' + xhr.responseText);
                        }
                    }
                }
            });
        });
    });
</script>
@endsection
