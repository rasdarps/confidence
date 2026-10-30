@extends('layout.main')
@section('title') {{ 'View Credit Sales' }} @endsection

@section('content')
<script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>

<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Credit Sales</h4>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Credit Sales Data</h4>

                        @if($assignedOutletId)
                            <div class="alert alert-info py-2 mb-3" role="alert">
                                <i class="fas fa-store me-1" aria-hidden="true"></i>
                                <strong>Branch Station:</strong> Showing credit sales for <strong>{{ optional($outlets->firstWhere('id', $assignedOutletId))->name ?? 'Assigned Branch' }}</strong>.
                            </div>
                        @elseif(auth()->user() instanceof \App\Models\User && auth()->user()->hasRole('Cashier'))
                            <div class="alert alert-info py-2 mb-3" role="alert">
                                <i class="fas fa-info-circle me-1" aria-hidden="true"></i>
                                Showing payments for your sales only.
                            </div>
                        @endif

                        <div class="row mb-3">
                            @if(empty($assignedOutletId) && isset($outlets) && $outlets->count() > 0)
                                <div class="col-md-3">
                                    <label>Branch / Station</label>
                                    <select id="filter_outlet_id" class="form-control select2">
                                        <option value="">All Branches & Central</option>
                                        @foreach($outlets as $outlet)
                                            <option value="{{ $outlet->id }}">{{ $outlet->name }} {{ $outlet->is_central ? '(Central)' : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="col-md-3">
                                <label>Sale Number</label>
                                <select id="filter_sale_id" class="form-control select2">
                                    <option value="">All Sales</option>
                                    @foreach($sales as $sale)
                                        <option value="{{ $sale->id }}">{{ $sale->sale_no }}</option>
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
                            <div class="col-md-3 mt-md-2">
                                <label>To Date</label>
                                <input type="date" id="filter_to_date" class="form-control">
                            </div>
                            <div class="col-md-3 mt-md-2 d-flex align-items-end">
                                <button id="reset_filters" class="btn btn-dark w-100">Reset Filters</button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            {!! $dataTable->table(['class' => 'table table-bordered table-striped'], true) !!}
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
    $(function() {
        let table = $('#credit-sales-table').DataTable();

        $('#filter_sale_id, #filter_customer_id, #filter_from_date, #filter_to_date, #filter_outlet_id').on('change', function() {
            table.ajax.reload();
        });

        $('#reset_filters').on('click', function() {
            $('#filter_outlet_id').val(null).trigger('change');
            $('#filter_sale_id').val(null).trigger('change');
            $('#filter_customer_id').val(null).trigger('change');
            $('#filter_from_date').val('');
            $('#filter_to_date').val('');
            table.ajax.reload();
        });

        table.on('preXhr.dt', function(e, settings, data) {
            data.outlet_id = $('#filter_outlet_id').val();
            data.sale_id = $('#filter_sale_id').val();
            data.customer_id = $('#filter_customer_id').val();
            data.from_date = $('#filter_from_date').val();
            data.to_date = $('#filter_to_date').val();
        });
    });
</script>
@endsection
