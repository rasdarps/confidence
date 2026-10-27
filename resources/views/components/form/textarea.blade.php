<div class="form-group">
    @if($label)
        <label for="{{ $id }}">
            {{ $label }}
            @if($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif
    
    <textarea 
        name="{{ $name }}"
        id="{{ $id }}"
        rows="{{ $rows }}"
        class="form-control @if($isEditor) editor @endif @error($name) is-invalid @enderror"
        placeholder="{{ $placeholder }}"
        {{-- {{ $required ? 'required' : '' }} --}}
        {{ $attributes }}
    >{{ old($name, $value) }}</textarea>
    
    @if($help)
        <small class="text-muted">{{ $help }}</small>
    @endif
    
    @error($name)
        <small class="error-text text-danger">{{ $message }}</small>
    @enderror
    <span class="text-danger error-text" data-field="{{ $name }}"></span>

</div>