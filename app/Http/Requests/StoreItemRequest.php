<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreItemRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'type' => 'nullable|string|max:255',
            'catalog_id' => 'required|exists:catalogs,id',
            'certificate_path' => 'nullable|file|mimes:pdf,jpg,png|max:512',
            'certificate_password' => 'nullable|string|max:255',
            'image_path' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'video_path' => 'nullable|file|mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/webm|max:10240',
            'gaya_main' => 'nullable|string|max:255',
            'body' => 'nullable|string|max:255',
            'umur' => 'nullable|string|max:255',
            'materi' => 'nullable|string|max:255',
            'volume' => 'nullable|string|max:255',
            'panjang_ekor' => 'nullable|string|max:255',
            'warna' => 'nullable|string|max:255',
            'warna_kaki' => 'nullable|string|max:255',
            'paruh' => 'nullable|string|max:255',
            'jenis_kepala' => 'nullable|string|max:255',
            'voer' => 'nullable|string|max:255',
            'extra_fooding' => 'nullable|string|max:255',
            'embun' => 'nullable|string|max:255',
            'jemur' => 'nullable|string|max:255',
            'mandi' => 'nullable|string|max:255',
            'tenggar' => 'nullable|string|max:255',
            'krodong_ablak' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ];
    }
}
