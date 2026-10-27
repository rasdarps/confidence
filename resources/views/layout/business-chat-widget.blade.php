@auth
@can('chat-use')
@unless(request()->routeIs('chat.index'))
<style>
    .business-chat-launcher {
        position: fixed;
        right: max(20px, env(safe-area-inset-right));
        bottom: max(72px, calc(env(safe-area-inset-bottom) + 20px));
        z-index: 1020;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 52px;
        height: 52px;
        border: 1px solid #095d57;
        border-radius: 50%;
        background: #0f766e;
        color: #fff;
        font-size: 24px;
        box-shadow: 0 4px 14px rgba(15, 118, 110, 0.25);
        text-decoration: none;
    }
    .business-chat-launcher:hover,
    .business-chat-launcher:focus-visible {
        background: #095d57;
        color: #fff;
    }
    .business-chat-launcher:focus-visible {
        outline: 3px solid #5eead4;
        outline-offset: 3px;
    }
    .business-chat-launcher-label {
        position: absolute;
        right: calc(100% + 10px);
        padding: 6px 10px;
        border-radius: 4px;
        background: #263238;
        color: #fff;
        font-size: 12px;
        white-space: nowrap;
        visibility: hidden;
        opacity: 0;
        pointer-events: none;
    }
    .business-chat-launcher:hover .business-chat-launcher-label,
    .business-chat-launcher:focus-visible .business-chat-launcher-label {
        visibility: visible;
        opacity: 1;
    }
</style>
<a href="{{ route('chat.index') }}" class="business-chat-launcher" data-business-chat-widget aria-label="Open Business Chat" title="Open Business Chat">
    <i class="ri-chat-3-line" aria-hidden="true"></i>
    <span class="business-chat-launcher-label" aria-hidden="true">Business Chat</span>
</a>
@endunless
@endcan
@endauth