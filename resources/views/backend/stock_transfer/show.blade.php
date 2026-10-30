@extends('layout.main')
@section('title') {{'Stock Transfer ' . $transfer->transfer_no}} @endsection

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Stock Transfer Details: {{ $transfer->transfer_no }}</h4>
                    <div class="page-title-right">
                        <a href="{{ route('stock-transfers.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Transfers
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <!-- end page title -->

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <h5 class="text-primary"><i class="fas fa-file-invoice"></i> Transfer Info</h5>
                                <p class="mb-1"><strong>Transfer #:</strong> {{ $transfer->transfer_no }}</p>
                                <p class="mb-1"><strong>Date:</strong> {{ \Carbon\Carbon::parse($transfer->transfer_date)->format('d-m-Y') }}</p>
                                <p class="mb-1"><strong>Status:</strong>
                                    @if($transfer->status === 'received')
                                        <span class="badge bg-success fs-6">Received</span>
                                    @elseif($transfer->status === 'dispatched')
                                        <span class="badge bg-warning text-dark fs-6">In Transit / Dispatched</span>
                                    @elseif($transfer->status === 'cancelled')
                                        <span class="badge bg-danger fs-6">Cancelled</span>
                                    @endif
                                </p>
                            </div>

                            <div class="col-md-4">
                                <h5 class="text-primary"><i class="fas fa-warehouse"></i> Route Information</h5>
                                <p class="mb-1"><strong>From (Source):</strong> {{ $transfer->fromOutlet->name ?? 'N/A' }} ({{ $transfer->fromOutlet->code ?? '' }})</p>
                                <p class="mb-1"><strong>To (Destination):</strong> {{ $transfer->toOutlet->name ?? 'N/A' }} ({{ $transfer->toOutlet->code ?? '' }})</p>
                                @if($transfer->notes)
                                    <p class="mb-1"><strong>Notes:</strong> {{ $transfer->notes }}</p>
                                @endif
                            </div>

                            <div class="col-md-4">
                                <h5 class="text-primary"><i class="fas fa-user-check"></i> Audit Trail</h5>
                                <p class="mb-1"><strong>Dispatched By:</strong> {{ $transfer->dispatchedBy->name ?? 'System' }}</p>
                                <p class="mb-1"><strong>Dispatched At:</strong> {{ $transfer->dispatched_at ? \Carbon\Carbon::parse($transfer->dispatched_at)->format('d-m-Y g:i A') : 'N/A' }}</p>
                                @if($transfer->status === 'received')
                                    <p class="mb-1"><strong>Received By:</strong> {{ $transfer->receivedBy->name ?? 'System' }}</p>
                                    <p class="mb-1"><strong>Received At:</strong> {{ $transfer->received_at ? \Carbon\Carbon::parse($transfer->received_at)->format('d-m-Y g:i A') : 'N/A' }}</p>
                                @endif
                            </div>
                        </div>

                        <hr>

                        <h5 class="mb-3">Transferred Items</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Product Name</th>
                                        <th>Barcode</th>
                                        <th>Unit</th>
                                        <th class="text-center">Qty Dispatched</th>
                                        <th class="text-center">Qty Received</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($transfer->items as $index => $item)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td><strong>{{ $item->product->name ?? 'N/A' }}</strong></td>
                                            <td>{{ $item->product->barcode ?? 'N/A' }}</td>
                                            <td>{{ $item->product->unit->name ?? 'pcs' }}</td>
                                            <td class="text-center">{{ number_format((float) $item->qty_dispatched, 2) }}</td>
                                            <td class="text-center">
                                                @if($transfer->status === 'received')
                                                    <span class="badge bg-success">{{ number_format((float) $item->qty_received, 2) }}</span>
                                                @else
                                                    <span class="text-muted">Pending receipt</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="table-light">
                                        <th colspan="4" class="text-end">Total Units:</th>
                                        <th class="text-center">{{ number_format((float) $transfer->items->sum('qty_dispatched'), 2) }}</th>
                                        <th class="text-center">
                                            @if($transfer->status === 'received')
                                                {{ number_format((float) $transfer->items->sum('qty_received'), 2) }}
                                            @else
                                                -
                                            @endif
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        @if($canReceive)
                            <div class="card border-success mt-4" id="receive-section">
                                <div class="card-header bg-success text-white">
                                    <h5 class="card-title text-white mb-0"><i class="fas fa-box-open"></i> Receive Goods into Branch Stock</h5>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted">Verify the incoming quantities and click confirm to credit this branch's local POS inventory.</p>
                                    <form action="{{ route('stock-transfers.receive', Crypt::encrypt($transfer->id)) }}" method="POST">
                                        @csrf
                                        <div class="table-responsive mb-3">
                                            <table class="table table-sm table-bordered">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Product</th>
                                                        <th class="text-center">Dispatched Qty</th>
                                                        <th class="text-center" style="width: 200px;">Confirmed Received Qty</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($transfer->items as $item)
                                                        <tr>
                                                            <td>{{ $item->product->name ?? 'N/A' }}</td>
                                                            <td class="text-center">{{ number_format((float) $item->qty_dispatched, 2) }}</td>
                                                            <td class="text-center">
                                                                <input type="number" step="any" min="0" class="form-control text-center"
                                                                    name="qty_received[{{ $item->id }}]"
                                                                    value="{{ (float) $item->qty_dispatched }}" required>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        <button type="submit" class="btn btn-success px-4" onclick="return confirm('Confirm receipt of these items into branch inventory?');">
                                            <i class="fas fa-check-double"></i> Confirm & Receive Stock
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endif

                        @if($transfer->status === 'dispatched')
                            @can('stock-transfer-cancel')
                                <div class="mt-4 pt-3 border-top">
                                    <form action="{{ route('stock-transfers.cancel', Crypt::encrypt($transfer->id)) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel this transfer and return stock to the source warehouse?');">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-danger">
                                            <i class="fas fa-times-circle"></i> Cancel Transfer & Revert Stock
                                        </button>
                                    </form>
                                </div>
                            @endcan
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

