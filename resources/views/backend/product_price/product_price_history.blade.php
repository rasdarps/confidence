@extends('layout.main')
@section('title') {{'Product Price History'}} @endsection

@section('content')

<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Product Price History</h4>
                </div>
            </div>
        </div>
        <!-- end page title -->
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">

                        {{-- <a href="{{ route('units.create') }}" class="btn btn-success" style="float:right;">
                            <i class="fas fa-plus-circle"> Add Unit </i>
                        </a>  --}}
                        <br><br>               

                        <h4 class="card-title">Product Price History</h4>

                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label>Product</label>
                                <select id="filter_product_id" class="form-control select2">
                                    <option value="">All Products</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }}</option>
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
                            <div class="col-md-3 d-flex align-items-end mt-3">
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

@endsection
@section('scripts')
    {{ $dataTable->scripts() }}
    <script>
        $(function() {
            let table = $('#productpricehistory-table').DataTable();

            $('#filter_product_id, #filter_from_date, #filter_to_date, #filter_created_by').on('change', function() {
                table.ajax.reload();
            });

            $('#reset_filters').on('click', function() {
                $('#filter_product_id').val(null).trigger('change');
                $('#filter_from_date').val('');
                $('#filter_to_date').val('');
                $('#filter_created_by').val(null).trigger('change');
                table.ajax.reload();
            });

            table.on('preXhr.dt', function(e, settings, data) {
                data.product_id = $('#filter_product_id').val();
                data.from_date = $('#filter_from_date').val();
                data.to_date = $('#filter_to_date').val();
                data.created_by = $('#filter_created_by').val();
            });
        });
    </script>
@endsection