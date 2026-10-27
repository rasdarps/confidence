@php
use Illuminate\Support\Facades\Crypt;
@endphp

@extends('layout.main')
@section('title') {{ 'Edit Expense Category' }} @endsection

@section('content')
<script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>

<div class="page-content">
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header with-border">
                    <span class="card-title" style="font-size:20px;">Edit | Expense Category</span>

                    <a href="{{ route('expense-categories.index') }}" style="float:right">
                        <button type="button" class="btn btn-primary modal_btn">View Expense Categories</button>
                    </a>

                    <hr>

                    <div class="card-body">
                        <form action="{{ route('expense-categories.update', Crypt::encrypt($expenseCategory->id)) }}" method="POST" id="myForm" enctype="multipart/form-data">
                            @csrf
                            @method('PATCH')

                            <div class="alert alert-danger alert-dismissible fade show" id="errorAlert" style="display: none;">
                                <button type="button" class="close h-100" data-dismiss="alert" aria-label="Close">
                                    <span><i class="mdi mdi-close"></i></span>
                                </button>
                                <ul id="errors"></ul>
                            </div>

                            <div class="col-md-6 mx-auto">
                                <div class="form-group">
                                    <label for="name"><strong>Category Name <span class="text-danger">*</span></strong></label>
                                    <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $expenseCategory->name) }}" placeholder="enter category name">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12 text-center">
                                    <button type="submit" class="btn btn-success mt-3">Update Category</button>
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

        Notiflix.Loading.standard('Updating category, please wait...');

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
            success: function (data) {
                Notiflix.Loading.remove();
                if (data.status === 200) {
                    Notiflix.Notify.success(data.message);
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                } else {
                    Notiflix.Notify.failure(data.message || 'Unable to update category');
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
