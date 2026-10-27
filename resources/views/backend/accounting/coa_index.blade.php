@extends('layout.main')
@section('title', 'Chart of Accounts')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12"><h4 class="mb-0">Chart of Accounts</h4></div>
        </div>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Create Account</h5>
                <form method="POST" action="{{ route('accounting.setup.coa.store') }}" novalidate>
                    @csrf
                    <div class="row">
                        <div class="col-md-2 mb-2"><label>Code <span class="text-danger">*</span></label><input class="form-control" name="code" required></div>
                        <div class="col-md-4 mb-2"><label>Name <span class="text-danger">*</span></label><input class="form-control" name="name" required></div>
                        <div class="col-md-3 mb-2">
                            <label>Type <span class="text-danger">*</span></label>
                            <select class="form-control" name="type" required>
                                <option value="asset">Asset</option>
                                <option value="liability">Liability</option>
                                <option value="equity">Equity</option>
                                <option value="income">Income</option>
                                <option value="expense">Expense</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-2 d-flex align-items-end">
                            <div>
                                <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="is_bank_account" value="1" id="is_bank_account"><label class="form-check-label" for="is_bank_account">Is bank account</label></div>
                                <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" checked><label class="form-check-label" for="is_active">Active</label></div>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-primary mt-2">Save</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body table-responsive">
                {!! $dataTable->table(['class' => 'table table-bordered table-striped table-sm'], true) !!}
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
{{ $dataTable->scripts() }}
@endsection

@stack('scripts')
