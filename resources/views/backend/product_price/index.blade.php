@extends('layout.main')
@section('title') {{'Product Prices'}} @endsection

@section('content')

<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Product Prices</h4>
                </div>
            </div>
        </div>
        <!-- end page title -->
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title mb-0">Master Product Prices</h4>

                            <div class="d-flex align-items-center gap-2">
                                @if(!auth()->user()->isCentral())
                                    <span class="badge bg-secondary p-2"><i class="fas fa-lock me-1"></i> Read-Only (Central Managed)</span>
                                @endif

                                @can('product-price-history-list')
                                    <a href="{{ route('product-prices.product_price_history') }}" class="btn btn-sm btn-outline-primary" style="border-color:#0F766E; color:#0F766E;">
                                        <i class="fas fa-history me-1"></i> Price Change History
                                    </a>
                                @endcan
                            </div>
                        </div>

                        <div class="alert alert-info d-flex align-items-center mb-3">
                            <i class="fas fa-tags fa-lg mr-3 me-2"></i>
                            <div>
                                <strong>Central Master Pricing:</strong> Product cost and selling prices are maintained centrally by Headquarters. Selling prices defined here take effect across all retail branch stations, POS terminals, and sales transactions enterprise-wide.
                            </div>
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