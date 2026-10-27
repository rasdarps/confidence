@extends('layout.main')
@section('title') {{ 'Expense Voucher Details' }} @endsection

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Expense Voucher Details</h4>
                    <div>
                        <a href="{{ route('expense-vouchers.print', encrypt($voucher->id)) }}" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">
                            <i class="fas fa-print"></i> Print
                        </a>
                        <a href="{{ route('expense-vouchers.edit', encrypt($voucher->id)) }}" class="btn btn-success btn-sm">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="{{ route('expense-vouchers.index') }}" class="btn btn-dark btn-sm">
                            Back
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-3"><strong>Date:</strong> {{ optional($voucher->voucher_date)->format('d-m-Y') }}</div>
                            <div class="col-md-3"><strong>Reference:</strong> {{ $voucher->reference_no ?: '-' }}</div>
                            @can('user-audit-view')
                                @php
                                    $viewerIsSuperAdmin = auth()->check() && strtolower((string) (auth()->user()->user_type ?? '')) === 'super admin';
                                    $createdBy = $voucher->createdBy;
                                    $maskCreatedBy = !$viewerIsSuperAdmin && $createdBy && strtolower((string) ($createdBy->user_type ?? '')) === 'super admin';
                                @endphp
                                <div class="col-md-3"><strong>Created By:</strong> {{ $maskCreatedBy ? '-' : (optional($createdBy)->name ?: '-') }}</div>
                            @endcan
                            <div class="col-md-3"><strong>Total:</strong> {{ number_format((float) $voucher->total_amount, 2) }}</div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12"><strong>Notes:</strong> {{ $voucher->notes ?: '-' }}</div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Category</th>
                                        <th>Amount</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($voucher->items as $index => $item)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ optional($item->category)->name }}</td>
                                            <td>{{ number_format((float) $item->amount, 2) }}</td>
                                            <td>{{ $item->description ?: '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center">No expense lines found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="2" class="text-right">Total</th>
                                        <th>{{ number_format((float) $voucher->total_amount, 2) }}</th>
                                        <th></th>
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
