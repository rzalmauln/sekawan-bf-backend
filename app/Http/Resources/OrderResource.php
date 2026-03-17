<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'invoice_number' => $this->invoice_number,
            'status' => $this->status,
            'total_price' => $this->total_price,
            'payment_proof_path' => $this->payment_proof_path,
            'payment_proof_url' => $this->payment_proof_path ? asset('storage/' . $this->payment_proof_path) : null,
            'tracking_number' => $this->tracking_number,
            'paid_at' => $this->paid_at,
            'shipped_at' => $this->shipped_at,
            'completed_at' => $this->completed_at,
            'cancelled_at' => $this->cancelled_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'customer' => $this->whenLoaded('customer', function () {
                return [
                    'id' => $this->customer?->id,
                    'name' => $this->customer?->name,
                    'email' => $this->customer?->email,
                    'phone' => $this->customer?->phone,
                    'address' => $this->customer?->address,
                    'city' => $this->customer?->city,
                    'province' => $this->customer?->province,
                    'postal_code' => $this->customer?->postal_code,
                ];
            }),
            'items' => $this->whenLoaded('orderItems', function () {
                return $this->orderItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'item_id' => $item->item_id,
                        'item_name' => $item->item_name,
                        'unit_price' => $item->unit_price,
                        'qty' => $item->qty,
                        'subtotal' => $item->subtotal,
                    ];
                });
            }),
        ];
    }
}
