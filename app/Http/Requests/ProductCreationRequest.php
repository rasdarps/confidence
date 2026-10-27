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
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'unit_id' => 'required|exists:units,id',
 
             // Stock Information
             'stock_level' => 'nullable|integer|min:0',
            
            ];
    }
    public function getProductData(): array
    {
        $validated = $this->validated();
        
        return [
            // Basic Information
            'name' => $validated['name'],
            'category_id' => $validated['category_id'],
            'unit_id' => $validated['unit_id'],
            
            // Stock Information
            'stock_level' => $validated['stock_level'] ?? 0,
            
            // Settings
            'created_by' => auth()->id(),
        ];
    }


    public function messages()
    {
        return  [
          'name.required' => 'Please enter a Product name',
            'name.unique' => 'This product already exists',
            'barcode.unique'       => 'This barcode is already registered',
            'barcode.string'       => 'Barcode must be a string',
            'barcode.max'          => 'Barcode must not exceed 50 characters',
            'unit_id.required' => 'Please select a unit',
            'unit_id.exists' => 'Selected unit is invalid',
            'category_id.required' => 'Please select a category',
            'category_id.exists' => 'Selected category is invalid',
            'stock_level.required' => 'Please enter stock level',
            'stock_level.numeric' => 'Stock level must be a number',
            'stock_level.min' => 'Stock level cannot be negative',
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
