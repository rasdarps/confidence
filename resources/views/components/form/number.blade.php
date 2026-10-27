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
        type="number"
        name="{{ $name }}"
        id="{{ $id }}"
        class="form-control @error($name) is-invalid @enderror"
        value="{{ old($name, $value) }}"
        step="{{ $step }}"
        @if($min !== null) min="{{ $min }}" @endif
        @if($max !== null) max="{{ $max }}" @endif
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