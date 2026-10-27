@extends('layout.main')
@section('title') {{'Edit Outlet'}} @endsection

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <span class="card-title" style="font-size:20px;">Edit Outlet / Branch: {{ $outlet->name }}</span>
                        <a href="{{ route('outlets.index') }}" style="float:right">
                            <button type="button" class="btn btn-primary">View Outlets</button>
                        </a>
                        <hr>

                        <div class="card-body">
                            <form action="{{ route('outlets.update', Crypt::encrypt($outlet->id)) }}" method="POST" id="myForm">
                                @csrf
                                @method('PATCH')

                                <div class="alert alert-danger alert-dismissible fade show" id="errorAlert" style="display: none;">
                                    <button type="button" class="close h-100" data-dismiss="alert" aria-label="Close">
                                        <span><i class="mdi mdi-close"></i></span>
                                    </button>
                                    <ul id="errors"></ul>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="name"><strong>Outlet Name <span class="text-danger">*</span></strong></label>
                                            <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $outlet->name) }}" required>
                                            <small class="text-muted">Display name of the outlet</small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="code"><strong>Outlet Code <span class="text-danger">*</span></strong></label>
                                            <input type="text" id="code" name="code" class="form-control" value="{{ old('code', $outlet->code) }}" required>
                                            <small class="text-muted">Unique short identifier code</small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="phone"><strong>Phone Number</strong></label>
                                            <input type="text" id="phone" name="phone" class="form-control" value="{{ old('phone', $outlet->phone) }}">
                                            <small class="text-muted">Printed on branch receipts</small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="email"><strong>Email</strong></label>
                                            <input type="email" id="email" name="email" class="form-control" value="{{ old('email', $outlet->email) }}">
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group mb-3">
                                            <label for="address"><strong>Address / Location</strong></label>
                                            <textarea id="address" name="address" class="form-control" rows="2">{{ old('address', $outlet->address) }}</textarea>
                                            <small class="text-muted">Printed on branch receipts</small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="is_central" name="is_central" value="1" {{ $outlet->is_central ? 'checked' : '' }}>
                                            <label class="form-check-label" for="is_central"><strong>Central Warehouse (HQ)</strong></label>
                                            <small class="d-block text-muted">Check only if this outlet acts as the Central Warehouse</small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ $outlet->is_active ? 'checked' : '' }}>
                                            <label class="form-check-label" for="is_active"><strong>Active</strong></label>
                                            <small class="d-block text-muted">Inactive outlets cannot execute POS sales or receive transfers</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12 text-center">
                                        <button type="submit" class="btn btn-success mt-3 px-4">Update Outlet</button>
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
                if (typeof Notiflix !== 'undefined') Notiflix.Notify.failure('Please enter outlet name');
                return;
            }
            if (!$('#code').val().trim()) {
                if (typeof Notiflix !== 'undefined') Notiflix.Notify.failure('Please enter outlet code');
                return;
            }

            if (typeof Notiflix !== 'undefined') {
                Notiflix.Loading.standard('Updating outlet, please wait...');
            }

            $.ajax({
                url: $(this).attr('action'),
                type: $(this).attr('method'),
                data: $(this).serialize(),
                success: function (data) {
                    if (typeof Notiflix !== 'undefined') Notiflix.Loading.remove();
                    if (data.status === 200) {
                        if (typeof Notiflix !== 'undefined') Notiflix.Notify.success(data.message);
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        }
                    } else {
                        if (typeof Notiflix !== 'undefined') Notiflix.Notify.failure(data.message);
                    }
                },
                error: function (xhr) {
                    if (typeof Notiflix !== 'undefined') Notiflix.Loading.remove();
                    $('#errors').empty();
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        $('#errorAlert').show();
                        $.each(xhr.responseJSON.errors, function (field, messages) {
                            $.each(messages, function (index, message) {
                                $('#errors').append('<li>' + message + '</li>');
                            });
                        });
                    } else {
                        var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error updating outlet';
                        if (typeof Notiflix !== 'undefined') Notiflix.Notify.failure(msg);
                    }
                }
            });
        });
    });
</script>
@endsection
