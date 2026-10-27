<div class="form-group">
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
            {{ $label }}
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