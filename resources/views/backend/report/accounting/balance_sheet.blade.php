@extends('layout.main')
@section('title', 'Balance Sheet')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12 d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h4 class="mb-0">Balance Sheet</h4>
                    <div class="small fw-semibold">{{ $companyName }}</div>
                    <small class="text-muted">As of {{ $asOfDate }}</small>
                </div>
                <form class="d-flex flex-wrap align-items-center gap-2" method="GET">
                    <div class="d-flex align-items-center gap-2">
                        <input type="date" name="to_date" class="form-control" value="{{ $asOfDate }}">
                        <button class="btn btn-primary" type="submit">Filter</button>
                        <a href="{{ route('accounting.balance-sheet') }}" class="btn btn-light">Reset</a>
                    </div>
                    <div class="d-flex align-items-center gap-2 ms-lg-2">
                        <a href="{{ route('accounting.balance-sheet.pdf', ['to_date' => $asOfDate]) }}" class="btn btn-success">
                            <i class="fa fa-file-pdf me-1"></i>Download PDF
                        </a>
                        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                            <i class="fa fa-print me-1"></i>Print
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small">Total Assets</div>
                        <h5 class="mb-0">{{ number_format((float) $assetTotal, 2) }}</h5>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small">Total Liabilities + Equity (Incl. Profit / Loss)</div>
                        <h5 class="mb-0">{{ number_format((float) $liabilitiesAndEquityTotal, 2) }}</h5>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small">Profit / (Loss) As Of Date</div>
                        <h5 class="mb-1 {{ (float) $currentEarnings >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ number_format((float) $currentEarnings, 2) }}
                        </h5>
                        <div class="text-muted small">Difference</div>
                        <h5 class="mb-0 {{ abs((float) $balanceDifference) < 0.01 ? 'text-success' : 'text-danger' }}">
                            {{ number_format((float) $balanceDifference, 2) }}
                        </h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="mb-3">Assets</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 22%;">Code</th>
                                        <th>Account</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($assets as $row)
                                        <tr>
                                            <td>{{ $row->code }}</td>
                                            <td>{{ $row->name }}</td>
                                            <td class="text-end">{{ number_format((float) $row->balance, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">No asset balances for this date.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="2">Total Assets</th>
                                        <th class="text-end">{{ number_format((float) $assetTotal, 2) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="mb-3">Liabilities</h5>
                        <div class="table-responsive mb-3">
                            <table class="table table-sm table-striped align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 22%;">Code</th>
                                        <th>Account</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($liabilities as $row)
                                        <tr>
                                            <td>{{ $row->code }}</td>
                                            <td>{{ $row->name }}</td>
                                            <td class="text-end">{{ number_format((float) $row->balance, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">No liability balances for this date.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="2">Total Liabilities</th>
                                        <th class="text-end">{{ number_format((float) $liabilityTotal, 2) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <h5 class="mb-3">Equity</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 22%;">Code</th>
                                        <th>Account</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($equity as $row)
                                        <tr>
                                            <td>{{ $row->code }}</td>
                                            <td>{{ $row->name }}</td>
                                            <td class="text-end">{{ number_format((float) $row->balance, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">No equity balances for this date.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="2">Total Equity</th>
                                        <th class="text-end">{{ number_format((float) $equityTotal, 2) }}</th>
                                    </tr>
                                    <tr>
                                        <th colspan="2">Profit / (Loss) As Of Date</th>
                                        <th class="text-end">{{ number_format((float) $currentEarnings, 2) }}</th>
                                    </tr>
                                    <tr>
                                        <th colspan="2">Total Equity (Incl. Profit / Loss)</th>
                                        <th class="text-end">{{ number_format((float) $equityWithEarningsTotal, 2) }}</th>
                                    </tr>
                                    <tr>
                                        <th colspan="2">Total Liabilities + Equity</th>
                                        <th class="text-end">{{ number_format((float) $liabilitiesAndEquityTotal, 2) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
