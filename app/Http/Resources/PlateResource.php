<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlateResource extends JsonResource
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
            'name' => $this->name,
            'price' => $this->price ?? null,
            'image' => $this->image,
            'is_available' => (bool) $this->is_available,
            'categories' => $this->categories->pluck('name'),
            'ingredients' => $this->ingredients->pluck('name'),

            // Recommandation
            'score' => $this->recommendation->score ?? null,
            'label' => $this->recommendation->label ?? null,
            'warning_message' => $this->recommendation->warning_message ?? null,
            'status' => $this->recommendation->status ?? 'processing',
        ];
    }
}
