@extends('layout.main')
@section('title') {{'View Unit'}} @endsection

@section('content')

<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Unit All</h4>
                </div>
            </div>
        </div>
        <!-- end page title -->
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">

                        @if(auth()->user()->isCentral() && auth()->user()->can('unit-create'))
                            <a href="{{ route('units.create') }}" class="btn btn-success" style="float:right;">
                                <i class="fas fa-plus-circle"> Add Unit </i>
                            </a> 
                        @else
                            <span class="badge bg-secondary p-2" style="float:right;"><i class="fas fa-lock me-1"></i> Read-Only (Central Managed)</span>
                        @endif
                        <br><br>

                        <div class="alert alert-info d-flex align-items-center mb-3">
                            <i class="fas fa-balance-scale fa-lg mr-3 me-2"></i>
                            <div>
                                <strong>Enterprise Measurement Units:</strong> Units of measurement are defined centrally at Headquarters and shared across Central Warehouse intake, inter-outlet stock transfers, and branch retail checkout.
                            </div>
                        </div>

                        <h4 class="card-title">Unit All Data</h4>

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
@endsection