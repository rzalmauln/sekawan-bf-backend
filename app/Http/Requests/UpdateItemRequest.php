<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateItemRequest extends FormRequest
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
            'name' => 'required|string|max:255' . $this->route('item')->id,
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'catalog_id' => 'required|exists:catalogs,id',
            'certificate_path' => 'nullable|file|mimes:pdf,jpg,png|max:512',
        ];
    }
}
