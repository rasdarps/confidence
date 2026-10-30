@extends('layout.main')
@section('title') {{'View Stock Report'}} @endsection

@section('content')
 <script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>

 <style>
     .report-filter-panel {
         background: #f8fafc;
         border: 1px solid #e2e8f0;
         border-radius: 10px;
         padding: 16px 18px;
         margin-bottom: 20px;
     }
     .report-filter-panel label {
         font-size: 12px;
         font-weight: 600;
         text-transform: uppercase;
         letter-spacing: 0.3px;
         color: #475569;
         margin-bottom: 6px;
         display: block;
     }
 </style>

 <div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Stock Report All</h4>
                </div>
            </div>
        </div>
        <!-- end page title -->
                        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-3">Stock Report</h4>

                        @php
                            $userOutletId = auth()->check() ? auth()->user()->outlet_id : null;
                            $initialOutletId = $userOutletId ?: request('outlet_id');
                        @endphp

                        <div class="report-filter-panel">
                            <div class="row g-3 align-items-end">
                                <div class="col-lg-3 col-md-6">
                                    <label for="filter_category">Category</label>
                                    <select id="filter_category" class="form-control select2">
                                        <option value="">All Categories</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <label for="filter_unit">Unit</label>
                                    <select id="filter_unit" class="form-control select2">
                                        <option value="">All Units</option>
                                        @foreach($units as $unit)
                                            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <label for="filter_outlet">Outlet / Warehouse</label>
                                    <select id="filter_outlet" class="form-control select2" {{ $userOutletId ? 'disabled' : '' }}>
                                        @if(!$userOutletId)
                                            <option value="">All Warehouses & Outlets</option>
                                        @endif
                                        @foreach($outlets as $outlet)
                                            @if(!$userOutletId || (int) $userOutletId === (int) $outlet->id)
                                                <option value="{{ $outlet->id }}" {{ (int) $initialOutletId === (int) $outlet->id ? 'selected' : '' }}>
                                                    {{ $outlet->name }} {{ $outlet->is_central ? '(Central)' : '' }}
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <button id="reset_filters" type="button" class="btn btn-dark w-100">
                                        <i class="ri-refresh-line me-1"></i>Reset Filters
                                    </button>
                                </div>
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
    <script src="{{ asset('notiflix/notiflix-aio-3.2.8.min.js') }}"></script>
    <script>
        $(function() {
            let table = $('#stocks-table').DataTable();

            $('#filter_category, #filter_unit, #filter_outlet').on('change', function() {
                table.ajax.reload();
            });

            $('#reset_filters').on('click', function() {
                $('#filter_category').val(null).trigger('change');
                $('#filter_unit').val(null).trigger('change');
                @if(!$userOutletId)
                    $('#filter_outlet').val(null).trigger('change');
                @endif
                table.ajax.reload();
            });

            table.on('preXhr.dt', function(e, settings, data) {
                data.category_id = $('#filter_category').val();
                data.unit_id = $('#filter_unit').val();
                data.outlet_id = $('#filter_outlet').val();
            });

            $.fn.dataTable.ext.errMode = 'none';
            $('#stocks-table').on('error.dt', function(e, settings, techNote, message) {
                Notiflix.Notify.failure("DataTables error: " + message);
            });
        });
    </script>
@endsection

@stack('scripts')
