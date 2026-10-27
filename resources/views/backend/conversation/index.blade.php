@extends('layout.main')
@section('title', 'Business Chat')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/reporting-chat.css') }}">
@endsection

@section('content')
<div class="page-content">
    <div class="container-fluid business-chat" id="business-chat" data-endpoint="{{ route('chat.ask') }}" data-conversation="{{ $conversationId }}">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="mb-0">Business Chat</h4>
            <button type="button" class="btn btn-light chat-icon-button" id="chat-new" title="New conversation" aria-label="New conversation">
                <i class="ri-chat-new-line" aria-hidden="true"></i>
            </button>
        </div>
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