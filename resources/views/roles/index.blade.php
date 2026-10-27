@extends('layout.main')
@section('title') {{ 'View Roles' }} @endsection

@section('content')

<div class="page-content">
    <div class="container-fluid">

        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Role All</h4>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">

                        <a href="{{ route('roles.create') }}" class="btn btn-success" style="float:right;">
                            <i class="fas fa-plus-circle"></i> Add Role
                        </a>
                        <br><br>

                        <h4 class="card-title">Role All Data</h4>

                        <div class="table-responsive">
                            {!! $dataTable->table() !!}
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

@endsection

@section('scripts')
    {{ $dataTable->scripts() }}

    <script>
        (function () {
            var flashSuccess = @json(session('success'));

            if (flashSuccess && typeof Notiflix !== 'undefined' && Notiflix.Notify) {
                Notiflix.Notify.success(flashSuccess);
            }

            function submitDelete(form) {
                var $form = $(form);
                $.ajax({
                    url: $form.attr('action'),
                    type: 'POST',
                    data: $form.serialize(),
                    success: function (response) {
                        var message = (response && response.message) ? response.message : 'Role deleted successfully.';

                        if (typeof Notiflix !== 'undefined' && Notiflix.Notify) {
                            Notiflix.Notify.success(message);
                        }

                        setTimeout(function () {
                            window.location.href = "{{ route('roles.index') }}";
                        }, 300);
                    },
                    error: function (xhr) {
                        var message = (xhr.responseJSON && xhr.responseJSON.message)
                            ? xhr.responseJSON.message
                            : 'Failed to delete role.';

                        if (typeof Notiflix !== 'undefined' && Notiflix.Notify) {
                            Notiflix.Notify.failure(message);
                        } else {
                            alert(message);
                        }
                    }
                });
            }

            document.addEventListener('click', function (e) {
                var button = e.target.closest('#roles-table .btn-delete, #roles-table .delete-confirm-btn');
                if (!button) {
                    return;
                }

                e.preventDefault();
                e.stopPropagation();

                var form = button.closest('form');
                if (!form) {
                    return;
                }

                if (typeof Notiflix !== 'undefined' && Notiflix.Confirm && typeof Notiflix.Confirm.show === 'function') {
                    Notiflix.Confirm.show(
                        'Confirm Delete',
                        'Are you sure you want to delete this record?',
                        'Yes, Delete',
                        'Cancel',
                        function () {
                            submitDelete(form);
                        }
                    );
                    return;
                }

                if (window.confirm('Are you sure you want to delete this record?')) {
                    submitDelete(form);
                }
            }, true);
        })();
    </script>
@endsection