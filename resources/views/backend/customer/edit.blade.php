@php
use Illuminate\Support\Facades\Crypt;
$isCash = (int) $customer->id === 1 || strtolower((string) $customer->name) === 'cash_sales';
@endphp

@extends('layout.main')
@section('title') {{'Edit Customer'}} @endsection

@section('content')

 <script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>

<div class="page-content">
<div class="container-fluid">

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header with-border">
                    <span class="card-title" style="font-size:20px;">Edit | Customer</span>

                    <a href="{{ route('customers.index') }}" style="float:right">
                        <button type="button" class="btn btn-primary modal_btn">
                            View Customers
                        </button>
                    </a>

                    <hr>

                    <div class="card-body">
                        <div class="alert alert-info py-2 mb-3" role="alert">
                            <i class="fas fa-users me-1" aria-hidden="true"></i>
                            <strong>Unified Directory:</strong> Updating this customer profile affects customer identification and outstanding balances across all branch POS terminals.
                        </div>

                        @if($isCash)
                            <div class="alert alert-warning py-2 mb-3" role="alert">
                                <i class="fas fa-exclamation-triangle me-1" aria-hidden="true"></i>
                                <strong>System Account:</strong> This is the default walk-in customer account. The account name is protected to preserve POS checkout operations.
                            </div>
                        @endif

                        <form action="{{ route('customers.update', Crypt::encrypt($customer->id)) }}" method="POST" id="myForm" enctype="multipart/form-data" novalidate>
                            @csrf
                            @method('PATCH')

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
                                        <label for="name"><strong>Customer Name <span class="text-danger">*</span></strong></label>
                                        <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $customer->name) }}" placeholder="enter customer name" {{ $isCash ? 'readonly' : '' }}>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="mobile_no"><strong>Mobile Number <span class="text-danger">*</span></strong></label>
                                        <input type="text" id="mobile_no" name="mobile_no" class="form-control" value="{{ old('mobile_no', $customer->mobile_no) }}" placeholder="enter mobile number" minlength="10" maxlength="15">
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-2">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email"><strong>Email Address</strong></label>
                                        <input type="email" id="email" name="email" class="form-control" value="{{ old('email', $customer->email) }}" placeholder="enter email address">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="address"><strong>Address</strong></label>
                                        <input type="text" id="address" name="address" class="form-control" value="{{ old('address', $customer->address) }}" placeholder="enter address">
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-12 text-center">
                                    <button type="submit" class="btn btn-success mt-2">Update Customer</button>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

@endsection

@section('scripts')
<script type="text/javascript">
    $(document).ready(function (){
        $('#myForm').on('submit', function(e) {
            e.preventDefault();

            if(!$('#name').val().trim()) {
                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Notify.failure('Please enter a customer name');
                } else {
                    alert('Please enter a customer name');
                }
                return;
            }

            var mobile = $('#mobile_no').val().trim();
            if(!mobile) {
                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Notify.failure('Please enter a mobile number');
                } else {
                    alert('Please enter a mobile number');
                }
                return;
            }

            var email = $('#email').val().trim();
            if(email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Notify.failure('Please enter a valid email address');
                } else {
                    alert('Please enter a valid email address');
                }
                return;
            }

            var formData = new FormData(this);
            var $submitBtn = $(this).find('button[type="submit"]');
            $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    $submitBtn.prop('disabled', false).html('Update Customer');
                    if(response.status === 200) {
                        if (typeof Notiflix !== 'undefined') {
                            Notiflix.Notify.success(response.message);
                        }
                        window.location.href = response.redirect || "{{ route('customers.index') }}";
                    } else {
                        if (typeof Notiflix !== 'undefined') {
                            Notiflix.Notify.failure(response.message || 'Update failed');
                        } else {
                            alert(response.message || 'Update failed');
                        }
                    }
                },
                error: function(xhr) {
                    $submitBtn.prop('disabled', false).html('Update Customer');
                    var errorsHtml = '';
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        $.each(xhr.responseJSON.errors, function(key, value) {
                            errorsHtml += '<li>' + value[0] + '</li>';
                        });
                        $('#errors').html(errorsHtml);
                        $('#errorAlert').show();
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        if (typeof Notiflix !== 'undefined') {
                            Notiflix.Notify.failure(xhr.responseJSON.message);
                        } else {
                            alert(xhr.responseJSON.message);
                        }
                    } else {
                        if (typeof Notiflix !== 'undefined') {
                            Notiflix.Notify.failure('An unexpected error occurred. Please try again.');
                        } else {
                            alert('An unexpected error occurred. Please try again.');
                        }
                    }
                }
            });
        });
    });
</script>
@endsection
