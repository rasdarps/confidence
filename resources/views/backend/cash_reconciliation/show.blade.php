@php use Carbon\Carbon; @endphp
@extends('layout.main')
@section('title') {{ 'Cash Reconciliation Details' }} @endsection

@section('content')
@php use Illuminate\Support\Facades\Crypt; @endphp
<script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Cash Reconciliation Details</h4>
                    <div>
                        @if(in_array($reconciliation->status, ['submitted', 'reopened'], true))
                            <a href="{{ route('cash-reconciliations.edit', Crypt::encryptString((string) $reconciliation->id)) }}" class="btn btn-success btn-sm">Edit</a>
                        @endif
                        <a href="{{ route('cash-reconciliations.index') }}" class="btn btn-dark btn-sm">Back</a>
                    </div>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Daily Cash Reconciliation</h4>

                        <div class="mb-3">
                            @if($reconciliation->status === 'approved')
                                <span class="badge bg-success">Approved / Locked</span>
                            @elseif($reconciliation->status === 'reopened')
                                <span class="badge bg-warning text-dark">Reopened</span>
                            @else
                                <span class="badge bg-info text-dark">Submitted</span>
                            @endif
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4"><strong>Date:</strong> {{ Carbon::parse($reconciliation->business_date)->format('d-m-Y') }}</div>
                            <div class="col-md-4"><strong>Cashier:</strong> {{ optional($reconciliation->user)->name ?: '-' }}</div>
                            <div class="col-md-4"><strong>Status:</strong> {{ ucfirst((string) $reconciliation->status) }}</div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <tbody>
                                    <tr>
                                        <th>Opening Float</th>
                                        <td>{{ number_format((float) $reconciliation->opening_float, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Expected Cash Sales (POS only)</th>
                                        <td>{{ number_format((float) $reconciliation->expected_cash_sales, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Expected Cash Expenses</th>
                                        <td>{{ number_format((float) $reconciliation->expected_cash_expenses, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Expected Closing Cash</th>
                                        <td>{{ number_format((float) $reconciliation->expected_closing_cash, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Physical Counted Cash</th>
                                        <td>{{ number_format((float) $reconciliation->physical_counted_cash, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Variance</th>
                                        <td>{{ number_format((float) $reconciliation->variance, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Variance Type</th>
                                        <td>{{ ucfirst((string) $reconciliation->variance_type) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Notes</th>
                                        <td>{{ $reconciliation->notes ?: '-' }}</td>
                                    </tr>
                                    @php
                                        $viewerIsSuperAdmin = auth()->check() && strtolower((string) (auth()->user()->user_type ?? '')) === 'super admin';
                                    @endphp
                                    @can('user-audit-view')
                                        <tr>
                                            <th>Created By</th>
                                            @php
                                                $createdBy = $reconciliation->createdBy;
                                                $maskCreatedBy = !$viewerIsSuperAdmin && $createdBy && strtolower((string) ($createdBy->user_type ?? '')) === 'super admin';
                                            @endphp
                                            <td>{{ $maskCreatedBy ? '-' : (optional($createdBy)->name ?: '-') }}</td>
                                        </tr>
                                    @endcan
                                    @if($reconciliation->approved_at)
                                        <tr>
                                            <th>Approved By</th>
                                            @php
                                                $approvedBy = $reconciliation->approvedBy;
                                                $maskApprovedBy = !$viewerIsSuperAdmin && $approvedBy && strtolower((string) ($approvedBy->user_type ?? '')) === 'super admin';
                                            @endphp
                                            <td>{{ $maskApprovedBy ? '-' : (optional($approvedBy)->name ?: '-') }} @if($reconciliation->approved_at) on {{ Carbon::parse($reconciliation->approved_at)->format('d-m-Y H:i') }} @endif</td>
                                        </tr>
                                    @endif
                                    @if($reconciliation->reversed_at)
                                        <tr>
                                            <th>Reversed By</th>
                                            @php
                                                $reversedBy = $reconciliation->reversedBy;
                                                $maskReversedBy = !$viewerIsSuperAdmin && $reversedBy && strtolower((string) ($reversedBy->user_type ?? '')) === 'super admin';
                                            @endphp
                                            <td>{{ $maskReversedBy ? '-' : (optional($reversedBy)->name ?: '-') }} @if($reconciliation->reversed_at) on {{ Carbon::parse($reconciliation->reversed_at)->format('d-m-Y H:i') }} @endif</td>
                                        </tr>
                                        <tr>
                                            <th>Reversal Reason</th>
                                            <td>{{ $reconciliation->reversal_reason ?: '-' }}</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>

                        @if($reconciliation->status !== 'approved')
                            <div class="mt-4 d-flex gap-2">
                                <form action="{{ route('cash-reconciliations.approve', $reconciliation->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-success">Approve & Lock</button>
                                </form>
                            </div>
                        @else
                            <form action="{{ route('cash-reconciliations.reverse-approval', $reconciliation->id) }}" method="POST" class="mt-4" id="reversal-form">
                                @csrf
                                <div class="row mb-3">
                                    <div class="col-md-8">
                                        <label>Supervisor Reversal Reason</label>
                                        <textarea name="reversal_reason" id="reversal_reason" class="form-control" rows="3" placeholder="Reason for unlocking this reconciliation..."></textarea>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-8">
                                        <button type="submit" class="btn btn-warning">Reverse Approval / Unlock</button>
                                    </div>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

                    @section('scripts')
                    <script>
                    $(function () {
                        $('#reversal-form').on('submit', function (event) {
                            const reason = ($('#reversal_reason').val() || '').trim();

                            if (!reason) {
                                event.preventDefault();
                                if (window.Notiflix && Notiflix.Notify) {
                                    Notiflix.Notify.failure('Please enter a reversal reason before unlocking.');
                                }
                                $('#reversal_reason').focus();
                            }
                        });
                    });
                    </script>
                    @endsection
