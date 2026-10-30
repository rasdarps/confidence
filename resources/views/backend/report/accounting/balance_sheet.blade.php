@extends('layout.main')
@section('title', 'Balance Sheet')

@section('content')
<style>
    .report-filter-panel {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 18px 20px;
        margin-bottom: 20px;
    }
    .report-filter-panel .filter-label {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #495057;
        margin-bottom: 6px;
        display: block;
    }
    .report-filter-panel .form-control,
    .report-filter-panel .form-select {
        height: 38px;
        border-radius: 6px;
        border-color: #dee2e6;
        font-size: 0.875rem;
    }
    .report-filter-panel .select2-container .select2-selection--single {
        height: 38px !important;
        border-radius: 6px !important;
        border-color: #dee2e6 !important;
        display: flex;
        align-items: center;
    }
    .report-filter-panel .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px !important;
        font-size: 0.875rem;
        padding-left: 12px;
    }
    .report-filter-panel .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
    }
    .report-filter-panel .btn-filter-action {
        height: 38px;
        border-radius: 6px;
        font-weight: 500;
        font-size: 0.875rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }
    .bs-statement-table th,
    .bs-statement-table td {
        padding: 0.65rem 1rem;
    }
    .bs-section-header {
        background-color: #f8f9fa;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.78rem;
        letter-spacing: 0.04em;
        color: #495057;
    }
    .bs-subtotal-row {
        background-color: #fdfdfe;
        border-top: 1px solid #dee2e6;
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
    }
    .bs-highlight-row {
        background-color: #eef2ff;
        border-top: 2px solid #93c5fd;
        border-bottom: 3px double #2563eb;
        font-weight: 700;
        font-size: 1.02rem;
    }
    .bs-grand-row {
        background-color: #e8f5e9;
        border-top: 2px solid #2e7d32;
        border-bottom: 3px double #2e7d32;
        font-weight: 700;
        font-size: 1.05rem;
    }
    .bs-grand-row.is-unbalanced {
        background-color: #ffebee;
        border-top-color: #c62828;
        border-bottom-color: #c62828;
    }

    @media print {
        @page {
            size: A4 portrait;
            margin: 14mm 12mm;
        }
        body,
        .main-content,
        .page-content,
        .container-fluid {
            background: #ffffff !important;
            color: #111827 !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        #page-topbar,
        .vertical-menu,
        .footer,
        .d-print-none {
            display: none !important;
        }
        .main-content .card.bs-statement-card {
            border: none !important;
            box-shadow: none !important;
            background: #ffffff !important;
            border-radius: 0 !important;
            margin: 0 !important;
        }
        .main-content .card.bs-statement-card > .card-body {
            padding: 12px 32px !important;
        }
        .bs-print-header {
            text-align: center;
            border-bottom: 2px solid #111827;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }
        .bs-print-header .company-name {
            font-size: 16pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #111827;
            margin-bottom: 2px;
        }
        .bs-print-header .statement-title {
            font-size: 13pt;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 4px;
        }
        .bs-print-header .statement-meta {
            font-size: 9.5pt;
            color: #374151;
        }
        .bs-statement-table {
            width: 100% !important;
            border-collapse: collapse !important;
            font-size: 10pt !important;
        }
        .bs-statement-table th,
        .bs-statement-table td {
            border: 1px solid #9ca3af !important;
            padding: 7px 10px !important;
            color: #111827 !important;
        }
        .bs-statement-table a {
            color: #111827 !important;
            text-decoration: none !important;
        }
        .bs-section-header td {
            background-color: #f3f4f6 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .bs-subtotal-row td {
            background-color: #f9fafb !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .bs-highlight-row td,
        .bs-grand-row td {
            background-color: #eef2ff !important;
            border-top: 2px solid #111827 !important;
            border-bottom: 3px double #111827 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .bs-print-footer {
            margin-top: 28px;
            font-size: 9pt;
            color: #4b5563;
        }
    }
</style>

@php
    $lockedOutletId = auth()->check() ? auth()->user()->outlet_id : null;
    $activeOutletId = $lockedOutletId ?? ($outletId ?? null);
    $isBalanced = abs((float) $balanceDifference) < 0.01;
    $outletLabel = !empty($selectedOutlet)
        ? ($selectedOutlet->name . ($selectedOutlet->is_central ? ' (Central)' : ''))
        : 'All Outlets (Combined)';
@endphp

<div class="page-content">
    <div class="container-fluid">
        <!-- Screen Header (Hidden in Print) -->
        <div class="row mb-2 d-print-none">
            <div class="col-12 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h4 class="mb-0">Balance Sheet (Statement of Financial Position)</h4>
                    <div class="small fw-semibold">
                        {{ $companyName }}
                        &mdash; <span class="{{ !empty($selectedOutlet) ? 'text-primary' : 'text-muted' }}">{{ $outletLabel }}</span>
                    </div>
                    <small class="text-muted">As of {{ $asOfDate }}</small>
                </div>
                <div class="d-flex align-items-center" style="gap: 8px;">
                    <a href="{{ route('accounting.balance-sheet.pdf', array_filter(['to_date' => $asOfDate, 'outlet_id' => $activeOutletId])) }}" class="btn btn-success btn-sm">
                        <i class="fa fa-file-pdf me-1"></i>Download PDF
                    </a>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                        <i class="ri-printer-line me-1"></i>Print Statement
                    </button>
                </div>
            </div>
        </div>

        <!-- KPI Summary Cards (Hidden in Print) -->
        <div class="row mb-3 d-print-none">
            <div class="col-xl col-md-4 col-sm-6 mb-2">
                <div class="card border shadow-sm mb-0 h-100">
                    <div class="card-body py-3">
                        <small class="text-muted d-block">Total Assets (Gh¢)</small>
                        <h4 class="mb-0 text-primary">{{ number_format((float) $assetTotal, 2) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-xl col-md-4 col-sm-6 mb-2">
                <div class="card border shadow-sm mb-0 h-100">
                    <div class="card-body py-3">
                        <small class="text-muted d-block">Total Liabilities (Gh¢)</small>
                        <h4 class="mb-0 text-warning">{{ number_format((float) $liabilityTotal, 2) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-xl col-md-4 col-sm-6 mb-2">
                <div class="card border shadow-sm mb-0 h-100">
                    <div class="card-body py-3">
                        <small class="text-muted d-block">Total Equity (Incl. Profit / Loss) (Gh¢)</small>
                        <h4 class="mb-0 {{ (float) $equityWithEarningsTotal >= 0 ? 'text-info' : 'text-danger' }}">
                            {{ number_format((float) $equityWithEarningsTotal, 2) }}
                        </h4>
                    </div>
                </div>
            </div>
            <div class="col-xl col-md-6 col-sm-6 mb-2">
                <div class="card border shadow-sm mb-0 h-100">
                    <div class="card-body py-3">
                        <small class="text-muted d-block">Total Liabilities + Equity (Gh¢)</small>
                        <h4 class="mb-0 text-dark">{{ number_format((float) $liabilitiesAndEquityTotal, 2) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-xl col-md-6 col-sm-12 mb-2">
                <div class="card border shadow-sm mb-0 h-100">
                    <div class="card-body py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted d-block">Profit / (Loss) As Of Date (Gh¢)</small>
                            <h4 class="mb-0 {{ (float) $currentEarnings >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format((float) $currentEarnings, 2) }}
                            </h4>
                        </div>
                        <div class="text-end">
                            <small class="text-muted d-block">Difference</small>
                            <h5 class="mb-0 {{ $isBalanced ? 'text-success' : 'text-danger' }}">
                                {{ number_format((float) $balanceDifference, 2) }}
                            </h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card bs-statement-card">
            <div class="card-body">
                <!-- Formal Print Header (Visible Only When Printing) -->
                <div class="d-none d-print-block bs-print-header">
                    <div class="company-name">{{ $companyName }}</div>
                    <div class="statement-title">BALANCE SHEET (STATEMENT OF FINANCIAL POSITION)</div>
                    <div class="statement-meta">
                        <strong>Outlet / Scope:</strong> {{ $outletLabel }}
                        &nbsp;|&nbsp;
                        <strong>As Of Date:</strong> {{ $asOfDate }}
                    </div>
                </div>

                <!-- Filter Panel (Hidden in Print) -->
                <div class="report-filter-panel d-print-none">
                    <form method="GET" action="{{ route('accounting.balance-sheet') }}">
                        <div class="row">
                            <div class="col-lg-6 col-md-6 mb-3">
                                <label for="filter_outlet_id" class="filter-label">
                                    <i class="ri-store-2-line me-1 text-primary"></i>Outlet / Branch
                                </label>
                                <select id="filter_outlet_id" name="outlet_id" class="form-control select2" {{ $lockedOutletId ? 'disabled' : '' }}>
                                    @if(!$lockedOutletId)
                                        <option value="">All Outlets (Combined)</option>
                                    @endif
                                    @foreach($outlets ?? [] as $outlet)
                                        @if(!$lockedOutletId || $lockedOutletId == $outlet->id)
                                            <option value="{{ $outlet->id }}" {{ (string) $activeOutletId === (string) $outlet->id ? 'selected' : '' }}>
                                                {{ $outlet->name }}{{ $outlet->is_central ? ' (Central)' : '' }}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-lg-6 col-md-6 mb-3">
                                <label for="filter_to_date" class="filter-label">
                                    <i class="ri-calendar-check-line me-1 text-primary"></i>As Of Date
                                </label>
                                <input type="date" id="filter_to_date" name="to_date" class="form-control" value="{{ $asOfDate }}">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 d-flex justify-content-end" style="gap: 10px;">
                                <button type="submit" class="btn btn-primary btn-filter-action px-4">
                                    <i class="ri-filter-3-line"></i> Filter
                                </button>
                                <a href="{{ route('accounting.balance-sheet') }}" class="btn btn-outline-secondary btn-filter-action px-4">
                                    <i class="ri-refresh-line"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0 bs-statement-table">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 14%;">Account Code</th>
                                <th>Particulars / Account Name</th>
                                <th class="text-end" style="width: 24%;">Balance (Gh¢)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- 1. ASSETS SECTION -->
                            <tr class="bs-section-header">
                                <td colspan="3">1. Assets</td>
                            </tr>
                            @forelse($assets as $row)
                                <tr>
                                    <td class="text-muted">{{ $row->code }}</td>
                                    <td class="ps-4">
                                        <a href="{{ route('accounting.general-ledger', array_filter(['account_id' => $row->id ?? null, 'to_date' => $asOfDate, 'outlet_id' => $activeOutletId])) }}" class="text-decoration-none">
                                            {{ $row->name }}
                                        </a>
                                    </td>
                                    <td class="text-end">{{ number_format((float) $row->balance, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No asset balances recorded as of {{ $asOfDate }}.</td>
                                </tr>
                            @endforelse
                            <tr class="bs-highlight-row">
                                <td colspan="2">Total Assets</td>
                                <td class="text-end text-primary">Gh¢ {{ number_format((float) $assetTotal, 2) }}</td>
                            </tr>

                            <!-- 2. LIABILITIES SECTION -->
                            <tr class="bs-section-header">
                                <td colspan="3">2. Liabilities</td>
                            </tr>
                            @forelse($liabilities as $row)
                                <tr>
                                    <td class="text-muted">{{ $row->code }}</td>
                                    <td class="ps-4">
                                        <a href="{{ route('accounting.general-ledger', array_filter(['account_id' => $row->id ?? null, 'to_date' => $asOfDate, 'outlet_id' => $activeOutletId])) }}" class="text-decoration-none">
                                            {{ $row->name }}
                                        </a>
                                    </td>
                                    <td class="text-end">{{ number_format((float) $row->balance, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No liability balances recorded as of {{ $asOfDate }}.</td>
                                </tr>
                            @endforelse
                            <tr class="bs-subtotal-row">
                                <td colspan="2">Total Liabilities</td>
                                <td class="text-end text-warning">{{ number_format((float) $liabilityTotal, 2) }}</td>
                            </tr>

                            <!-- 3. EQUITY SECTION -->
                            <tr class="bs-section-header">
                                <td colspan="3">3. Equity &amp; Retained Earnings</td>
                            </tr>
                            @forelse($equity as $row)
                                <tr>
                                    <td class="text-muted">{{ $row->code }}</td>
                                    <td class="ps-4">
                                        <a href="{{ route('accounting.general-ledger', array_filter(['account_id' => $row->id ?? null, 'to_date' => $asOfDate, 'outlet_id' => $activeOutletId])) }}" class="text-decoration-none">
                                            {{ $row->name }}
                                        </a>
                                    </td>
                                    <td class="text-end">{{ number_format((float) $row->balance, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No posted capital/equity accounts recorded as of {{ $asOfDate }}.</td>
                                </tr>
                            @endforelse
                            <tr>
                                <td class="text-muted">&mdash;</td>
                                <td class="ps-4">
                                    <a href="{{ route('accounting.income-statement', array_filter(['to_date' => $asOfDate, 'outlet_id' => $activeOutletId])) }}" class="text-decoration-none">
                                        Current Period Profit / (Loss) As Of {{ $asOfDate }}
                                    </a>
                                </td>
                                <td class="text-end {{ (float) $currentEarnings >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format((float) $currentEarnings, 2) }}
                                </td>
                            </tr>
                            <tr class="bs-subtotal-row">
                                <td colspan="2">Total Equity (Incl. Current Period Profit / Loss)</td>
                                <td class="text-end">{{ number_format((float) $equityWithEarningsTotal, 2) }}</td>
                            </tr>

                            <!-- 4. TOTAL LIABILITIES & EQUITY -->
                            <tr class="bs-grand-row {{ !$isBalanced ? 'is-unbalanced' : '' }}">
                                <td colspan="2">
                                    Total Liabilities &amp; Equity
                                    @if($isBalanced)
                                        <span class="badge bg-white text-success border ms-2">Balanced</span>
                                    @else
                                        <span class="badge bg-white text-danger border ms-2">Difference: Gh¢ {{ number_format((float) $balanceDifference, 2) }}</span>
                                    @endif
                                </td>
                                <td class="text-end {{ $isBalanced ? 'text-success' : 'text-danger' }}">
                                    Gh¢ {{ number_format((float) $liabilitiesAndEquityTotal, 2) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Formal Print Footer (Visible Only When Printing) -->
                <div class="d-none d-print-block bs-print-footer">
                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                        <span>System-generated from posted accounting journals.</span>
                        <span>Printed on {{ now()->format('Y-m-d H:i') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
