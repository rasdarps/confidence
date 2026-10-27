@extends('layout.main')
@section('title') {{'View Unit'}} @endsection
@section('styles')
    <style>
        .custom-control-switch {
            display: inline-block;
        }

        .status-text {
            margin-left: 10px;
            font-size: 12px;
            font-weight: 500;
        }

        /* Success highlight animation */
        .bg-success-light {
            background-color: #d4edda !important;
            transition: background-color 0.3s ease;
        }

        /* Modal styling */
        .modal-content {
            border-radius: 10px;
        }

        .modal-header {
            background: #034141;
            color: white;
            border-radius: 10px 10px 0 0;
        }

        .modal-header .close {
            color: white;
            opacity: 1;
        }

        .modal-header .close:hover {
            color: white;
            opacity: 0.8;
        }

        /* Toggle switch styling */
        .custom-control-input:checked ~ .custom-control-label::before {
            background-color: #28a745;
            border-color: #28a745;
        }

        .custom-control-input:focus ~ .custom-control-label::before {
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }

        /* DataTable custom styling */
        #category-table_wrapper .dataTables_filter input {
            border-radius: 20px;
            padding: 5px 15px;
        }

        #category-table tbody tr:hover {
            background-color: #f5f5f5;
            cursor: pointer;
        }

    </style>
@endsection
@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Category All</h4>         
                    </div>
                </div>
            </div>
            <!-- end page title -->
                        
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">

                            <a href="{{ route('categories.create') }}" class="btn btn-dark" style="float:right;"><i class="fas fa-plus-circle"> Add Category </i></a> <br>  <br>               

                            <h4 class="card-title">Category All Data </h4>
                            
                            <div class="table-responsive">
                                {!! $dataTable->table() !!}
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

        $(document).ready(function() {});
    </script>
@endsection