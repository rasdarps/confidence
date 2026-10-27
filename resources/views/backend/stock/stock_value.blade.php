@extends('layout.main')
@section('title') {{'View Stock Value Report'}} @endsection

@section('content')
 <script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>

 <div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Stock Value Report</h4> 

                </div>
            </div>
        </div>
        <!-- end page title -->
                        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="product_name_search" class="form-label">Product Name</label>
                                <div class="d-flex gap-2">
                                    <select id="product_name_search" class="form-control select2">
                                        <option value="">All Products</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->name }}">{{ $product->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" id="reset_product_search" class="btn btn-secondary">
                                        Reset
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            {!! $dataTable->table([], true) !!}
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
        $(function () {
            var table = $('#stockvaluereport-table').DataTable();

            $('#product_name_search').on('change', function () {
                var productName = $(this).val();

                if (productName) {
                    table.column(0).search('^' + $.fn.dataTable.util.escapeRegex(productName) + '$', true, false).draw();
                } else {
                    table.column(0).search('').draw();
                }
            });

            $('#reset_product_search').on('click', function () {
                $('#product_name_search').val('');
                table.column(0).search('').draw();
            });
        });
    </script>
@endsection

@stack('scripts')