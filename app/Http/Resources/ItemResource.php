<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'catalog_id' => $this->catalog_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'stock' => $this->stock,
            'type' => $this->type,
            'image_url' => $this->image_path ? asset('storage/' . $this->image_path) : null,
            'video_url' => $this->video_path ? asset('storage/' . $this->video_path) : null,
            'certificate_url' => $this->certificate_path ? asset('storage/' . $this->certificate_path) : null,
            'gaya_main' => $this->gaya_main,
            'body' => $this->body,
            'umur' => $this->umur,
            'materi' => $this->materi,
            'volume' => $this->volume,
            'panjang_ekor' => $this->panjang_ekor,
            'warna' => $this->warna,
            'warna_kaki' => $this->warna_kaki,
            'paruh' => $this->paruh,
            'jenis_kepala' => $this->jenis_kepala,
            'voer' => $this->voer,
            'extra_fooding' => $this->extra_fooding,
            'embun' => $this->embun,
            'jemur' => $this->jemur,
            'mandi' => $this->mandi,
            'tenggar' => $this->tenggar,
            'krodong_ablak' => $this->krodong_ablak,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
