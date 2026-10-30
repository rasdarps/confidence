@extends('layout.main')
@section('title', 'Income Statement')

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
    .pnl-statement-table th,
    .pnl-statement-table td {
        padding: 0.65rem 1rem;
    }
    .pnl-section-header {
        background-color: #f8f9fa;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.78rem;
        letter-spacing: 0.04em;
        color: #495057;
    }
    .pnl-subtotal-row {
        background-color: #fdfdfe;
        border-top: 1px solid #dee2e6;
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
    }
    .pnl-highlight-row {
        background-color: #f1f5f9;
        border-top: 2px solid #cbd5e1;
        border-bottom: 2px solid #cbd5e1;
        font-weight: 700;
    }
    .pnl-net-row {
        background-color: #e8f5e9;
        border-top: 2px solid #2e7d32;
        border-bottom: 3px double #2e7d32;
        font-weight: 700;
        font-size: 1.05rem;
    }
    .pnl-net-row.is-loss {
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
        .main-content .card.pnl-statement-card {
            border: none !important;
            box-shadow: none !important;
            background: #ffffff !important;
            border-radius: 0 !important;
            margin: 0 !important;
        }
        .main-content .card.pnl-statement-card > .card-body {
            padding: 12px 32px !important;
        }
        .pnl-print-header {
            text-align: center;
            border-bottom: 2px solid #111827;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }
        .pnl-print-header .company-name {
            font-size: 16pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #111827;
            margin-bottom: 2px;
        }
        .pnl-print-header .statement-title {
            font-size: 13pt;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 4px;
        }
        .pnl-print-header .statement-meta {
            font-size: 9.5pt;
            color: #374151;
        }
        .pnl-statement-table {
            width: 100% !important;
            border-collapse: collapse !important;
            font-size: 10pt !important;
        }
        .pnl-statement-table th,
        .pnl-statement-table td {
            border: 1px solid #9ca3af !important;
            padding: 7px 10px !important;
            color: #111827 !important;
        }
        .pnl-statement-table a {
            color: #111827 !important;
            text-decoration: none !important;
        }
        .pnl-section-header td {
            background-color: #f3f4f6 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .pnl-highlight-row td,
        .pnl-subtotal-row td {
            background-color: #f9fafb !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .pnl-net-row td {
            background-color: #eef2ff !important;
            border-top: 2px solid #111827 !important;
            border-bottom: 3px double #111827 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .pnl-print-footer {
            margin-top: 28px;
            font-size: 9pt;
            color: #4b5563;
        }
    }
</style>

@php
    $lockedOutletId = auth()->check() ? auth()->user()->outlet_id : null;
    $activeOutletId = $lockedOutletId ?? ($outletId ?? null);
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
                    <h4 class="mb-0">Income Statement (Profit &amp; Loss)</h4>
                    <div class="small fw-semibold">
                        {{ $companyName ?? config('app.name', 'Company') }}
                        &mdash; <span class="{{ !empty($selectedOutlet) ? 'text-primary' : 'text-muted' }}">{{ $outletLabel }}</span>
                    </div>
                    <small class="text-muted">Period: {{ $fromDate }} to {{ $toDate }}</small>
                </div>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                    <i class="ri-printer-line me-1"></i> Print Statement
                </button>
            </div>
        </div>

        <!-- KPI Summary Cards (Hidden in Print) -->
        <div class="row mb-3 d-print-none">
            <div class="col-xl col-md-4 col-sm-6 mb-2">
                <div class="card border shadow-sm mb-0 h-100">
                    <div class="card-body py-3">
                        <small class="text-muted d-block">Total Revenue (Gh¢)</small>
                        <h4 class="mb-0 text-success">{{ number_format((float) $income, 2) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-xl col-md-4 col-sm-6 mb-2">
                <div class="card border shadow-sm mb-0 h-100">
                    <div class="card-body py-3">
                        <small class="text-muted d-block">Cost of Goods Sold (Gh¢)</small>
                        <h4 class="mb-0 text-warning">{{ number_format((float) $cogs, 2) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-xl col-md-4 col-sm-6 mb-2">
                <div class="card border shadow-sm mb-0 h-100">
                    <div class="card-body py-3">
                        <small class="text-muted d-block">Gross Profit (Gh¢)</small>
                        <h4 class="mb-0 {{ (float) $grossProfit >= 0 ? 'text-primary' : 'text-danger' }}">
                            {{ number_format((float) $grossProfit, 2) }}
                            <small class="fs-6 fw-normal text-muted">({{ number_format((float) ($grossMargin ?? 0), 1) }}%)</small>
                        </h4>
                    </div>
                </div>
            </div>
            <div class="col-xl col-md-6 col-sm-6 mb-2">
                <div class="card border shadow-sm mb-0 h-100">
                    <div class="card-body py-3">
                        <small class="text-muted d-block">Operating Expenses (Gh¢)</small>
                        <h4 class="mb-0 text-danger">{{ number_format((float) $operatingExpense, 2) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-xl col-md-6 col-sm-12 mb-2">
                <div class="card border shadow-sm mb-0 h-100">
                    <div class="card-body py-3">
                        <small class="text-muted d-block">Net Profit / (Loss) (Gh¢)</small>
                        <h4 class="mb-0 {{ (float) $netProfit >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ number_format((float) $netProfit, 2) }}
                            <small class="fs-6 fw-normal text-muted">({{ number_format((float) ($netMargin ?? 0), 1) }}%)</small>
                        </h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="card pnl-statement-card">
            <div class="card-body">
                <!-- Formal Print Header (Visible Only When Printing) -->
                <div class="d-none d-print-block pnl-print-header">
                    <div class="company-name">{{ $companyName ?? config('app.name', 'Company') }}</div>
                    <div class="statement-title">INCOME STATEMENT (PROFIT &amp; LOSS)</div>
                    <div class="statement-meta">
                        <strong>Outlet / Scope:</strong> {{ $outletLabel }}
                        &nbsp;|&nbsp;
                        <strong>Reporting Period:</strong> {{ $fromDate }} to {{ $toDate }}
                    </div>
                </div>

                <!-- Filter Panel (Hidden in Print) -->
                <div class="report-filter-panel d-print-none">
                    <form method="GET" action="{{ route('accounting.income-statement') }}">
                        <div class="row">
                            <div class="col-lg-4 col-md-4 mb-3">
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

                            <div class="col-lg-4 col-md-4 mb-3">
                                <label for="filter_from_date" class="filter-label">
                                    <i class="ri-calendar-event-line me-1 text-primary"></i>From Date
                                </label>
                                <input type="date" id="filter_from_date" name="from_date" class="form-control" value="{{ $fromDate }}">
                            </div>

                            <div class="col-lg-4 col-md-4 mb-3">
                                <label for="filter_to_date" class="filter-label">
                                    <i class="ri-calendar-check-line me-1 text-primary"></i>To Date
                                </label>
                                <input type="date" id="filter_to_date" name="to_date" class="form-control" value="{{ $toDate }}">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 d-flex justify-content-end" style="gap: 10px;">
                                <button type="submit" class="btn btn-primary btn-filter-action px-4">
                                    <i class="ri-filter-3-line"></i> Filter
                                </button>
                                <a href="{{ route('accounting.income-statement') }}" class="btn btn-outline-secondary btn-filter-action px-4">
                                    <i class="ri-refresh-line"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0 pnl-statement-table">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 14%;">Account Code</th>
                                <th>Particulars / Account Name</th>
                                <th class="text-end" style="width: 24%;">Amount (Gh¢)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- 1. REVENUE SECTION -->
                            <tr class="pnl-section-header">
                                <td colspan="3">1. Operating Revenue / Income</td>
                            </tr>
                            @forelse($incomeRows as $row)
                                <tr>
                                    <td class="text-muted">{{ $row->code }}</td>
                                    <td class="ps-4">
                                        <a href="{{ route('accounting.general-ledger', array_filter(['account_id' => $row->id, 'from_date' => $fromDate, 'to_date' => $toDate, 'outlet_id' => $activeOutletId])) }}" class="text-decoration-none">
                                            {{ $row->name }}
                                        </a>
                                    </td>
                                    <td class="text-end">{{ number_format((float) $row->net_amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No revenue accounts found.</td>
                                </tr>
                            @endforelse
                            <tr class="pnl-subtotal-row">
                                <td colspan="2">Total Revenue / Income</td>
                                <td class="text-end text-success">{{ number_format((float) $income, 2) }}</td>
                            </tr>

                            <!-- 2. COST OF GOODS SOLD SECTION -->
                            <tr class="pnl-section-header">
                                <td colspan="3">2. Less: Cost of Goods Sold (COGS)</td>
                            </tr>
                            @forelse($cogsRows ?? [] as $row)
                                <tr>
                                    <td class="text-muted">{{ $row->code }}</td>
                                    <td class="ps-4">
                                        <a href="{{ route('accounting.general-ledger', array_filter(['account_id' => $row->id, 'from_date' => $fromDate, 'to_date' => $toDate, 'outlet_id' => $activeOutletId])) }}" class="text-decoration-none">
                                            {{ $row->name }}
                                        </a>
                                    </td>
                                    <td class="text-end">({{ number_format((float) $row->net_amount, 2) }})</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No cost of goods sold recorded for this period.</td>
                                </tr>
                            @endforelse
                            <tr class="pnl-subtotal-row">
                                <td colspan="2">Total Cost of Goods Sold</td>
                                <td class="text-end text-warning">({{ number_format((float) $cogs, 2) }})</td>
                            </tr>

                            <!-- 3. GROSS PROFIT -->
                            <tr class="pnl-highlight-row">
                                <td colspan="2">
                                    Gross Profit
                                    <span class="badge bg-light text-dark border ms-2">Gross Margin: {{ number_format((float) ($grossMargin ?? 0), 2) }}%</span>
                                </td>
                                <td class="text-end {{ (float) $grossProfit >= 0 ? 'text-primary' : 'text-danger' }}">
                                    {{ number_format((float) $grossProfit, 2) }}
                                </td>
                            </tr>

                            <!-- 4. OPERATING EXPENSES -->
                            <tr class="pnl-section-header">
                                <td colspan="3">3. Less: Operating &amp; Shrinkage Expenses</td>
                            </tr>
                            @forelse($operatingExpenseRows ?? [] as $row)
                                <tr>
                                    <td class="text-muted">{{ $row->code }}</td>
                                    <td class="ps-4">
                                        <a href="{{ route('accounting.general-ledger', array_filter(['account_id' => $row->id, 'from_date' => $fromDate, 'to_date' => $toDate, 'outlet_id' => $activeOutletId])) }}" class="text-decoration-none">
                                            {{ $row->name }}
                                        </a>
                                    </td>
                                    <td class="text-end">({{ number_format((float) $row->net_amount, 2) }})</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No operating expenses recorded for this period.</td>
                                </tr>
                            @endforelse
                            <tr class="pnl-subtotal-row">
                                <td colspan="2">Total Operating Expenses</td>
                                <td class="text-end text-danger">({{ number_format((float) $operatingExpense, 2) }})</td>
                            </tr>

                            <!-- 5. NET PROFIT / LOSS -->
                            <tr class="pnl-net-row {{ (float) $netProfit < 0 ? 'is-loss' : '' }}">
                                <td colspan="2">
                                    Net {{ (float) $netProfit >= 0 ? 'Profit' : 'Loss' }} for the Period
                                    <span class="badge bg-white text-dark border ms-2">Net Margin: {{ number_format((float) ($netMargin ?? 0), 2) }}%</span>
                                </td>
                                <td class="text-end {{ (float) $netProfit >= 0 ? 'text-success' : 'text-danger' }}">
                                    Gh¢ {{ number_format((float) $netProfit, 2) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Formal Print Footer (Visible Only When Printing) -->
                <div class="d-none d-print-block pnl-print-footer">
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
