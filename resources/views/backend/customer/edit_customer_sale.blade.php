@extends('layout.main')
@section('title') {{ 'Update Customer Sale' }} @endsection

@section('content')
<script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>

<style>
    .payment-shell {
        background: linear-gradient(180deg, #f8fbff 0%, #f3f6fb 100%);
        border: 1px solid #e5edf7;
        border-radius: 18px;
        box-shadow: 0 10px 24px rgba(24, 42, 76, 0.06);
        overflow: hidden;
    }
    .payment-shell .card-body {
        padding: 1.25rem;
    }
    .settlement-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.3rem 0.7rem;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 600;
        background: #e8f8ef;
        color: #0f7a47;
    }
    .info-card,
    .totals-card,
    .form-card {
        border: 1px solid #e5edf7;
        border-radius: 14px;
        background: #fff;
    }
    .info-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.75rem;
    }
    .info-item {
        border: 1px solid #edf2f9;
        border-radius: 10px;
        background: #fbfdff;
        padding: 0.75rem;
    }
    .info-label {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #7f8ca3;
        margin-bottom: 0.2rem;
    }
    .info-value {
        font-size: 0.92rem;
        font-weight: 600;
        color: #1c2a39;
        word-break: break-word;
    }
    .metrics-row {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.75rem;
    }
    .metric-box {
        border-radius: 12px;
        padding: 0.8rem 0.95rem;
    }
    .metric-box .metric-label {
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 0.2rem;
        opacity: 0.9;
    }
    .metric-box .metric-value {
        font-size: 1.05rem;
        font-weight: 700;
        line-height: 1.2;
    }
    .metric-total {
        background: #eef3ff;
        color: #2a3f79;
    }
    .metric-paid {
        background: #e8f8ef;
        color: #17643f;
    }
    .metric-due {
        background: #fff4ec;
        color: #8a4520;
    }
    .lines-table thead th {
        border-bottom: 1px solid #dfe8f4;
        color: #5d6b81;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .lines-table tbody td {
        vertical-align: middle;
    }
    .summary-table td {
        border-top: 1px solid #edf2f9;
        padding-top: 0.55rem;
        padding-bottom: 0.55rem;
    }
    .summary-table tr:last-child td {
        border-top: 1px solid #dbe6f5;
        font-weight: 700;
        color: #1a335f;
    }
    @media (max-width: 991px) {
        .info-grid,
        .metrics-row {
            grid-template-columns: 1fr;
        }
    }
</style>

@php
    $saleDetails = $payment->sale->sale_details ?? collect();
    $subtotal = 0;
    foreach ($saleDetails as $detail) {
        $subtotal += (float) $detail->selling_price;
    }
@endphp

<div class="page-content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h4 class="mb-1">Credit Sale Settlement</h4>
                        <p class="text-muted mb-0">Sale No: {{ $payment->sale->sale_no ?? 'N/A' }}</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="settlement-chip">Pending Balance</span>
                        <a href="{{ route('credit.customer') }}" class="btn btn-dark btn-sm">
                            <i class="fa fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card payment-shell">
            <div class="card-body">
                <div class="info-card p-3 mb-3">
                    <h6 class="mb-3">Customer Information</h6>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Customer Name</div>
                            <div class="info-value">{{ $payment->customer->name ?? 'N/A' }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Mobile Number</div>
                            <div class="info-value">{{ $payment->customer->mobile_no ?? 'N/A' }}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Email</div>
                            <div class="info-value">{{ $payment->customer->email ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>

                <div class="totals-card p-3 mb-3">
                    <div class="metrics-row">
                        <div class="metric-box metric-total">
                            <div class="metric-label">Grand Amount</div>
                            <div class="metric-value">Gh¢{{ number_format((float) $payment->total_amount, 2) }}</div>
                        </div>
                        <div class="metric-box metric-paid">
                            <div class="metric-label">Paid Amount</div>
                            <div class="metric-value">Gh¢{{ number_format((float) $payment->paid_amount, 2) }}</div>
                        </div>
                        <div class="metric-box metric-due">
                            <div class="metric-label">Due Amount</div>
                            <div class="metric-value">Gh¢{{ number_format((float) $payment->due_amount, 2) }}</div>
                        </div>
                    </div>
                </div>

                <div class="form-card p-3">
                    <h6 class="mb-3">Sale Items</h6>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle lines-table mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 70px;">#</th>
                                    <th>Category</th>
                                    <th>Product Name</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Unit Price (Gh¢)</th>
                                    <th class="text-end">Line Total (Gh¢)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($saleDetails as $index => $detail)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $detail->category->name ?? 'N/A' }}</td>
                                        <td>{{ $detail->product->name ?? 'N/A' }}</td>
                                        <td class="text-center">{{ $detail->selling_qty }}</td>
                                        <td class="text-end">{{ number_format((float) $detail->unit_selling_price, 2) }}</td>
                                        <td class="text-end">{{ number_format((float) $detail->selling_price, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No sale items found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="row justify-content-end mt-3">
                        <div class="col-lg-5">
                            <table class="table table-sm summary-table mb-0">
                                <tr>
                                    <td class="text-muted">Subtotal</td>
                                    <td class="text-end">{{ number_format($subtotal, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Discount</td>
                                    <td class="text-end">{{ number_format((float) $payment->discount_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Paid</td>
                                    <td class="text-end">{{ number_format((float) $payment->paid_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Due</td>
                                    <td class="text-end">{{ number_format((float) $payment->due_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Grand Amount</td>
                                    <td class="text-end">Gh¢{{ number_format((float) $payment->total_amount, 2) }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <form method="post" action="{{ route('customer.update.sale', $payment->sale_id) }}" class="mt-4" novalidate>
                        @csrf
                        <input type="hidden" name="new_paid_amount" value="{{ $payment->due_amount }}">

                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label">Payment Type</label>
                                <select name="paid_status" id="paid_status" class="form-select" required>
                                    <option value="">Select Status</option>
                                    <option value="full_payment">Full Payment</option>
                                    <option value="partial_payment">Partial Payment</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Amount To Pay</label>
                                <input type="text" name="paid_amount" class="form-control paid_amount paid-amount-input" placeholder="Enter Paid Amount" style="display:none;" inputmode="decimal" autocomplete="off">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Payment Date</label>
                                <input class="form-control" name="date" type="date" id="date" value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-info px-4">Pay Invoice</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    const currentDueAmount = {{ json_encode(round((float) $payment->due_amount, 2)) }};

    $(document).on('change', '#paid_status', function() {
        var paidStatus = $(this).val();
        var paidAmountField = $('.paid_amount');

        if (paidStatus === 'partial_payment') {
            paidAmountField
                .show()
                .prop('required', true)
                .attr('max', currentDueAmount)
                .attr('placeholder', 'Enter Paid Amount (Max ' + currentDueAmount.toFixed(2) + ')');
        } else {
            paidAmountField.hide().prop('required', false).val('');
        }
    });

    $(document).on('submit', 'form[action="{{ route('customer.update.sale', $payment->sale_id) }}"]', function(e) {
        const paidStatus = ($('#paid_status').val() || '').trim();

        if (!paidStatus) {
            e.preventDefault();
            Notiflix.Notify.failure('Please select Payment Type.');
            $('#paid_status').focus();
            return;
        }

        if (paidStatus === 'partial_payment') {
            const amountRaw = ($('.paid-amount-input').val() || '').trim().replace(',', '.');
            const amountValue = parseFloat(amountRaw);

            if (!amountRaw || Number.isNaN(amountValue) || amountValue <= 0) {
                e.preventDefault();
                Notiflix.Notify.failure('Please enter a valid Amount To Pay for partial payment.');
                $('.paid-amount-input').focus();
                return;
            }

            if (amountValue > currentDueAmount) {
                e.preventDefault();
                Notiflix.Notify.failure('Amount To Pay cannot be more than the due amount.');
                $('.paid-amount-input').focus();
            }
        }
    });

    $(document).on('keydown', '.paid-amount-input', function(e) {
        const allowed = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Home', 'End'];
        const ctrlCmd = e.ctrlKey || e.metaKey;

        if (ctrlCmd && ['a', 'c', 'v', 'x'].includes(e.key.toLowerCase())) {
            return;
        }

        const isDecimalKey = ['.', ',', 'Decimal', 'NumpadDecimal'].includes(e.key);
        if (isDecimalKey) {
            if ($(this).val().includes('.')) {
                e.preventDefault();
                return;
            }

            const input = this;
            const start = input.selectionStart ?? input.value.length;
            const end = input.selectionEnd ?? input.value.length;
            const before = input.value.slice(0, start);
            const after = input.value.slice(end);
            input.value = before + '.' + after;
            const pos = start + 1;
            input.setSelectionRange(pos, pos);
            e.preventDefault();
            return;
        }

        if (allowed.includes(e.key) || /^[0-9]$/.test(e.key)) {
            return;
        }

        e.preventDefault();
    });

    $(document).on('input', '.paid-amount-input', function() {
        const input = this;
        const oldValue = input.value || '';
        const oldPos = input.selectionStart ?? oldValue.length;

        let clean = oldValue.replace(',', '.').replace(/[^0-9.]/g, '');
        const firstDot = clean.indexOf('.');
        if (firstDot !== -1) {
            clean = clean.substring(0, firstDot + 1) + clean.substring(firstDot + 1).replace(/\./g, '');
        }

        const parts = clean.split('.');
        if (parts.length === 2) {
            parts[1] = parts[1].substring(0, 2);
            clean = parts[0] + '.' + parts[1];
        }

        if (clean !== oldValue) {
            const removed = oldValue.length - clean.length;
            input.value = clean;
            const newPos = Math.max(0, oldPos - Math.max(0, removed));
            input.setSelectionRange(newPos, newPos);
        }
    });

    $('#paid_status').trigger('change');
</script>
<script src="{{ asset('notiflix/notiflix-aio-3.2.8.min.js') }}"></script>
@endsection
