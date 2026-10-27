<div class="form-group">
    @if($label)
        <label>{{ $label }}</label>
    @endif
    
    <div class="custom-control custom-switch">
        <input 
            type="checkbox" 
            name="{{ $name }}"
            class="custom-control-input @error($name) is-invalid @enderror"
            id="{{ $id }}"
            value="{{ $value }}"
            {{ $checked ? 'checked' : '' }}
            {{ $attributes }}
        >
        <label class="custom-control-label" for="{{ $id }}">
            <span class="switch-text">{{ $checked ? $onText : $offText }}</span>
            @if($help)
                <small class="text-muted">{{ $help }}</small>
            @endif
        </label>
    </div>
    
    @error($name)
        <small class="error-text text-danger">{{ $message }}</small>
    @enderror
    <span class="text-danger error-text" data-field="{{ $name }}"></span>

</div>

@push('scripts')
<script>
    // Optional: Update text when toggle changes
    $(document).ready(function() {
        $('#{{ $id }}').on('change', function() {
            var text = $(this).is(':checked') ? '{{ $onText }}' : '{{ $offText }}';
            $(this).closest('.custom-control').find('.switch-text').text(text);
        });
    });
</script>
@endpush