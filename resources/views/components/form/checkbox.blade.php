<div class="form-group">
    <div class="form-check">
        <input 
            type="checkbox" 
            name="{{ $name }}"
            class="form-check-input @error($name) is-invalid @enderror"
            id="{{ $id }}"
            value="{{ $value }}"
            {{ $checked ? 'checked' : '' }}
            {{ $attributes }}
        >
        <label class="form-check-label" for="{{ $id }}">
            {{ $label }}
            @if($help)
                <small class="text-muted">{{ $help }}</small>
            @endif
        </label>
    </div>
<span class="text-danger error-text" data-field="{{ $name }}"></span>

    @error($name)
        <small class="error-text text-danger">{{ $message }}</small>
    @enderror
</div>