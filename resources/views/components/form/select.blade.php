<div class="form-group">
    @if($label)
        <label for="{{ $id }}">
            {{ $label }}
            @if($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif
    
    <select 
        name="{{ $name }}"
        id="{{ $id }}"
        class="form-control @error($name) is-invalid @enderror select2"
        {{-- {{ $required ? 'required' : '' }} --}}
        {{ $attributes }}
    >
        <option value="">{{ $placeholder }}</option>
        @foreach($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" {{ old($name, $value) == $optionValue ? 'selected' : '' }}>
                {{ $optionLabel }}
            </option>
        @endforeach
    </select>
    
    @if($help)
        <small class="text-muted">{{ $help }}</small>
    @endif
    
    @error($name)
        <small class="error-text text-danger">{{ $message }}</small>
    @enderror

    <span class="text-danger error-text" data-field="{{ $name }}"></span>

</div>