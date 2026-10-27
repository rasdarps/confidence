<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProductCreationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Basic Information
            'name' => 'required|string|max:255|unique:products,name',
            'category_id' => 'required|exists:categories,id',
            'unit_id' => 'required|exists:units,id',
            'stock_level' => 'nullable|numeric|min:0',
        ];
    }

    public function getProductData(): array
    {
        $validated = $this->validated();
        
        return [
            'name' => $validated['name'],
            'category_id' => $validated['category_id'],
            'unit_id' => $validated['unit_id'],
            'created_by' => auth()->id(),
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Please enter a Product name',
            'name.unique' => 'This product already exists',
            'category_id.required' => 'Please select a Category',
            'category_id.exists' => 'The selected Category is invalid',
            'unit_id.required' => 'Please select a Unit',
            'unit_id.exists' => 'The selected Unit is invalid',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 422));
    }
}
