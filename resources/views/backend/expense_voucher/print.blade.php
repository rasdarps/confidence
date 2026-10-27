<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Voucher Print</title>
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
        <h2>Expense Voucher</h2>
        <div><strong>Date:</strong> {{ optional($voucher->voucher_date)->format('d-m-Y') }}</div>
    </div>

    <div class="meta">
        <div><strong>Reference:</strong> {{ $voucher->reference_no ?: '-' }}</div>
        @can('user-audit-view')
            @php
                $viewerIsSuperAdmin = auth()->check() && strtolower((string) (auth()->user()->user_type ?? '')) === 'super admin';
                $createdBy = $voucher->createdBy;
                $maskCreatedBy = !$viewerIsSuperAdmin && $createdBy && strtolower((string) ($createdBy->user_type ?? '')) === 'super admin';
            @endphp
            <div><strong>Created By:</strong> {{ $maskCreatedBy ? '-' : (optional($createdBy)->name ?: '-') }}</div>
        @endcan
        <div><strong>Notes:</strong> {{ $voucher->notes ?: '-' }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 8%;">#</th>
                <th style="width: 36%;">Category</th>
                <th style="width: 20%;">Amount</th>
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
                    <td colspan="4" style="text-align: center;">No expense lines found.</td>
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

    <script>
        window.addEventListener('load', function () {
            window.print();
        });
    </script>
</body>
</html>
