@extends('layout.main')
@section('title', 'Cash Flow')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Cash Flow</h4>
                <form class="d-flex gap-2" method="GET">
                    <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}">
                    <input type="date" name="to_date" class="form-control" value="{{ $toDate }}">
                    <button class="btn btn-primary">Filter</button>
                </form>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4"><div class="card"><div class="card-body"><h6>Cash Inflow</h6><h4>{{ number_format($inflow, 2) }}</h4></div></div></div>
            <div class="col-md-4"><div class="card"><div class="card-body"><h6>Cash Outflow</h6><h4>{{ number_format($outflow, 2) }}</h4></div></div></div>
            <div class="col-md-4"><div class="card"><div class="card-body"><h6>Net Cash Change</h6><h4>{{ number_format($net, 2) }}</h4></div></div></div>
        </div>
    </div>
</div>
@endsection
