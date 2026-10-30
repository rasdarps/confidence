<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Daily Transaction Report</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.5; color: #1e293b; background-color: #f1f5f9; margin: 0; padding: 20px; }
        .container { max-width: 680px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); }
        .header { background: linear-gradient(135deg, #034141 0%, #0F766E 100%); color: #ffffff; padding: 24px; }
        .header h2 { margin: 0 0 6px 0; color: #ffffff; font-size: 22px; }
        .header p { margin: 3px 0; font-size: 13px; color: #ccfbf1; }
        .content { padding: 24px; }
        .section-title { font-size: 15px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #0F766E; margin: 20px 0 10px 0; border-bottom: 2px solid #e6fffa; padding-bottom: 6px; }
        .outlet-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; font-size: 13px; }
        .outlet-table th { background-color: #f8fafc; color: #475569; font-weight: 600; text-align: right; padding: 10px 12px; border-bottom: 2px solid #e2e8f0; }
        .outlet-table th:first-child { text-align: left; }
        .outlet-table td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; text-align: right; color: #334155; }
        .outlet-table td:first-child { text-align: left; }
        .outlet-table tr.total-row td { background-color: #f0fdfa; font-weight: 700; color: #0f766e; border-top: 2px solid #0f766e; border-bottom: 2px solid #0f766e; }
        .badge-type { display: inline-block; padding: 2px 6px; font-size: 10px; font-weight: 600; border-radius: 4px; background: #e2e8f0; color: #475569; margin-left: 4px; }
        .badge-central { background: #ccfbf1; color: #0f766e; }
        .summary-grid { display: table; width: 100%; margin-bottom: 24px; }
        .summary-col { display: table-cell; width: 25%; padding: 6px; }
        .summary-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; text-align: center; }
        .summary-label { font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600; display: block; margin-bottom: 4px; }
        .summary-value { font-size: 16px; font-weight: 700; color: #0f766e; }
        .summary-value.expense { color: #d97706; }
        .summary-value.shortage { color: #dc2626; }
        .summary-value.net { color: #2563eb; }
        .info-card { background-color: #f8fafc; border-left: 4px solid #0f766e; padding: 12px 16px; border-radius: 4px; font-size: 12px; color: #64748b; line-height: 1.6; margin-top: 20px; }
        .footer { background-color: #f8fafc; padding: 16px 24px; text-align: center; font-size: 11px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
        .footer p { margin: 3px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Daily Transaction Report</h2>
            <p><strong>Company:</strong> {{ $companyName }}</p>
            <p><strong>Business Date:</strong> {{ $businessDate }}</p>
            <p><strong>Report Generated:</strong> {{ $timestamp }}</p>
        </div>

        <div class="content">
            <p style="margin-top: 0;">Good day,</p>
            <p>Please find below today's performance breakdown by outlet station followed by the consolidated enterprise aggregates:</p>

            {{-- 1. Outlet Breakdown Table --}}
            <div class="section-title">📍 Station & Branch Breakdown</div>
            <table class="outlet-table">
                <thead>
                    <tr>
                        <th>Station / Outlet</th>
                        <th>Sales (Gh¢)</th>
                        <th>Expenses (Gh¢)</th>
                        <th>Shortage (Gh¢)</th>
                        <th>Net (Gh¢)</th>
                    </tr>
                </thead>
                <tbody>
                    @if (!empty($breakdown) && count($breakdown) > 0)
                        @foreach ($breakdown as $row)
                            <tr>
                                <td>
                                    <strong>{{ $row['name'] }}</strong>
                                    <span class="badge-type {{ $row['is_central'] ? 'badge-central' : '' }}">
                                        {{ $row['is_central'] ? 'HQ / Central' : 'Retail' }}
                                    </span>
                                </td>
                                <td>{{ number_format($row['sales'], 2) }}</td>
                                <td style="color: {{ $row['expenses'] > 0 ? '#d97706' : '#64748b' }};">{{ number_format($row['expenses'], 2) }}</td>
                                <td style="color: {{ $row['shortage'] > 0 ? '#dc2626' : '#64748b' }};">{{ number_format($row['shortage'], 2) }}</td>
                                <td style="font-weight: 600; color: {{ $row['net'] >= 0 ? '#0f766e' : '#dc2626' }};">
                                    {{ $row['net'] >= 0 ? '+' : '' }}{{ number_format($row['net'], 2) }}
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="5" style="text-align: center; color: #94a3b8; padding: 14px;">No branch activity recorded today.</td>
                        </tr>
                    @endif
                    <tr class="total-row">
                        <td><strong>Consolidated Enterprise Total</strong></td>
                        <td>{{ $salesTotal }}</td>
                        <td>{{ $expensesTotal }}</td>
                        <td>{{ $cashShortage }}</td>
                        <td>{{ (floatval(str_replace(',', '', $netTotal)) >= 0 ? '+' : '') . $netTotal }}</td>
                    </tr>
                </tbody>
            </table>

            {{-- 2. Consolidated Metric Cards --}}
            <div class="section-title">📊 Consolidated Enterprise Totals</div>
            <div class="summary-grid">
                <div class="summary-col">
                    <div class="summary-box">
                        <span class="summary-label">Total Sales</span>
                        <span class="summary-value">Gh¢ {{ $salesTotal }}</span>
                    </div>
                </div>
                <div class="summary-col">
                    <div class="summary-box">
                        <span class="summary-label">Total Expenses</span>
                        <span class="summary-value expense">Gh¢ {{ $expensesTotal }}</span>
                    </div>
                </div>
                <div class="summary-col">
                    <div class="summary-box">
                        <span class="summary-label">Total Shortage</span>
                        <span class="summary-value shortage">Gh¢ {{ $cashShortage }}</span>
                    </div>
                </div>
                <div class="summary-col">
                    <div class="summary-box">
                        <span class="summary-label">Net Flow</span>
                        <span class="summary-value net">Gh¢ {{ $netTotal }}</span>
                    </div>
                </div>
            </div>

            {{-- Audit Details --}}
            <div class="info-card">
                <strong>Dispatch Audit Details:</strong><br>
                • Dispatched By: {{ $sentBy }} ({{ $sendType }})<br>
                • Generation Timestamp: {{ $timestamp }}<br>
                • Business Date: {{ $businessDate }}
            </div>
        </div>

        <div class="footer">
            <p>This is an automated executive notification from {{ $companyName }} Multi-Outlet POS System.</p>
            <p>&copy; {{ now()->year }} {{ $companyName }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
