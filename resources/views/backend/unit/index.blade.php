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

                        <a href="{{ route('units.create') }}" class="btn btn-success" style="float:right;">
                            <i class="fas fa-plus-circle"> Add Unit </i>
                        </a> 
                        <br><br>               

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