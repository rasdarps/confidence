{{-- @php
use Illuminate\Support\Facades\Crypt;
@endphp --}}

@extends('layout.main')
@section('title') {{'Edit Supplier'}} @endsection

@section('content')

 <script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>

<div class="page-content">
<div class="container-fluid">

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header with-border">
                    <span class="card-title" style="font-size:20px;">Edit | Supplier </span>

                    <a href="{{route('suppliers.index')}}" style="float:right">
                        <button type="button" class="btn btn-primary modal_btn">
                            View Suppliers
                        </button>
                    </a>

                    <hr>

                    <div class="card-body">

                        <form action="{{ route('suppliers.update', Crypt::encrypt($supplier->id)) }}" method="POST" id="myForm" enctype="multipart/form-data">
                            @csrf
                            @method('PATCH')  {{-- Using PATCH method --}}

                            {{-- backend error alerts --}}
                            <div class="alert alert-danger alert-dismissible fade show" id="errorAlert" style="display: none;">
                                <button type="button" class="close h-100" data-dismiss="alert" aria-label="Close">
                                    <span><i class="mdi mdi-close"></i></span>
                                </button>
                                <ul id="errors"></ul>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name"><strong>Supplier Name <span class="text-danger">*</span></strong></label>
                                        <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $supplier->name) }}" placeholder="enter supplier name" onkeypress="return isCharKey(event)">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="mobile_no"><strong>Mobile Number <span class="text-danger">*</span></strong></label>
                                        <input type="text" id="mobile_no" name="mobile_no" class="form-control" value="{{ old('mobile_no', $supplier->mobile_no) }}" placeholder="enter mobile number" maxlength="15">
                                    </div>
                                </div>
                            </div>
                            {{-- row 1 ends --}}

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email"><strong>Email Address</strong></label>
                                        <input type="email" id="email" name="email" class="form-control" value="{{ old('email', $supplier->email) }}" placeholder="enter email address">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="address"><strong>Address</strong></label>
                                        <input type="text" id="address" name="address" class="form-control" value="{{ old('address', $supplier->address) }}" placeholder="enter address">
                                    </div>
                                </div>
                            </div>
                            {{-- row 2 ends --}}

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="city"><strong>City</strong></label>
                                        <input type="text" id="city" name="city" class="form-control" value="{{ old('city', $supplier->city) }}" placeholder="enter city" onkeypress="return isCharKey(event)">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="type"><strong>Supplier Type</strong></label>
                                        <select name="type" id="type" class="form-control select2" aria-label="Select supplier type">
                                            <option disabled value="">Select Supplier Type</option>
                                            <option value="Distributor" {{ (old('type', $supplier->type) == 'Distributor') ? 'selected' : '' }}>Distributor</option>
                                            <option value="Wholesaler" {{ (old('type', $supplier->type) == 'Wholesaler') ? 'selected' : '' }}>Wholesaler</option>
                                            <option value="Retailer" {{ (old('type', $supplier->type) == 'Retailer') ? 'selected' : '' }}>Retailer</option>
                                            <option value="Manufacturer" {{ (old('type', $supplier->type) == 'Manufacturer') ? 'selected' : '' }}>Manufacturer</option>
                                            <option value="Other" {{ (old('type', $supplier->type) == 'Other') ? 'selected' : '' }}>Other</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            {{-- row 3 ends --}}

                            <div class="row">
                                <div class="col-12 text-center">
                                    <button type="submit" class="btn btn-success mt-3">Update Supplier</button>
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
    
    .form-control:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
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
                    Notiflix.Notify.failure('Please enter a supplier name');
                } else {
                    alert('Please enter a supplier name');
                }
                return;
            }

            if(!$('#mobile_no').val().trim()) {
                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Notify.failure('Please enter a mobile number');
                } else {
                    alert('Please enter a mobile number');
                }
                return;
            }

            // Email validation (if provided)
            if($('#email').val().trim() && !isValidEmail($('#email').val())) {
                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Notify.failure('Please enter a valid email address');
                } else {
                    alert('Please enter a valid email address');
                }
                return;
            }

            // Show loading
            if (typeof Notiflix !== 'undefined') {
                Notiflix.Loading.standard('Updating supplier, please wait...');
            } else {
                console.log('Updating supplier...');
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

        // Email validation helper function
        function isValidEmail(email) {
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }
    });
</script>
@endsection
