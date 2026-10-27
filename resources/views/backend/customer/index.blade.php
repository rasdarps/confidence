@extends('layout.main')
@section('title') {{'View Customers'}} @endsection

@section('content')
 <script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>
    <div class="page-content">
        <div class="container-fluid">
                <!-- start page title -->
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                            <h4 class="mb-sm-0">Customer All</h4>
                        </div>
                    </div>
                </div>
                <!-- end page title -->          
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                          <a href="{{ route('customers.create') }}" class="btn btn-dark" style="float:right;"><i class="fas fa-plus-circle"></i> Add Customer </a> <br>  <br>               
                          <h4 class="card-title">Customer All Data </h4>
                            <div class="table-responsive">
                                {!! $dataTable->table() !!}
                            </div>
                        </div> {{-- card body end --}}
                    </div> {{-- card end --}}
                </div> <!-- end col -->
            </div> <!-- end row -->     
        </div> <!-- container-fluid -->
    </div><!-- Page-content -->
 

@endsection

@section('scripts')
    {{ $dataTable->scripts() }}
@endsection

@stack('scripts')