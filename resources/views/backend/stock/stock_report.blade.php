@extends('layout.main')
@section('title') {{'View Stock Report'}} @endsection

@section('content')
 <script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>

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
                        <h4 class="card-title">Stock Report </h4>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label>Category</label>
                                <select id="filter_category" class="form-control select2">
                                    <option value="">All Categories</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>Unit</label>
                                <select id="filter_unit" class="form-control select2">
                                    <option value="">All Units</option>
                                    @foreach($units as $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
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
    <script src="{{ asset('notiflix/notiflix-aio-3.2.8.min.js') }}"></script>
    <script>
        $(function() {
            let table = $('#stocks-table').DataTable();

            $('#filter_category, #filter_unit').on('change', function() {
                table.ajax.reload();
            });

            $('#reset_filters').on('click', function() {
                $('#filter_category').val(null).trigger('change');
                $('#filter_unit').val(null).trigger('change');
                table.ajax.reload();
            });

            table.on('preXhr.dt', function(e, settings, data) {
                data.category_id = $('#filter_category').val();
                data.unit_id = $('#filter_unit').val();
            });

            $.fn.dataTable.ext.errMode = 'none';
            $('#stocks-table').on('error.dt', function(e, settings, techNote, message) {
                Notiflix.Notify.failure("DataTables error: " + message);
            });
        });
    </script>
@endsection

@stack('scripts')