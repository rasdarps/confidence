@extends('layout.main')
@section('title') {{'Company Profile'}} @endsection

@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Corporate Business Profile</h4>         
                    </div>
                </div>
            </div>
            <!-- end page title -->
                        
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">

                            @if(isset($company) && $company)
                                <a href="{{ route('companies.edit', \Illuminate\Support\Facades\Crypt::encrypt($company->id)) }}" class="btn btn-primary" style="float:right;">
                                    <i class="fas fa-edit"></i> Edit Corporate Profile
                                </a>
                            @else
                                <a href="{{ route('companies.create') }}" class="btn btn-dark" style="float:right;">
                                    <i class="fas fa-plus-circle"></i> Add Company
                                </a>
                            @endif
                            <br><br>               

                            <h4 class="card-title">Company Profile Data</h4>

                            <div class="alert alert-info py-2 mb-3" role="alert">
                                <i class="fas fa-building me-1" aria-hidden="true"></i>
                                <strong>Corporate Legal Identity:</strong> Represents your registered company and official Ghana Card (TIN). All <strong>{{ $outletsCount ?? 0 }}</strong> branches and the Central Warehouse operate under this unified legal entity on thermal receipts, financial statements, and tax compliance.
                            </div>
                            
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