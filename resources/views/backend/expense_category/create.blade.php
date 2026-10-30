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

                    <a href="{{ route('expense-categories.index') }}" class="btn btn-modern-header" style="float:right">
                        <i class="fas fa-list me-1 mr-1"></i> View Expense Categories
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
                                <div class="form-group mb-3">
                                    <label for="name"><strong>Category Name <span class="text-danger">*</span></strong></label>
                                    <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}" placeholder="enter category name">
                                </div>
                                <div class="form-group mb-3">
                                    <label for="accounting_account_id"><strong>GL Expense Account (Chart of Accounts)</strong></label>
                                    <select name="accounting_account_id" id="accounting_account_id" class="form-control select2">
                                        <option value="">Default (5200 Operating Expenses)</option>
                                        @foreach($accounts as $acc)
                                            <option value="{{ $acc->id }}" {{ old('accounting_account_id') == $acc->id ? 'selected' : '' }}>
                                                [{{ $acc->code }}] {{ $acc->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted">Expense lines using this category will automatically debit this account in the General Ledger and Income Statement.</small>
                                </div>
                            </div>

                            <div class="row mt-4 pt-3 border-top">
                                <div class="col-12 text-center">
                                    <a href="{{ route('expense-categories.index') }}" class="btn btn-modern-cancel mr-2 me-2">
                                        <i class="fas fa-times me-1 mr-1"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-modern-primary">
                                        <i class="fas fa-check-circle me-1 mr-1"></i> Create Expense Category
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
