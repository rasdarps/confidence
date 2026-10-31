@extends('layout.main')
@section('title') {{'Stock Transfer ' . $transfer->transfer_no}} @endsection

@section('scripts')
<script>
$(document).ready(function() {
    $(document).on('input change', '.recv-pack-input, .recv-piece-input', function() {
        var $row = $(this).closest('tr');
        var ppp = parseInt($(this).data('ppp')) || 1;
        var targetId = $(this).data('target');
        var packs = parseFloat($row.find('.recv-pack-input').val()) || 0;
        var pieces = parseFloat($row.find('.recv-piece-input').val()) || 0;
        var total = Math.round((packs * ppp) + pieces);
        $('#' + targetId).val(total);
        $row.find('[class*="recv-preview-"]').text('(= ' + total + ' total)');
    });
});
</script>
@endsection

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
                                            <td class="text-center">
                                                @if($item->product && $item->product->has_sub_unit && ($item->product->pieces_per_pack ?: 1) > 1)
                                                    <strong>{{ $item->product->formatQuantity($item->qty_dispatched) }}</strong>
                                                    <br><small class="text-muted">({{ (float) $item->qty_dispatched == (int) $item->qty_dispatched ? (int) $item->qty_dispatched : number_format($item->qty_dispatched, 2) }} total {{ $item->product->subUnit?->name ?? 'pcs' }})</small>
                                                @else
                                                    <strong>{{ $item->product ? $item->product->formatQuantity($item->qty_dispatched) : number_format((float) $item->qty_dispatched, 2) }}</strong>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($transfer->status === 'received')
                                                    @if($item->product && $item->product->has_sub_unit && ($item->product->pieces_per_pack ?: 1) > 1)
                                                        <span class="badge bg-success">{{ $item->product->formatQuantity($item->qty_received) }}</span>
                                                        <br><small class="text-muted">({{ (float) $item->qty_received == (int) $item->qty_received ? (int) $item->qty_received : number_format($item->qty_received, 2) }} total {{ $item->product->subUnit?->name ?? 'pcs' }})</small>
                                                    @else
                                                        <span class="badge bg-success">{{ number_format((float) $item->qty_received, 2) }}</span>
                                                    @endif
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
                                            <table class="table table-bordered table-striped align-middle mb-0" style="border: 1px solid #cbd5e1;">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th class="ps-3 pl-3">Product Name</th>
                                                        <th class="text-center" style="width: 200px;">Dispatched Quantity</th>
                                                        <th class="text-center" style="width: 300px; min-width: 280px;">Confirmed Received Qty</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($transfer->items as $item)
                                                        @php
                                                            $p = $item->product;
                                                            $hasSub = $p && $p->has_sub_unit && ($p->pieces_per_pack ?: 1) > 1;
                                                            $dispatched = (float) $item->qty_dispatched;
                                                            $packName = $p?->unit?->name ?? 'Pack';
                                                            $subName = $p?->subUnit?->name ?? 'pcs';
                                                            $ppp = $p?->pieces_per_pack ?: 1;
                                                            $dispPacks = $hasSub ? (int) floor($dispatched / $ppp) : 0;
                                                            $dispPieces = $hasSub ? (int) round(fmod($dispatched, $ppp)) : 0;
                                                        @endphp
                                                        <tr>
                                                            <td class="ps-3 pl-3"><strong>{{ $p->name ?? 'N/A' }}</strong></td>
                                                            <td class="text-center">
                                                                @if($hasSub)
                                                                    <strong class="text-primary fs-6">{{ $p->formatQuantity($dispatched) }}</strong>
                                                                    <br><small class="text-muted fw-bold">({{ (float) $dispatched == (int) $dispatched ? (int) $dispatched : number_format($dispatched, 2) }} {{ $subName }})</small>
                                                                @else
                                                                    <strong class="text-primary fs-6">{{ number_format($dispatched, 2) }} {{ $packName }}</strong>
                                                                @endif
                                                            </td>
                                                            <td class="text-center p-2">
                                                                @if($hasSub)
                                                                    <div class="d-flex align-items-center gap-2 mb-1">
                                                                        <div style="flex: 1; position: relative;">
                                                                            <input type="number" min="0" step="1" class="form-control text-center fw-bold recv-pack-input"
                                                                                style="height: 40px; font-size: 15px; padding-right: 48px; border: 1.5px solid #cbd5e1; border-radius: 8px;"
                                                                                data-ppp="{{ $ppp }}"
                                                                                data-target="recv_qty_{{ $item->id }}"
                                                                                value="{{ $dispPacks }}" placeholder="0">
                                                                            <span class="position-absolute end-0 top-50 translate-middle-y me-2 text-muted fw-semibold small" style="pointer-events: none; font-size: 11px;">{{ $packName }}</span>
                                                                        </div>
                                                                        <span class="fw-bold text-muted fs-5 px-1">+</span>
                                                                        <div style="flex: 1; position: relative;">
                                                                            <input type="number" min="0" step="1" class="form-control text-center fw-bold recv-piece-input"
                                                                                style="height: 40px; font-size: 15px; padding-right: 48px; border: 1.5px solid #cbd5e1; border-radius: 8px;"
                                                                                data-ppp="{{ $ppp }}"
                                                                                data-target="recv_qty_{{ $item->id }}"
                                                                                value="{{ $dispPieces }}" placeholder="0">
                                                                            <span class="position-absolute end-0 top-50 translate-middle-y me-2 text-muted fw-semibold small" style="pointer-events: none; font-size: 11px;">{{ $subName }}</span>
                                                                        </div>
                                                                    </div>
                                                                    <input type="hidden" id="recv_qty_{{ $item->id }}" name="qty_received[{{ $item->id }}]" value="{{ $dispatched }}">
                                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-semibold recv-preview-{{ $item->id }}" style="font-size: 11px;">(= {{ $dispatched }} {{ $subName }})</span>
                                                                @else
                                                                    <input type="number" step="any" min="0" class="form-control text-center fw-bold"
                                                                        style="height: 40px; font-size: 15px; border: 1.5px solid #cbd5e1; border-radius: 8px;"
                                                                        name="qty_received[{{ $item->id }}]"
                                                                        value="{{ $dispatched }}" required>
                                                                @endif
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

