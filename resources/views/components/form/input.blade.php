<div class="form-group">
    @if($label)
        <label for="{{ $id }}">
            {{ $label }}
            @if($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif
    
    <input 
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $id }}"
        class="form-control @error($name) is-invalid @enderror"
        value="{{ old($name, $value) }}"
        placeholder="{{ $placeholder }}"
        {{-- {{ $required ? 'required' : '' }} --}}
        {{ $attributes }}
    >
    
    @if($help)
        <small class="text-muted">{{ $help }}</small>
    @endif
    
    @error($name)
        <small class="error-text text-danger">{{ $message }}</small>
    @enderror

    <span class="text-danger error-text" data-field="{{ $name }}"></span>

</div>