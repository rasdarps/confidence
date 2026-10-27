{{-- filepath: c:\xampp\htdocs\rictPOS\resources\views\backend\unit\create.blade.php --}}
@extends('layout.main')
@section('title') {{'Create Unit'}} @endsection

@section('content')

 <script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>

<div class="page-content">
<div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header with-border">
                            <span class="card-title" style="font-size:20px;">Create | Unit </span>

                            <a href="{{route('units.index')}}" style="float:right">
                                <button type="button" class="btn btn-primary modal_btn">
                                    View Units
                                </button>
                            </a>

                              <hr>

                            <div class="card-body">

                                <form action="{{ route('units.store') }}" method="POST" id="myForm" enctype="multipart/form-data">
                                    @csrf

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
                                                <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}" placeholder="enter unit name" onkeypress="return isCharKey(event)">
                                            </div>
                                        </div>
                                    
                                    {{-- row 1 ends --}}

                                    <div class="row">
                                        <div class="col-12 text-center">
                                            <button type="submit" class="btn btn-success mt-3">Submit</button>
                                        </div>
                                    </div>
                                    {{-- row 2 ends --}}
                            
                                </form>
                            
                            </div>
                                
                        </div>
                        

                    <div>
                </div>
                <!-- end col 12-->
            </div>
            <!-- end row -->
        </div>
        <!-- container fluid ends -->
    </div>
    <!-- page content ends -->

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
                    Notiflix.Loading.standard('Creating unit, please wait...');
                } else {
                    console.log('Creating unit...');
                }

                $.ajax({
                    url: $(this).attr('action'),
                    type: $(this).attr('method'),
                    data: new FormData(this),
                    contentType: false,
                    processData: false,
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
                            $('#myForm')[0].reset();
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
                            $('#errors').append('<li>An error occurred. Please try again later.</li>');
                            if (typeof Notiflix !== 'undefined') {
                                Notiflix.Notify.failure('An error occurred. Please try again later.');
                            } else {
                                alert('An error occurred. Please try again later.');
                            }
                        }
                    }
                });
            });

        });
</script>

@endsection
