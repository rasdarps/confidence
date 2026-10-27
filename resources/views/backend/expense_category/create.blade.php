@extends('layout.main')
@section('title') {{ 'Create Expense Category' }} @endsection

@section('content')
<script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>

<div class="page-content">
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header with-border">
                    <span class="card-title" style="font-size:20px;">Create | Expense Category</span>

                    <a href="{{ route('expense-categories.index') }}" style="float:right">
                        <button type="button" class="btn btn-primary modal_btn">View Expense Categories</button>
                    </a>

                    <hr>

                    <div class="card-body">
                        <form action="{{ route('expense-categories.store') }}" method="POST" id="myForm" enctype="multipart/form-data">
                            @csrf

                            <div class="alert alert-danger alert-dismissible fade show" id="errorAlert" style="display: none;">
                                <button type="button" class="close h-100" data-dismiss="alert" aria-label="Close">
                                    <span><i class="mdi mdi-close"></i></span>
                                </button>
                                <ul id="errors"></ul>
                            </div>

                            <div class="col-md-6 mx-auto">
                                <div class="form-group">
                                    <label for="name"><strong>Category Name <span class="text-danger">*</span></strong></label>
                                    <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}" placeholder="enter category name">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12 text-center">
                                    <button type="submit" class="btn btn-success mt-3">Submit</button>
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
$(document).ready(function () {
    $('#myForm').on('submit', function (e) {
        e.preventDefault();

        if (!$('#name').val().trim()) {
            Notiflix.Notify.failure('Please enter category name');
            return;
        }

        Notiflix.Loading.standard('Creating category, please wait...');

        $.ajax({
            url: $(this).attr('action'),
            type: $(this).attr('method'),
            data: new FormData(this),
            contentType: false,
            processData: false,
            success: function (data) {
                Notiflix.Loading.remove();
                if (data.status === 200) {
                    Notiflix.Notify.success(data.message);
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                } else {
                    Notiflix.Notify.failure(data.message || 'Unable to create category');
                }
            },
            error: function (xhr) {
                Notiflix.Loading.remove();
                $('#errors').empty();

                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    $('#errorAlert').show();
                    $.each(xhr.responseJSON.errors, function (field, messages) {
                        $('#errors').append('<li>' + messages[0] + '</li>');
                        Notiflix.Notify.failure(messages[0]);
                    });
                    $('html, body').animate({ scrollTop: 0 }, 'slow');
                } else {
                    Notiflix.Notify.failure(xhr.responseJSON?.message || 'Something went wrong');
                }
            }
        });
    });
});
</script>
@endsection
