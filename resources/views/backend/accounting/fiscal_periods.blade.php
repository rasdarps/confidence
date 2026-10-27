@extends('layout.main')
@section('title', 'Fiscal Periods')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12"><h4 class="mb-0">Fiscal Periods</h4></div>
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
