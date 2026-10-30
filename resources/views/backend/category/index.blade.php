@extends('layout.main')
@section('title') {{'View Categories'}} @endsection
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

                            @if(auth()->user()->isCentral() && auth()->user()->can('category-create'))
                                <a href="{{ route('categories.create') }}" class="btn btn-dark" style="float:right;"><i class="fas fa-plus-circle"> Add Category </i></a>
                            @else
                                <span class="badge bg-secondary p-2" style="float:right;"><i class="fas fa-lock me-1"></i> Read-Only (Central Managed)</span>
                            @endif
                            <br><br>

                            <div class="alert alert-info d-flex align-items-center mb-3">
                                <i class="fas fa-sitemap fa-lg mr-3 me-2"></i>
                                <div>
                                    <strong>Master Catalog Taxonomy:</strong> Product categories are defined centrally at Headquarters and shared globally across Central Warehouse and all branch retail stations.
                                </div>
                            </div>

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