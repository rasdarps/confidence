@extends('layout.main')
@section('title') {{ 'Edit Cash Reconciliation' }} @endsection

@section('content')
<script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>

<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Edit Cash Reconciliation</h4>
                    <a href="{{ route('cash-reconciliations.show', Crypt::encryptString((string) $reconciliation->id)) }}" class="btn btn-dark btn-sm">Back</a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if(session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif

                        <h4 class="card-title">Reopen / Correct Cash Reconciliation</h4>

                        <form action="{{ route('cash-reconciliations.update', $reconciliation->id) }}" method="POST" id="cash-reconciliation-edit-form">
                            @csrf
                            @method('PATCH')

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label>Business Date</label>
                                    <input type="date" id="business_date" name="business_date" class="form-control" value="{{ old('business_date', optional($reconciliation->business_date)->format('Y-m-d')) }}">
                                </div>
                                <div class="col-md-4">
                                    <label>Opening Float</label>
                                    <input type="number" step="0.01" min="0" id="opening_float" name="opening_float" class="form-control" value="{{ old('opening_float', number_format((float) $reconciliation->opening_float, 2, '.', '')) }}">
                                </div>
                                <div class="col-md-4">
                                    <label>Physical Counted Cash</label>
                                    <input type="number" step="0.01" min="0" id="physical_counted_cash" name="physical_counted_cash" class="form-control" value="{{ old('physical_counted_cash', number_format((float) $reconciliation->physical_counted_cash, 2, '.', '')) }}">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label>Expected Cash Sales (POS only)</label>
                                    <input type="text" id="expected_cash_sales" class="form-control" value="{{ number_format((float) $expected['expected_cash_sales'], 2, '.', '') }}" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label>Expected Cash Expenses</label>
                                    <input type="text" id="expected_cash_expenses" class="form-control" value="{{ number_format((float) $expected['expected_cash_expenses'], 2, '.', '') }}" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label>Expected Closing Cash</label>
                                    <input type="text" id="expected_closing_cash" class="form-control" value="{{ number_format((float) $expected['expected_closing_cash'], 2, '.', '') }}" readonly>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label>Variance</label>
                                    <input type="text" id="variance" class="form-control" value="{{ number_format((float) $reconciliation->variance, 2, '.', '') }}" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label>Variance Type</label>
                                    <input type="text" id="variance_type" class="form-control" value="{{ ucfirst((string) $reconciliation->variance_type) }}" readonly>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label>Notes / Explanation</label>
                                    <textarea name="notes" class="form-control" rows="3" placeholder="Optional note for shortage/overage...">{{ old('notes', $reconciliation->notes) }}</textarea>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary">Update and Resubmit</button>
                                </div>
                            </div>
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
$(function () {
    const expectedUrl = "{{ route('cash-reconciliations.expected') }}";

    function notifyFailure(message) {
        if (window.Notiflix && Notiflix.Notify) {
            Notiflix.Notify.failure(message);
        } else {
            alert(message);
        }
    }

    function toNumber(value) {
        const n = parseFloat(value);
        return Number.isNaN(n) ? 0 : n;
    }

    function formatMoney(value) {
        return Number(value || 0).toFixed(2);
    }

    function refreshVariance() {
        const expected = toNumber($('#expected_closing_cash').val());
        const physical = toNumber($('#physical_counted_cash').val());
        const variance = physical - expected;

        $('#variance').val(formatMoney(variance));

        if (variance > 0) {
            $('#variance_type').val('Overage');
        } else if (variance < 0) {
            $('#variance_type').val('Shortage');
        } else {
            $('#variance_type').val('Balanced');
        }
    }

    function loadExpected() {
        $.get(expectedUrl, {
            business_date: $('#business_date').val(),
            opening_float: $('#opening_float').val()
        }).done(function (res) {
            $('#expected_cash_sales').val(formatMoney(res.expected_cash_sales));
            $('#expected_cash_expenses').val(formatMoney(res.expected_cash_expenses));
            $('#expected_closing_cash').val(formatMoney(res.expected_closing_cash));
            refreshVariance();
        });
    }

    $('#business_date, #opening_float').on('change input', function () {
        loadExpected();
    });

    $('#physical_counted_cash').on('change input', function () {
        refreshVariance();
    });

    $('#cash-reconciliation-edit-form').on('submit', function (event) {
        const businessDate = ($('#business_date').val() || '').trim();
        const openingFloat = ($('#opening_float').val() || '').trim();
        const physicalCountedCash = ($('#physical_counted_cash').val() || '').trim();

        if (!businessDate) {
            event.preventDefault();
            notifyFailure('Please select the business date.');
            $('#business_date').focus();
            return;
        }

        if (!openingFloat || Number(openingFloat) < 0) {
            event.preventDefault();
            notifyFailure('Please enter a valid opening float.');
            $('#opening_float').focus();
            return;
        }

        if (!physicalCountedCash || Number(physicalCountedCash) < 0) {
            event.preventDefault();
            notifyFailure('Please enter a valid physical counted cash value.');
            $('#physical_counted_cash').focus();
        }
    });

    refreshVariance();
});
</script>
@endsection
