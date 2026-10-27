@if ($wrapperClass === "")
    
<div>

@else

<div class="{{ $wrapperClass }}" >
    
@endif

    {{ $slot }}

</div>