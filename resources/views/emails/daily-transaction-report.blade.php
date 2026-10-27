<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .header h2 { margin: 0 0 10px 0; color: #111827; }
        .header p { margin: 5px 0; font-size: 14px; color: #666; }
        .content { margin: 20px 0; }
        .metric { margin: 15px 0; padding: 15px; border-left: 4px solid #007bff; background-color: #f0f7ff; }
        .metric-label { font-weight: bold; color: #111827; font-size: 14px; }
        .metric-value { font-size: 20px; color: #007bff; font-weight: bold; margin-top: 5px; }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
        .info-box { background-color: #e7f3ff; padding: 10px; border-radius: 5px; font-size: 13px; margin-top: 10px; }
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
            <p>Good day,</p>
            <p>Please find below today's transaction summary:</p>

            <div class="metric">
                <div class="metric-label">💰 Daily Total Sales</div>
                <div class="metric-value">GHS {{ $salesTotal }}</div>
            </div>

            <div class="metric">
                <div class="metric-label">📊 Daily Total Expenses</div>
                <div class="metric-value">GHS {{ $expensesTotal }}</div>
            </div>

            <div class="metric">
                <div class="metric-label">⚠️ Daily Cash Shortage</div>
                <div class="metric-value">GHS {{ $cashShortage }}</div>
            </div>

            <div class="info-box">
                <strong>Report Details:</strong><br>
                • Sent By: {{ $sentBy }}<br>
                • Send Type: {{ $sendType }}<br>
                • Timestamp: {{ $timestamp }}
            </div>
        </div>

        <div class="footer">
            <p>This is an automated email from {{ $companyName }} POS System. Please do not reply to this email.</p>
            <p>&copy; {{ now()->year }} {{ $companyName }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
