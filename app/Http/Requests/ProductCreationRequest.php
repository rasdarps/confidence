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

            // Multi-Unit Packaging breakdown
            'has_sub_unit' => 'nullable|boolean',
            'sub_unit_id' => 'nullable|required_if:has_sub_unit,1,true|exists:units,id',
            'pieces_per_pack' => 'nullable|required_if:has_sub_unit,1,true|integer|min:2',
        ];
    }

    public function getProductData(): array
    {
        $validated = $this->validated();
        $hasSubUnit = $this->boolean('has_sub_unit');

        return [
            'name' => $validated['name'],
            'category_id' => $validated['category_id'],
            'unit_id' => $validated['unit_id'],
            'has_sub_unit' => $hasSubUnit,
            'sub_unit_id' => $hasSubUnit ? ($validated['sub_unit_id'] ?? null) : null,
            'pieces_per_pack' => $hasSubUnit ? ((int) ($validated['pieces_per_pack'] ?? 1)) : null,
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
            'unit_id.required' => 'Please select a Primary Packaging Unit',
            'unit_id.exists' => 'The selected Unit is invalid',
            'sub_unit_id.required_if' => 'Please select a Piece/Sub-unit',
            'pieces_per_pack.required_if' => 'Please enter the number of pieces per pack',
            'pieces_per_pack.min' => 'Pieces per pack must be at least 2',
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
