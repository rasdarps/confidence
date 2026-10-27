<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Adjustment Print</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; color: #222; }
        .header { display: flex; justify-content: space-between; margin-bottom: 20px; }
        h2 { margin: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background: #f2f2f2; text-align: left; }
        .text-right { text-align: right; }
        .meta { margin-top: 10px; }
        .meta div { margin-bottom: 4px; }
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 15px;">
        <button onclick="window.print()">Print</button>
        <button onclick="window.close()">Close</button>
    </div>

    <div class="header">
        <h2>Stock Adjustment Voucher</h2>
        <div><strong>Date:</strong> {{ optional($adjustment->adjustment_date)->format('d-m-Y') }}</div>
    </div>

    <div class="meta">
        <div><strong>Reference:</strong> {{ $adjustment->reference_no ?: '-' }}</div>
        <div><strong>Type:</strong> {{ ucfirst($adjustment->adjustment_type) }}</div>
        <div><strong>Reason:</strong> {{ $adjustment->reason ?: '-' }}</div>
        @can('user-audit-view')
            @php
                $viewerIsSuperAdmin = auth()->check() && strtolower((string) (auth()->user()->user_type ?? '')) === 'super admin';
                $createdBy = $adjustment->createdBy;
                $maskCreatedBy = !$viewerIsSuperAdmin && $createdBy && strtolower((string) ($createdBy->user_type ?? '')) === 'super admin';
            @endphp
            <div><strong>Created By:</strong> {{ $maskCreatedBy ? '-' : (optional($createdBy)->name ?: '-') }}</div>
        @endcan
        <div><strong>Notes:</strong> {{ $adjustment->notes ?: '-' }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 6%;">#</th>
                <th style="width: 28%;">Product</th>
                <th style="width: 12%;">Before Qty</th>
                <th style="width: 12%;">Qty</th>
                <th style="width: 12%;">After Qty</th>
                <th style="width: 12%;">Unit Cost</th>
                <th style="width: 12%;">Amount</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            @forelse($adjustment->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ optional($item->product)->name }}</td>
                    <td>{{ number_format((float) $item->before_qty, 2) }}</td>
                    <td>{{ number_format((float) $item->qty, 2) }}</td>
                    <td>{{ number_format((float) $item->after_qty, 2) }}</td>
                    <td>{{ number_format((float) $item->unit_cost, 2) }}</td>
                    <td>{{ number_format((float) $item->amount, 2) }}</td>
                    <td>{{ $item->description ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center;">No adjustment lines found.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="text-right">Totals</th>
                <th>{{ number_format((float) $adjustment->total_qty, 2) }}</th>
                <th></th>
                <th></th>
                <th>{{ number_format((float) $adjustment->total_value, 2) }}</th>
                <th></th>
            </tr>
        </tfoot>
    </table>

    <script>
        window.addEventListener('load', function () {
            window.print();
        });
    </script>
</body>
</html>
