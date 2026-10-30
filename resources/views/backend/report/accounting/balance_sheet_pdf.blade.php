<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Balance Sheet</title>
    <style>
        @page {
            margin: 16mm 14mm;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111827;
            margin: 0;
            padding: 8px 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #111827;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }
        .company-name {
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            color: #111827;
            margin-bottom: 3px;
        }
        .statement-title {
            font-size: 13px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 4px;
        }
        .statement-meta {
            font-size: 10px;
            color: #374151;
        }
        .statement-table {
            width: 100%;
            border-collapse: collapse;
        }
        .statement-table th,
        .statement-table td {
            border: 1px solid #9ca3af;
            padding: 6px 8px;
        }
        .statement-table thead th {
            background: #f3f4f6;
            text-align: left;
            font-weight: 700;
            color: #111827;
        }
        .statement-table thead th.amount {
            text-align: right;
        }
        .section-row td {
            background: #f3f4f6;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 10px;
            color: #374151;
        }
        .indent {
            padding-left: 18px !important;
        }
        .muted {
            color: #6b7280;
        }
        .center-muted {
            text-align: center;
            color: #6b7280;
        }
        .amount {
            text-align: right;
            white-space: nowrap;
        }
        .subtotal-row td {
            background: #f9fafb;
            font-weight: 700;
        }
        .highlight-row td {
            background: #eef2ff;
            font-weight: 700;
            border-top: 2px solid #111827;
            border-bottom: 2px solid #111827;
        }
        .grand-row td {
            background: #e8f5e9;
            font-weight: 700;
            font-size: 11.5px;
            border-top: 2px solid #111827;
            border-bottom: 3px double #111827;
        }
        .grand-row.unbalanced td {
            background: #ffebee;
        }
        .badge {
            display: inline-block;
            padding: 1px 6px;
            font-size: 9px;
            border: 1px solid #9ca3af;
            background: #ffffff;
            margin-left: 6px;
        }
        .footer {
            margin-top: 18px;
            padding-top: 8px;
            border-top: 1px solid #d1d5db;
            font-size: 9.5px;
            color: #4b5563;
            width: 100%;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }
        .footer-table td {
            border: none;
            padding: 0;
        }
    </style>
</head>
<body>
    @php
        $isBalanced = abs((float) $balanceDifference) < 0.01;
        $outletLabel = !empty($selectedOutlet)
            ? ($selectedOutlet->name . ($selectedOutlet->is_central ? ' (Central)' : ''))
            : 'All Outlets (Combined)';
    @endphp

    <div class="header">
        <div class="company-name">{{ $companyName }}</div>
        <div class="statement-title">BALANCE SHEET (STATEMENT OF FINANCIAL POSITION)</div>
        <div class="statement-meta">
            <strong>Outlet / Scope:</strong> {{ $outletLabel }}
            &nbsp;|&nbsp;
            <strong>As Of Date:</strong> {{ $asOfDate }}
        </div>
    </div>

    <table class="statement-table">
        <thead>
            <tr>
                <th style="width: 16%;">Account Code</th>
                <th>Particulars / Account Name</th>
                <th class="amount" style="width: 24%;">Balance (Gh¢)</th>
            </tr>
        </thead>
        <tbody>
            <!-- 1. ASSETS -->
            <tr class="section-row">
                <td colspan="3">1. Assets</td>
            </tr>
            @forelse($assets as $row)
                <tr>
                    <td class="muted">{{ $row->code }}</td>
                    <td class="indent">{{ $row->name }}</td>
                    <td class="amount">{{ number_format((float) $row->balance, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="center-muted">No asset balances recorded as of {{ $asOfDate }}.</td>
                </tr>
            @endforelse
            <tr class="highlight-row">
                <td colspan="2">Total Assets</td>
                <td class="amount">Gh¢ {{ number_format((float) $assetTotal, 2) }}</td>
            </tr>

            <!-- 2. LIABILITIES -->
            <tr class="section-row">
                <td colspan="3">2. Liabilities</td>
            </tr>
            @forelse($liabilities as $row)
                <tr>
                    <td class="muted">{{ $row->code }}</td>
                    <td class="indent">{{ $row->name }}</td>
                    <td class="amount">{{ number_format((float) $row->balance, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="center-muted">No liability balances recorded as of {{ $asOfDate }}.</td>
                </tr>
            @endforelse
            <tr class="subtotal-row">
                <td colspan="2">Total Liabilities</td>
                <td class="amount">{{ number_format((float) $liabilityTotal, 2) }}</td>
            </tr>

            <!-- 3. EQUITY -->
            <tr class="section-row">
                <td colspan="3">3. Equity &amp; Retained Earnings</td>
            </tr>
            @forelse($equity as $row)
                <tr>
                    <td class="muted">{{ $row->code }}</td>
                    <td class="indent">{{ $row->name }}</td>
                    <td class="amount">{{ number_format((float) $row->balance, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="center-muted">No posted capital/equity accounts recorded as of {{ $asOfDate }}.</td>
                </tr>
            @endforelse
            <tr>
                <td class="muted">&mdash;</td>
                <td class="indent">Current Period Profit / (Loss) As Of {{ $asOfDate }}</td>
                <td class="amount">{{ number_format((float) $currentEarnings, 2) }}</td>
            </tr>
            <tr class="subtotal-row">
                <td colspan="2">Total Equity (Incl. Current Period Profit / Loss)</td>
                <td class="amount">{{ number_format((float) $equityWithEarningsTotal, 2) }}</td>
            </tr>

            <!-- 4. GRAND TOTAL -->
            <tr class="grand-row {{ !$isBalanced ? 'unbalanced' : '' }}">
                <td colspan="2">
                    Total Liabilities &amp; Equity
                    @if($isBalanced)
                        <span class="badge">Balanced</span>
                    @else
                        <span class="badge">Difference: Gh¢ {{ number_format((float) $balanceDifference, 2) }}</span>
                    @endif
                </td>
                <td class="amount">Gh¢ {{ number_format((float) $liabilitiesAndEquityTotal, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <table class="footer-table">
            <tr>
                <td>System-generated from posted accounting journals.</td>
                <td style="text-align: right;">Generated on {{ optional($generatedAt)->format('Y-m-d H:i') }}</td>
            </tr>
        </table>
    </div>
</body>
</html>
