@extends('layout.main')
@section('title') {{'View Sales Sale'}} @endsection

@section('content')
<script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>

<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">All Sales</h4>  
                </div>
            </div>
        </div>
        <!-- end page title -->

        <div class="row mb-3">
            <div class="col-xl-4 col-md-6">
                <div class="card mb-0" id="sales-total-card" aria-busy="true">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="flex-grow-1" style="min-width:0;">
                                <h6 class="text-muted mb-2">Total Sales (GHS)</h6>
                                <h3 id="sales-total-amount" class="mb-1" role="status" aria-live="polite" style="font-size:24px;min-height:29px;overflow-wrap:anywhere;font-variant-numeric:tabular-nums;">Loading...</h3>
                                <span id="sales-total-scope" class="text-muted small">All time{{ !empty($isRestrictedToOwnSales) ? ' - Your sales' : '' }}</span>
                            </div>
                            <i class="ri-money-dollar-circle-line text-success ms-3" aria-hidden="true" style="font-size:28px;"></i>
                        </div>
                        <div class="text-muted small mt-2">Cancelled sales excluded</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">

                        @can('sale-create')
                            <a href="{{ route('sales.create') }}" class="btn btn-dark btn-rounded waves-effect waves-light" style="float:right;">
                                <i class="fas fa-plus-circle"></i> New Sales
                            </a>
                        @endcan
                        @can('sale-delivery-verify')
                            <a href="{{ route('sales.delivery.verify.page') }}" class="btn btn-outline-success" style="float:right;margin-right:8px;">
                                <i class="fas fa-barcode"></i> Verify Delivery
                            </a>
                        @endcan
                        <br><br>               

                        {{-- <h4 class="card-title mb-1">Sales Sale Data</h4> --}}
                        @if(!empty($isRestrictedToOwnSales))
                            <div class="alert alert-info py-2 mb-3" role="alert">
                                <i class="fas fa-info-circle me-1"></i>
                                Showing your sales only.
                            </div>
                        @endif

                        <!-- ðŸ”Ž Filter boxes -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label>Sale Number</label>
                                <select id="filter_invoice_id" class="form-control select2">
                                    <option value="">All Sales sales</option>
                                    @foreach($sales as $Sale)
                                        <option value="{{ $Sale->id }}">{{ $Sale->sale_no }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>Customer Name</label>
                                <select id="filter_customer_id" class="form-control select2">
                                    <option value="">All Customers</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
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
                                    <select id="filter_created_by" class="form-control">
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

@can('sale-cancel')
    {{-- Cancel Sale Modal --}}
    <div class="modal fade" id="cancelInvoiceModal" tabindex="-1" aria-labelledby="cancelInvoiceModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelInvoiceModalLabel">Cancel Sale <span id="cancelModalInvoiceNo" class="text-danger"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger py-2">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        <strong>Warning:</strong> This will restore stock for all items in this Sale. This action cannot be undone.
                    </div>
                    <div class="mb-3">
                        <label for="cancellation_reason" class="form-label">
                            Reason for Cancellation <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="cancellation_reason" rows="3"
                            placeholder="Enter reason for cancellation..."></textarea>
                        <div id="reason_error" class="text-danger small mt-1" style="display:none;">
                            Please provide a reason for cancellation.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-danger" id="confirmCancelInvoice">
                        <i class="fas fa-ban me-1"></i> Confirm Cancellation
                    </button>
                </div>
            </div>
        </div>
    </div>
@endcan

@can('sale-receipt-reprint')
    <div class="modal fade" id="reprintSaleModal" tabindex="-1" aria-labelledby="reprintSaleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="reprintSaleForm" class="modal-content" novalidate>
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="reprintSaleModalLabel">Manager Reprint <span id="reprintSaleNo" class="text-warning"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label for="reprint_reason" class="form-label">Reason for reprint <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="reprint_reason" name="reason" rows="3" maxlength="500" required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-print me-1"></i> Record and Print</button>
                </div>
            </form>
        </div>
    </div>
@endcan
@endsection

@section('scripts')
    {{ $dataTable->scripts() }}
    <script src="{{ asset('notiflix/notiflix-aio-3.2.8.min.js') }}"></script>
    <script>
        $(function() {
            let table = $('#allsales-table').DataTable();
            const $totalCard = $('#sales-total-card');
            const $totalAmount = $('#sales-total-amount');
            const $totalScope = $('#sales-total-scope');
            const ownSales = @json(!empty($isRestrictedToOwnSales));

            table.on('xhr.dt', function(event, settings, json) {
                if (json && Number(json.draw) < settings.iDraw) {
                    return;
                }
                $totalCard.attr('aria-busy', 'false');
                if (!json || json.total_sales === undefined || !Number.isFinite(Number(json.total_sales))) {
                    $totalAmount.text('Unavailable');
                    return;
                }
                $totalAmount.text(Number(json.total_sales).toLocaleString('en-GH', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }));
                const filtered = ['#filter_invoice_id', '#filter_customer_id', '#filter_from_date', '#filter_to_date', '#filter_payment_status', '#filter_created_by']
                    .some(selector => Boolean($(selector).val()));
                const searched = Boolean(table.search().trim());
                $totalScope.text((filtered || searched ? 'Filtered total' : 'All time') + (ownSales ? ' - Your sales' : ''));
            });

            @can('sale-receipt-reprint')
                const reprintUrl = '{{ route("sales.receipt.reprint", "__ID__") }}';
                $(document).on('click', '.btn-reprint-sale', function() {
                    const saleId = $(this).data('id');
                    $('#reprintSaleNo').text('#' + $(this).data('no'));
                    $('#reprint_reason').val('');
                    $('#reprintSaleForm').attr('action', reprintUrl.replace('__ID__', saleId));
                    $('#reprintSaleModal').modal('show');
                });

                $('#reprintSaleForm').on('submit', function(event) {
                    const $reason = $('#reprint_reason');
                    if (!$reason.val().trim()) {
                        event.preventDefault();
                        Notiflix.Notify.failure('Please enter a reason for the manager reprint.');
                        $reason.trigger('focus');
                    }
                });
            @endcan

            // Reload table when filters change
            $('#filter_invoice_id, #filter_customer_id, #filter_from_date, #filter_to_date, #filter_payment_status, #filter_created_by').on('change', function() {
                table.ajax.reload();
            });

            // Reset filters
            $('#reset_filters').on('click', function() {
                $('#filter_invoice_id').val(null).trigger('change');
                $('#filter_customer_id').val(null).trigger('change');
                $('#filter_from_date').val('');
                $('#filter_to_date').val('');
                $('#filter_payment_status').val('');
                $('#filter_created_by').val(null).trigger('change');
                table.ajax.reload();
            });

            // Pass filter values into AJAX
            table.on('preXhr.dt', function(e, settings, data) {
                $totalCard.attr('aria-busy', 'true');
                $totalAmount.text('Loading...');
                data.sale_id = $('#filter_invoice_id').val();
                data.customer_id = $('#filter_customer_id').val();
                data.from_date = $('#filter_from_date').val();
                data.to_date = $('#filter_to_date').val();
                data.payment_status = $('#filter_payment_status').val();
                data.created_by = $('#filter_created_by').val();
            });

            // Handle DataTables errors gracefully
            $.fn.dataTable.ext.errMode = 'none';
            $('#allsales-table').on('error.dt', function(e, settings, techNote, message) {
                Notiflix.Notify.failure("DataTables error: " + message);
            });

            @can('sale-cancel')
                // Cancel Sale
                let cancelInvoiceId = null;
                const cancelUrl = '{{ route("sales.cancel", "__ID__") }}';

                $(document).on('click', '.btn-cancel-sale', function() {
                    cancelInvoiceId = $(this).data('id');
                    $('#cancelModalInvoiceNo').text('#' + $(this).data('no'));
                    $('#cancellation_reason').val('');
                    $('#reason_error').hide();
                    $('#cancelInvoiceModal').modal('show');
                });

                $('#confirmCancelInvoice').on('click', function() {
                    let reason = $('#cancellation_reason').val().trim();
                    if (!reason) {
                        $('#reason_error').show();
                        return;
                    }

                    let $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Cancelling...');

                    $.ajax({
                        url: cancelUrl.replace('__ID__', cancelInvoiceId),
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            reason: reason,
                        },
                        success: function(response) {
                            $('#cancelInvoiceModal').modal('hide');
                            if (response.status === 200) {
                                Notiflix.Notify.success(response.message);
                                table.ajax.reload(null, false);
                            } else {
                                Notiflix.Notify.failure(response.message || 'Failed to cancel Sale.');
                            }
                        },
                        error: function(xhr) {
                            let msg = xhr.responseJSON?.message || 'Failed to cancel Sale.';
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

@stack('scripts')

