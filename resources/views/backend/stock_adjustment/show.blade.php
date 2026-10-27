@php use Illuminate\Support\Facades\Crypt; @endphp
@extends('layout.main')
@section('title') {{ 'Stock Adjustment Details' }} @endsection

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Stock Adjustment Details</h4>
                    <div>
                        @can('stock_adjustment-print')
                            <a href="{{ route('stock-adjustments.print', Crypt::encrypt($adjustment->id)) }}" target="_blank" class="btn btn-secondary btn-sm">
                                <i class="fas fa-print"></i> Print
                            </a>
                        @endcan
                        <a href="{{ route('stock-adjustments.edit', Crypt::encrypt($adjustment->id)) }}" class="btn btn-success btn-sm">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="{{ route('stock-adjustments.index') }}" class="btn btn-dark btn-sm">Back</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-3"><strong>Date:</strong> {{ optional($adjustment->adjustment_date)->format('d-m-Y') }}</div>
                            <div class="col-md-3"><strong>Reference:</strong> {{ $adjustment->reference_no ?: '-' }}</div>
                            <div class="col-md-3"><strong>Type:</strong> {{ ucfirst($adjustment->adjustment_type) }}</div>
                            <div class="col-md-3"><strong>Reason:</strong> {{ $adjustment->reason ?: '-' }}</div>
                        </div>

                        <div class="row mb-3">
                            @can('user-audit-view')
                                @php
                                    $viewerIsSuperAdmin = auth()->check() && strtolower((string) (auth()->user()->user_type ?? '')) === 'super admin';
                                    $createdBy = $adjustment->createdBy;
                                    $maskCreatedBy = !$viewerIsSuperAdmin && $createdBy && strtolower((string) ($createdBy->user_type ?? '')) === 'super admin';
                                @endphp
                                <div class="col-md-6"><strong>Created By:</strong> {{ $maskCreatedBy ? '-' : (optional($createdBy)->name ?: '-') }}</div>
                            @endcan
                            <div class="col-md-6"><strong>Notes:</strong> {{ $adjustment->notes ?: '-' }}</div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Product</th>
                                        <th>Qty</th>
                                        <th>Unit Cost</th>
                                        <th>Amount</th>
                                        <th>Before Qty</th>
                                        <th>After Qty</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($adjustment->items as $index => $item)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ optional($item->product)->name }}</td>
                                            <td>{{ number_format((float) $item->qty, 2) }}</td>
                                            <td>{{ number_format((float) $item->unit_cost, 2) }}</td>
                                            <td>{{ number_format((float) $item->amount, 2) }}</td>
                                            <td>{{ number_format((float) $item->before_qty, 2) }}</td>
                                            <td>{{ number_format((float) $item->after_qty, 2) }}</td>
                                            <td>{{ $item->description ?: '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center">No lines found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="2" class="text-right">Totals</th>
                                        <th>{{ number_format((float) $adjustment->total_qty, 2) }}</th>
                                        <th></th>
                                        <th>{{ number_format((float) $adjustment->total_value, 2) }}</th>
                                        <th colspan="3"></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
