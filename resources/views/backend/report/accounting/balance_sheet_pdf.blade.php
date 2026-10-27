<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Balance Sheet</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111827;
        }

        .header {
            margin-bottom: 14px;
        }

        .title {
            font-size: 18px;
            font-weight: 700;
        }

        .sub {
            font-size: 11px;
            color: #374151;
        }

        .summary {
            width: 100%;
            margin-bottom: 12px;
            border-collapse: collapse;
        }

        .summary td {
            border: 1px solid #d1d5db;
            padding: 7px 8px;
        }

        .summary .label {
            width: 40%;
            background: #f3f4f6;
            font-weight: 600;
        }

        .grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .grid th,
        .grid td {
            border: 1px solid #d1d5db;
            padding: 6px 8px;
        }

        .grid thead th {
            background: #f3f4f6;
            text-align: left;
            font-weight: 700;
        }

        .amount {
            text-align: right;
            white-space: nowrap;
        }

        .section-title {
            margin: 10px 0 6px;
            font-size: 13px;
            font-weight: 700;
        }

        .foot-note {
            margin-top: 10px;
            font-size: 10px;
            color: #374151;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="sub" style="font-size: 13px; font-weight: 700; color: #111827;">{{ $companyName }}</div>
        <div class="title">Balance Sheet</div>
        <div class="sub">As of {{ $asOfDate }}</div>
        <div class="sub">Generated at {{ optional($generatedAt)->format('Y-m-d H:i') }}</div>
    </div>

    <table class="summary">
        <tr>
            <td class="label">Total Assets</td>
            <td class="amount">{{ number_format((float) $assetTotal, 2) }}</td>
        </tr>
        <tr>
            <td class="label">Total Liabilities + Equity (Incl. Profit / Loss)</td>
            <td class="amount">{{ number_format((float) $liabilitiesAndEquityTotal, 2) }}</td>
        </tr>
        <tr>
            <td class="label">Difference</td>
            <td class="amount">{{ number_format((float) $balanceDifference, 2) }}</td>
        </tr>
    </table>

    <div class="section-title">Assets</div>
    <table class="grid">
        <thead>
            <tr>
                <th style="width: 20%;">Code</th>
                <th>Account</th>
                <th class="amount" style="width: 22%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($assets as $row)
                <tr>
                    <td>{{ $row->code }}</td>
                    <td>{{ $row->name }}</td>
                    <td class="amount">{{ number_format((float) $row->balance, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">No asset balances for this date.</td>
                </tr>
            @endforelse
            <tr>
                <td colspan="2"><strong>Total Assets</strong></td>
                <td class="amount"><strong>{{ number_format((float) $assetTotal, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Liabilities</div>
    <table class="grid">
        <thead>
            <tr>
                <th style="width: 20%;">Code</th>
                <th>Account</th>
                <th class="amount" style="width: 22%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($liabilities as $row)
                <tr>
                    <td>{{ $row->code }}</td>
                    <td>{{ $row->name }}</td>
                    <td class="amount">{{ number_format((float) $row->balance, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">No liability balances for this date.</td>
                </tr>
            @endforelse
            <tr>
                <td colspan="2"><strong>Total Liabilities</strong></td>
                <td class="amount"><strong>{{ number_format((float) $liabilityTotal, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Equity</div>
    <table class="grid">
        <thead>
            <tr>
                <th style="width: 20%;">Code</th>
                <th>Account</th>
                <th class="amount" style="width: 22%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($equity as $row)
                <tr>
                    <td>{{ $row->code }}</td>
                    <td>{{ $row->name }}</td>
                    <td class="amount">{{ number_format((float) $row->balance, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">No equity balances for this date.</td>
                </tr>
            @endforelse
            <tr>
                <td colspan="2"><strong>Total Equity</strong></td>
                <td class="amount"><strong>{{ number_format((float) $equityTotal, 2) }}</strong></td>
            </tr>
            <tr>
                <td colspan="2"><strong>Profit / (Loss) As Of Date</strong></td>
                <td class="amount"><strong>{{ number_format((float) $currentEarnings, 2) }}</strong></td>
            </tr>
            <tr>
                <td colspan="2"><strong>Total Equity (Incl. Profit / Loss)</strong></td>
                <td class="amount"><strong>{{ number_format((float) $equityWithEarningsTotal, 2) }}</strong></td>
            </tr>
            <tr>
                <td colspan="2"><strong>Total Liabilities + Equity</strong></td>
                <td class="amount"><strong>{{ number_format((float) $liabilitiesAndEquityTotal, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="foot-note">
        This report is system-generated from posted journals only.
    </div>
</body>
</html>
