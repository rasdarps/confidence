{{-- filepath: c:\xampp\htdocs\rictPOS\resources\views\backend\unit\edit.blade.php --}}
@php
use Illuminate\Support\Facades\Crypt;
@endphp

@extends('layout.main')
@section('title') {{'Edit Unit'}} @endsection

@section('content')

 <script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>

<div class="page-content">
<div class="container-fluid">

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header with-border">
                    <span class="card-title" style="font-size:20px;">Edit | Unit </span>

                    <a href="{{route('units.index')}}" style="float:right">
                        <button type="button" class="btn btn-primary modal_btn">
                            View Units
                        </button>
                    </a>

                    <hr>

                    <div class="card-body">

                        <form action="{{ route('units.update', Crypt::encrypt($unit->id)) }}" method="POST" id="myForm" enctype="multipart/form-data">
                            @csrf
                            @method('PATCH')  {{-- Using PATCH method --}}

                            {{-- backend error alerts --}}
                            <div class="alert alert-danger alert-dismissible fade show" id="errorAlert" style="display: none;">
                                <button type="button" class="close h-100" data-dismiss="alert" aria-label="Close">
                                    <span><i class="mdi mdi-close"></i></span>
                                </button>
                                <ul id="errors"></ul>
                            </div>

                            <div class="col-md-6 mx-auto">
                                <div class="form-group">
                                    <label for="name"><strong>Unit Name <span class="text-danger">*</span></strong></label>
                                    <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $unit->name) }}" placeholder="enter unit name" onkeypress="return isCharKey(event)">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12 text-center">
                                    <button type="submit" class="btn btn-success mt-3">Update Unit</button>
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
                    Notiflix.Notify.failure('Please enter a unit name');
                } else {
                    alert('Please enter a unit name');
                }
                return;
            }

            // Show loading
            if (typeof Notiflix !== 'undefined') {
                Notiflix.Loading.standard('Updating unit, please wait...');
            } else {
                console.log('Updating unit...');
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
