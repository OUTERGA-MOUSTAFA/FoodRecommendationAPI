<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecommendationResource extends JsonResource
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
            'score' => $this->score,
            'label' => $this->label,
            'status' => $this->status,
            'warning_message' => $this->warning_message,
            'conflicting_tags' => $this->conflicting_tags,

            // 🔥 relation plat
            'plat' => [
                'id' => $this->plat?->id,
                'title' => $this->plat?->title,
            ],

            'created_at' => $this->created_at,
        ];
    }
}
