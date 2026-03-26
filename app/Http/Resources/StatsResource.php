<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StatsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'totals' => [
                'plates' => $this['total_plates'],
                'categories' => $this['total_categories'],
                'ingredients' => $this['total_ingredients'],
                'recommendations' => $this['total_recommendations'],
            ],

            'most_recommended_plate' => $this->formatPlate($this['most_recommended_plate']),
            'least_recommended_plate' => $this->formatPlate($this['least_recommended_plate']),

            'top_category' => $this['category_with_most_plates']
                ? [
                    'id' => $this['category_with_most_plates']->id,
                    'name' => $this['category_with_most_plates']->name,
                    'plates_count' => $this['category_with_most_plates']->plats_count,
                ]
                : null,
        ];
    }

    private function formatPlate($data)
    {
        if (!$data) return null;

        return [
            'id' => $data->plat?->id,
            'title' => $data->plat?->title,
            'avg_score' => round($data->avg_score, 2),
        ];
    }
}