@extends('layout.main')
@section('title', 'Income Statement')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Income Statement</h4>
                <form class="d-flex gap-2" method="GET">
                    <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}">
                    <input type="date" name="to_date" class="form-control" value="{{ $toDate }}">
                    <button class="btn btn-primary">Filter</button>
                </form>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between py-2 border-bottom"><span>Total Income</span><strong>{{ number_format($income, 2) }}</strong></div>
                <div class="d-flex justify-content-between py-2 border-bottom"><span>Total Expenses</span><strong>{{ number_format($expense, 2) }}</strong></div>
                <div class="d-flex justify-content-between py-2 mt-2"><span>Net Profit</span><strong>{{ number_format($netProfit, 2) }}</strong></div>
            </div>
        </div>
    </div>
</div>
@endsection
