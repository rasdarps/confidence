@extends('layout.main')
@section('title') {{ 'Daily Total Sales' }} @endsection

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Daily Total Sales</h4>
                    <div class="page-title-right d-print-none">
                        <a href="{{ route('sales.index') }}" class="btn btn-secondary btn-sm">Back to All Sales</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card d-print-none mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('sales.daily-total-sales') }}" class="row g-2 align-items-end">
                    <div class="{{ $canSelectCashier ? 'col-md-3 col-sm-6' : 'col-md-4 col-sm-6' }}">
                        <label for="date" class="form-label">Select Date</label>
                        <input type="date" id="date" name="date" class="form-control" value="{{ $selectedDate }}" required>
                    </div>
                    @if($canSelectCashier)
                        <div class="col-md-4 col-sm-6">
                            <label for="user_id" class="form-label">Select Cashier</label>
                            <select id="user_id" name="user_id" class="form-control" required>
                                @forelse($cashiers as $cashier)
                                    <option value="{{ $cashier->id }}" {{ (int) $selectedUserId === (int) $cashier->id ? 'selected' : '' }}>
                                        {{ $cashier->username }}{{ !empty($cashier->name) ? ' (' . $cashier->name . ')' : '' }}
                                    </option>
                                @empty
                                    <option value="">No cashier found</option>
                                @endforelse
                            </select>
                        </div>
                    @endif
                    <div class="{{ $canSelectCashier ? 'col-md-5 col-sm-12' : 'col-md-8 col-sm-6' }} d-flex gap-2">
                        <button type="submit" class="btn btn-dark">Load</button>
                        @can('sale-print')
                            <button type="button" onclick="window.print()" class="btn btn-success">
                                <i class="fa fa-print"></i> Print
                            </button>
                        @endcan
                    </div>
                </form>
            </div>
        </div>

        <div class="receipt-container" id="dailyTotalSalesArea">
            <div class="card">
                <div class="card-body">
                    <div class="text-center">
                        <h5 class="mb-1">{{ $companyInfo->name ?? 'N/A' }}</h5>
                        <div>{{ $companyInfo->address ?? 'N/A' }}</div>
                        <div>{{ $companyInfo->phone ?? 'N/A' }} | TIN: {{ $companyInfo->tin_number ?? 'N/A' }}</div>
                        <h6 class="mt-2">Daily Total Sales Report</h6>
                    </div>

                    <hr>

                    <div class="table-responsive">
                        <table class="table table-bordered summary-table mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Username</th>
                                    <th class="text-end">Total Sales Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($selectedDate)->format('d-m-Y') }}</td>
                                    <td>{{ $username }}</td>
                                    <td class="text-end">{{ number_format($totalSalesAmount, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .summary-table th,
    .summary-table td {
        font-size: 16px;
        vertical-align: middle;
    }

    @media print {
        html,
        body {
            width: 80mm !important;
            height: auto !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: visible !important;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14px;
            line-height: 1.3;
            color: #000;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .no-print,
        .btn,
        .topbar,
        .vertical-menu,
        .main-footer,
        .page-title-box,
        .breadcrumb {
            display: none !important;
        }

        body * {
            visibility: hidden;
        }

        #dailyTotalSalesArea,
        #dailyTotalSalesArea * {
            visibility: visible;
        }

        #dailyTotalSalesArea {
            width: 79mm !important;
            max-width: 79mm !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        #dailyTotalSalesArea .card,
        #dailyTotalSalesArea .card-body {
            border: 0 !important;
            box-shadow: none !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        #dailyTotalSalesArea .table {
            width: 100% !important;
            margin-bottom: 0 !important;
            table-layout: fixed;
        }

        #dailyTotalSalesArea .table th,
        #dailyTotalSalesArea .table td {
            font-size: 13px !important;
            padding: 3px 4px !important;
            word-wrap: break-word;
        }

        @page {
            size: 80mm auto;
            margin: 0;
        }
    }
</style>
@endsection
