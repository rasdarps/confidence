@php
use Illuminate\Support\Facades\Crypt;
@endphp

@extends('layout.main')
@section('title') {{'Edit Product Price'}} @endsection

@section('content')

<div class="page-content">
<div class="container-fluid">

    <div class="row">
        <div class="col-12 col-xl-10 mx-auto">
            <div class="card">
                <div class="card-header with-border d-flex align-items-center justify-content-between">
                    <span class="card-title" style="font-size:20px;">Edit | Master Product Price</span>

                    <a href="{{ route('product-prices.index') }}">
                        <button type="button" class="btn btn-primary modal_btn">
                            View Product Prices
                        </button>
                    </a>
                </div>

                <div class="card-body">
                    <div class="alert alert-info d-flex align-items-center mb-3">
                        <i class="fas fa-tags fa-lg mr-2 me-2"></i>
                        <div>
                            <strong>Central Master Pricing:</strong> Updating prices here updates the master catalog enterprise-wide. The selling price applies immediately to all retail branches, POS terminals, and sales transactions.
                        </div>
                    </div>

                    <form action="{{ route('product-prices.update', $encryptedId) }}" method="POST" id="myForm" enctype="multipart/form-data">
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
                            <div class="col-md-12">
                                <div class="form-group mb-3">
                                    <label for="product_name"><strong>Product Name</strong></label>
                                    <input type="text" id="product_name" class="form-control" value="{{ $product->name }}" readonly style="background-color: #f8f9fa;">
                                    <small class="text-muted">Master product registered in central catalog.</small>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="cost_price"><strong>{{ $product->has_sub_unit ? ($product->unit?->name . ' Cost Price') : 'Cost Price' }} <span class="text-danger">*</span></strong></label>
                                    <input type="number" step="0.01" id="cost_price" name="cost_price" class="form-control" value="{{ old('cost_price', $product->cost_price) }}" placeholder="Enter cost price" min="0">
                                    <small class="text-muted">Standard baseline purchasing / landed cost per {{ $product->unit?->name ?? 'unit' }}.</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="selling_price"><strong>{{ $product->has_sub_unit ? ($product->unit?->name . ' Selling Price') : 'Selling Price' }} <span class="text-danger">*</span></strong></label>
                                    <input type="number" step="0.01" id="selling_price" name="selling_price" class="form-control" value="{{ old('selling_price', $product->selling_price) }}" placeholder="Enter selling price" min="0">
                                    <small class="text-muted">Retail selling price per full {{ $product->unit?->name ?? 'pack' }}.</small>
                                </div>
                            </div>
                        </div>

                        @if($product->has_sub_unit && $product->pieces_per_pack > 1)
                        <div class="card border border-info shadow-none my-3" style="background: #f0fdfa;">
                            <div class="card-body py-3">
                                <h6 class="font-weight-bold text-dark mb-2">
                                    <i class="fas fa-boxes text-success mr-1 me-1"></i> Loose Piece Pricing (Breakdown for {{ $product->subUnit?->name ?? 'Piece' }})
                                </h6>
                                <p class="text-muted small mb-3">
                                    1 {{ $product->unit?->name ?? 'Pack' }} contains <strong>{{ $product->pieces_per_pack }} {{ $product->subUnit?->name ?? 'Pieces' }}</strong>. Set the individual loose piece prices below when selling or buying individual units.
                                </p>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-group mb-2">
                                            <label for="sub_unit_cost_price"><strong>Piece Cost Price ({{ $product->subUnit?->name ?? 'Piece' }})</strong></label>
                                            <input type="number" step="0.01" id="sub_unit_cost_price" name="sub_unit_cost_price" class="form-control" value="{{ old('sub_unit_cost_price', $product->sub_unit_cost_price) }}" placeholder="e.g. Cost per individual piece" min="0">
                                            <small class="text-muted">Landed cost per single piece (Leave empty to use {{ $product->unit?->name }} Cost / {{ $product->pieces_per_pack }}).</small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group mb-2">
                                            <label for="sub_unit_selling_price"><strong>Piece Selling Price ({{ $product->subUnit?->name ?? 'Piece' }})</strong></label>
                                            <input type="number" step="0.01" id="sub_unit_selling_price" name="sub_unit_selling_price" class="form-control" value="{{ old('sub_unit_selling_price', $product->sub_unit_selling_price) }}" placeholder="e.g. Retail price per piece" min="0">
                                            <small class="text-muted">Retail price charged to customers for 1 loose piece at POS.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <div class="row g-3 mb-2">
                            <div class="col-12">
                                <div class="p-3 rounded bg-light border d-flex align-items-center justify-content-between">
                                    <div>
                                        <span class="text-muted font-size-13 d-block">Estimated Margin Preview:</span>
                                        <strong id="marginPreviewText" class="text-success font-size-15">Calculating...</strong>
                                    </div>
                                    <span class="badge" id="marginBadge" style="background-color: #0F766E; color:#fff; font-size:13px; padding: 6px 12px;">Active Margin</span>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12 text-center">
                                <button type="submit" class="btn btn-primary mt-3" style="background-color: #0F766E; border-color: #0F766E; padding: 8px 26px;">
                                    <i class="fas fa-save mr-1 me-1"></i> Update Product Price
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

@section('styles')
<style>
    .card {
        box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease-in-out;
        border-radius: 15px;
        overflow: hidden;
        transform: translateY(0);
        background: linear-gradient(145deg, #ffffff, #f0f0f0);
        border: none;
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
    
    .invalid-feedback {
        display: block;
        width: 100%;
        margin-top: 0.25rem;
        font-size: 0.875em;
        color: #dc3545;
    }
    
    .form-control:focus {
        border-color: #0F766E;
        box-shadow: 0 0 0 0.2rem rgba(15, 118, 110, 0.2);
    }
    
    .form-control.is-invalid:focus {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }
</style>
@endsection

@section('scripts')
<script type="text/javascript">
    $(document).ready(function (){

        function updateMarginPreview() {
            var cost = parseFloat($('#cost_price').val()) || 0;
            var selling = parseFloat($('#selling_price').val()) || 0;
            var profit = selling - cost;
            var marginPercent = selling > 0 ? ((profit / selling) * 100).toFixed(1) : 0;

            if (profit < 0) {
                $('#marginPreviewText').html('<span class="text-danger">Loss: ' + profit.toFixed(2) + ' (' + marginPercent + '%)</span>');
                $('#marginBadge').removeClass('bg-success').addClass('bg-danger').text('Negative Margin');
            } else {
                $('#marginPreviewText').html('<span class="text-success">Profit: ' + profit.toFixed(2) + ' (' + marginPercent + '%)</span>');
                $('#marginBadge').removeClass('bg-danger').addClass('bg-success').text('Markup Margin');
            }
        }

        $('#cost_price, #selling_price').on('input change', updateMarginPreview);
        updateMarginPreview();
       
        // Handle form submission
        $('#myForm').on('submit', function(e) {
            e.preventDefault();

            var costVal = $('#cost_price').val().trim();
            var sellingVal = $('#selling_price').val().trim();

            // Client-side validation
            if(!costVal || !sellingVal) {
                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Notify.failure('Please enter cost and selling prices');
                } else {
                    alert('Please enter cost and selling prices');
                }
                return;
            }

            var cost = parseFloat(costVal);
            var selling = parseFloat(sellingVal);

            if (isNaN(cost) || cost < 0) {
                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Notify.failure('Cost price must be a valid non-negative number');
                } else {
                    alert('Cost price must be a valid non-negative number');
                }
                return;
            }

            if (isNaN(selling) || selling < 0) {
                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Notify.failure('Selling price must be a valid non-negative number');
                } else {
                    alert('Selling price must be a valid non-negative number');
                }
                return;
            }

            if (selling < cost) {
                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Notify.failure('Selling price must be greater than or equal to cost price');
                } else {
                    alert('Selling price must be greater than or equal to cost price');
                }
                return;
            }

            // Show loading
            if (typeof Notiflix !== 'undefined') {
                Notiflix.Loading.standard('Updating product price, please wait...');
            } else {
                console.log('Updating product price...');
            }

            var formData = new FormData(this);
            formData.set('_method', 'PATCH');

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                headers: {
                    'X-HTTP-Method-Override': 'PATCH'
                },
                success: function(data) {
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
                        var errMsg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : (xhr.responseText || 'An error occurred. Please try again later.');
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
    });
</script>
@endsection
