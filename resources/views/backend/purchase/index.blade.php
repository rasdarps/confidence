@extends('layout.main')
@section('title') {{'View Purchases'}} @endsection

@section('content')
<script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>

<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">All Purchases</h4>  
                </div>
            </div>
        </div>
        <!-- end page title -->

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">

                        @can('purchase-create')
                            <a href="{{ route('purchases.create') }}" class="btn btn-dark btn-rounded waves-effect waves-light" style="float:right;">
                                <i class="fas fa-plus-circle"></i> Add Purchase
                            </a>
                        @endcan
                        <br><br>               

                        <h4 class="card-title">Purchases Data</h4>

                        <!-- 🔎 Filter boxes -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label>Purchase Number</label>
                                <select id="filter_purchase_id" class="form-control select2">
                                    <option value="">All Purchases</option>
                                    @foreach($purchase_invoices as $purchase_invoice)
                                        <option value="{{ $purchase_invoice->id }}">{{ $purchase_invoice->purchase_no }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>Supplier Name</label>
                                <select id="filter_supplier_id" class="form-control">
                                    <option value="">All Suppliers</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>From Date</label>
                                <input type="date" id="filter_from_date" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label>To Date</label>
                                <input type="date" id="filter_to_date" class="form-control">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label>Payment Status</label>
                                <select id="filter_payment_status" class="form-control">
                                    <option value="">All Statuses</option>
                                    <option value="full_payment">Full Payment</option>
                                    <option value="partial_payment">Partial Payment</option>
                                    <option value="full_credit">Full Credit</option>
                                </select>
                            </div>
                            @can('user-filter-view')
                                <div class="col-md-3">
                                    <label>Created By</label>
                                    <select id="filter_created_by" class="form-control select2">
                                        <option value="">All Users</option>
                                        @php
                                            $viewerIsSuperAdmin = auth()->check() && strtolower((string) (auth()->user()->user_type ?? '')) === 'super admin';
                                        @endphp
                                        @foreach($users as $user)
                                            @if($viewerIsSuperAdmin || strtolower((string) ($user->user_type ?? '')) !== 'super admin')
                                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                            @endcan
                            <div class="col-md-3 d-flex align-items-end">
                                <button id="reset_filters" class="btn btn-dark w-100">Reset Filters</button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            {!! $dataTable->table(['class' => 'table table-bordered table-striped'], true) !!}
                        </div>
                    </div>
                </div>
            </div> <!-- end col -->
        </div> <!-- end row -->
    </div> <!-- container-fluid -->
</div>

@can('purchase-cancel')
    {{-- Cancel Purchase Modal --}}
    <div class="modal fade" id="cancelPurchaseModal" tabindex="-1" aria-labelledby="cancelPurchaseModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelPurchaseModalLabel">Cancel Purchase <span id="cancelPurchaseModalNo" class="text-danger"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger py-2">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        <strong>Warning:</strong> This will reverse stock added by this purchase and zero its financial values.
                    </div>
                    <div class="mb-3">
                        <label for="purchase_cancellation_reason" class="form-label">
                            Reason for Cancellation <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="purchase_cancellation_reason" rows="3"
                            placeholder="Enter reason for cancellation..."></textarea>
                        <div id="purchase_reason_error" class="text-danger small mt-1" style="display:none;">
                            Please provide a reason for cancellation.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-danger" id="confirmCancelPurchase">
                        <i class="fas fa-ban me-1"></i> Confirm Cancellation
                    </button>
                </div>
            </div>
        </div>
    </div>
@endcan
@endsection

@section('scripts')
    {{ $dataTable->scripts() }}
    <script src="{{ asset('notiflix/notiflix-aio-3.2.8.min.js') }}"></script>
    <script>
        $(function() {
            let table = $('#allpurchases-table').DataTable();

            // Reload table when filters change
            $('#filter_purchase_id, #filter_supplier_id, #filter_from_date, #filter_to_date, #filter_payment_status, #filter_created_by').on('change', function() {
                table.ajax.reload();
            });

            // Reset filters
            $('#reset_filters').on('click', function() {
                $('#filter_purchase_id').val(null).trigger('change');
                $('#filter_supplier_id').val(null).trigger('change');
                $('#filter_from_date').val('');
                $('#filter_to_date').val('');
                $('#filter_payment_status').val('');
                $('#filter_created_by').val(null).trigger('change');
                table.ajax.reload();
            });

            // Pass filter values into AJAX
            table.on('preXhr.dt', function(e, settings, data) {
                data.purchase_id = $('#filter_purchase_id').val();
                data.supplier_id = $('#filter_supplier_id').val();
                data.from_date = $('#filter_from_date').val();
                data.to_date = $('#filter_to_date').val();
                data.payment_status = $('#filter_payment_status').val();
                data.created_by = $('#filter_created_by').val();
            });

            // Handle DataTables errors gracefully
            $.fn.dataTable.ext.errMode = 'none';
            $('#allpurchases-table').on('error.dt', function(e, settings, techNote, message) {
                Notiflix.Notify.failure("DataTables error: " + message);
            });

            @can('purchase-cancel')
                // Cancel Purchase
                let cancelPurchaseId = null;
                const cancelUrl = '{{ route("purchases.cancel", "__ID__") }}';

                $(document).on('click', '.btn-cancel-purchase', function() {
                    cancelPurchaseId = $(this).data('id');
                    $('#cancelPurchaseModalNo').text('#' + $(this).data('no'));
                    $('#purchase_cancellation_reason').val('');
                    $('#purchase_reason_error').hide();
                    $('#cancelPurchaseModal').modal('show');
                });

                $('#confirmCancelPurchase').on('click', function() {
                    let reason = $('#purchase_cancellation_reason').val().trim();
                    if (!reason) {
                        $('#purchase_reason_error').show();
                        return;
                    }

                    let $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Cancelling...');

                    $.ajax({
                        url: cancelUrl.replace('__ID__', cancelPurchaseId),
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            reason: reason,
                        },
                        success: function(response) {
                            $('#cancelPurchaseModal').modal('hide');
                            if (response.status === 200) {
                                Notiflix.Notify.success(response.message);
                                table.ajax.reload(null, false);
                            } else {
                                Notiflix.Notify.failure(response.message || 'Failed to cancel purchase.');
                            }
                        },
                        error: function(xhr) {
                            let msg = xhr.responseJSON?.message || 'Failed to cancel purchase.';
                            Notiflix.Notify.failure(msg);
                        },
                        complete: function() {
                            $btn.prop('disabled', false).html('<i class="fas fa-ban me-1"></i> Confirm Cancellation');
                        }
                    });
                });
            @endcan
        });
    </script>
@endsection

@section('styles')
    <style>
        /* Ensure main content is not hidden under sidebar */
        /* Remove custom margin, let theme handle sidebar spacing */
        
        .select2 {
            width: 100% !important;
        }
    </style>

@stack('scripts')
