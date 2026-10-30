@extends('layout.main')
@section('title', 'Business Chat')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/reporting-chat.css') }}">
@endsection

@section('content')
<div class="page-content">
    <div class="container-fluid business-chat" id="business-chat" data-endpoint="{{ route('chat.ask') }}" data-conversation="{{ $conversationId }}" data-outlet-id="{{ $selectedOutletId ?? '' }}">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">
                Business Chat
                @if(isset($selectedOutlet) && $selectedOutlet)
                    <span class="badge {{ $selectedOutlet->is_central ? 'bg-primary' : 'bg-info text-dark' }} ms-2 font-size-12">
                        <i class="ri-store-2-line me-1"></i>{{ $selectedOutlet->name }} ({{ $selectedOutlet->code }}){{ $selectedOutlet->is_central ? ' — Central HQ' : ' — Branch Station' }}
                    </span>
                @elseif(!empty($canFilterOutlets))
                    <span class="badge bg-primary ms-2 font-size-12">
                        <i class="ri-building-line me-1"></i>All Outlets &amp; Central Warehouse (Consolidated)
                    </span>
                @endif
            </h4>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-light chat-icon-button" id="chat-new" title="New conversation" aria-label="New conversation">
                    <i class="ri-chat-new-line" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        @if(!empty($canFilterOutlets) && isset($outlets) && $outlets->count() > 0)
        <!-- Multi-Outlet Scope Selector -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card mb-0 shadow-sm" style="border: 1px solid #cbd5e1; border-radius: 10px;">
                    <div class="card-body py-2 px-3">
                        <form method="GET" action="{{ route('chat.index') }}" class="row align-items-center g-2 mb-0" id="chat-outlet-filter-form">
                            <div class="col-md-auto">
                                <span class="fw-bold text-dark font-size-13">
                                    <i class="ri-store-2-line text-primary me-1"></i>Filter Chat Scope by Location:
                                </span>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <select name="outlet_id" id="chat-outlet-select" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">All Outlets &amp; Central Warehouse (Consolidated)</option>
                                    @foreach($outlets as $outlet)
                                        <option value="{{ $outlet->id }}" {{ (int) ($selectedOutletId ?? 0) === (int) $outlet->id ? 'selected' : '' }}>
                                            {{ $outlet->name }} ({{ $outlet->code }}){{ $outlet->is_central ? ' — Central HQ' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @if(!empty($selectedOutletId))
                            <div class="col-md-auto">
                                <a href="{{ route('chat.index', ['outlet_id' => '']) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="ri-refresh-line me-1"></i>Reset to Consolidated
                                </a>
                            </div>
                            @endif
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @elseif(!empty($selectedOutlet))
        <!-- Locked Scope Banner for Branch Staff -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-info border-0 shadow-sm mb-0 py-2 d-flex align-items-center justify-content-between" style="background: #f0fdfa; border-left: 4px solid #0F766E !important; color: #134e4a;">
                    <div>
                        <i class="ri-store-2-line me-1 font-size-15" style="color: #0F766E;"></i>
                        <strong>Branch Scoped Chat:</strong> AI reporting metrics, sales, collections, and stock balances are scoped strictly to <strong>{{ $selectedOutlet->name }} ({{ $selectedOutlet->code }})</strong>.
                    </div>
                    <span class="badge" style="background: #0F766E; color: #fff;">Branch Station</span>
                </div>
            </div>
        </div>
        @endif

        <div class="chat-workspace">
            <div id="chat-messages" class="chat-messages" role="log" aria-label="Conversation" aria-live="polite" aria-relevant="additions">
                <div id="chat-empty" class="chat-empty"><i class="ri-chat-3-line" aria-hidden="true"></i><h5>No reports yet</h5></div>
            </div>
            <div class="chat-composer">
                @if(count($examples))
                <div class="chat-examples" aria-label="Suggested questions">
                    @foreach($examples as $example)
                    <button type="button" class="btn btn-sm btn-light chat-example" data-question="{{ $example }}"><i class="ri-search-line" aria-hidden="true"></i> {{ $example }}</button>
                    @endforeach
                </div>
                @endif
                <form id="chat-form" class="chat-form">
                    <label for="chat-question" class="visually-hidden">Question</label>
                    <input type="text" id="chat-question" class="form-control" maxlength="500" required autocomplete="off" placeholder="What were total sales today?">
                    <button type="submit" id="chat-send" class="btn btn-primary chat-icon-button" title="Send question" aria-label="Send question"><i class="ri-send-plane-2-line" aria-hidden="true"></i></button>
                </form>
                <div id="chat-status" class="chat-status" role="status"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/reporting-chat.js') }}" defer></script>
@endsection
