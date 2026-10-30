@extends('layout.main')
@section('title') {{ 'Edit Purchase' }} @endsection

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header with-border d-flex justify-content-between align-items-center flex-wrap">
                        <h4 class="mb-0">Edit Purchase #{{ $purchase->purchase_no }}</h4>
                        <a href="{{ route('purchases.index') }}" class="btn btn-secondary btn-sm">Back to Purchases</a>
                    </div>

                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="alert alert-info">
                            <div><strong>Note:</strong> You can edit quantity and unit cost price per line.</div>
                            <div>The Already Sold (Min) column shows minimum quantity that must remain to keep stock valid.</div>
                            <div>Setting a quantity to 0 removes that item from the purchase.</div>
                            <div>Window: {{ $editWindowHours }} hours, ends {{ $windowEndsAt->format('d-m-Y H:i') }}.</div>
                        </div>

                        <div class="mb-3 d-flex align-items-center flex-wrap gap-2">
                            <span><strong>Supplier:</strong> {{ optional(optional($purchase->purchase_payment)->supplier)->name ?? 'N/A' }}</span>
                            <span class="ms-3"><strong>Date:</strong> {{ \Carbon\Carbon::parse($purchase->date)->format('d-m-Y') }}</span>
                            <span class="ms-3"><strong>Payment Status:</strong> {{ optional($purchase->purchase_payment)->paid_status ?? 'N/A' }}</span>
                            <span class="ms-3"><strong>Receiving Location:</strong> <span class="badge bg-primary">Central Warehouse</span></span>
                        </div>

                        @if ($purchase->updated_by)
                            <div class="mb-3 text-muted">
                                <strong>Last edited:</strong> {{ optional($purchase->updated_at)->format('d-m-Y H:i') }}
                                by <strong>{{ optional($purchase->updatedBy)->name ?? 'System' }}</strong>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('purchases.update', $encryptedId) }}" id="purchaseEditForm">
                            @csrf
                            @method('PATCH')

                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="editPurchaseTable">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Product</th>
                                            <th>Unit</th>
                                            <th class="text-end">Unit Cost Price</th>
                                            <th class="text-end">Original Qty</th>
                                            <th class="text-end">Already Sold (Min)</th>
                                            <th class="text-end">New Qty</th>
                                            <th class="text-end">New Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($purchase->purchase_details as $detail)
                                            <tr>
                                                <td>{{ optional($detail->product)->name ?? 'Unknown Product' }}</td>
                                                <td>{{ optional(optional($detail->product)->unit)->name ?? 'N/A' }}</td>
                                                <td>
                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        min="0.01"
                                                        name="unit_cost_price[{{ $detail->id }}]"
                                                        class="form-control text-end unit-cost"
                                                        value="{{ number_format((float) $detail->unit_cost_price, 2, '.', '') }}"
                                                        required
                                                    >
                                                </td>
                                                <td class="text-end align-middle">
                                                    <span class="original-qty" data-value="{{ number_format((float) $detail->buying_qty, 2, '.', '') }}">
                                                        {{ number_format((float) $detail->buying_qty, 2) }}
                                                    </span>
                                                </td>
                                                <td class="text-end align-middle text-danger">
                                                    {{ number_format((float) $detail->editable_min_qty, 2) }}
                                                </td>
                                                <td>
                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        min="{{ number_format((float) $detail->editable_min_qty, 2, '.', '') }}"
                                                        name="qty[{{ $detail->id }}]"
                                                        class="form-control text-end line-qty"
                                                        value="{{ number_format((float) $detail->buying_qty, 2, '.', '') }}"
                                                        required
                                                    >
                                                </td>
                                                <td class="text-end align-middle line-total">0.00</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="6" class="text-end">Updated Gross Total</th>
                                            <th class="text-end" id="updatedGrossTotal">0.00</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <div class="mb-3">
                                <label for="reason" class="form-label"><strong>Reason for Edit <span class="text-danger">*</span></strong></label>
                                <textarea name="reason" id="reason" class="form-control" rows="2" required>{{ old('reason') }}</textarea>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label"><strong>Description</strong></label>
                                <textarea name="description" id="description" class="form-control" rows="3">{{ old('description', $purchase->description) }}</textarea>
                            </div>

                            <button type="submit" class="btn btn-success">Save Purchase Update</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
    const table = document.getElementById('editPurchaseTable');
    if (!table) {
        return;
    }

    const grossNode = document.getElementById('updatedGrossTotal');

    function toNumber(value) {
        const parsed = Number(value);
        return Number.isFinite(parsed) ? parsed : 0;
    }

    function recalc() {
        let gross = 0;
        table.querySelectorAll('tbody tr').forEach((row) => {
            const qtyInput = row.querySelector('.line-qty');
            const costInput = row.querySelector('.unit-cost');
            const lineTotalNode = row.querySelector('.line-total');

            const qty = toNumber(qtyInput?.value);
            const cost = toNumber(costInput?.value);
            const lineTotal = qty * cost;

            gross += lineTotal;
            if (lineTotalNode) {
                lineTotalNode.textContent = lineTotal.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
            }
        });

        grossNode.textContent = gross.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    }

    table.addEventListener('input', recalc);
    recalc();
})();
</script>
@endsection
