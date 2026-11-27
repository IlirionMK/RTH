<?php

namespace App\Http\Resources;

use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Trip
 */
class AdminTripResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'starts_on' => $this->starts_on->toDateString(),
            'ends_on' => $this->ends_on?->toDateString(),
            'created_at' => $this->created_at,
            'distance_km' => $this->whenHas('distance_km', fn (mixed $value): float => round((float) $value, 1)),
            /** @var list<string> */
            'countries' => $this->whenLoaded('points', fn (): array => $this->points->pluck('country_code')->filter()->unique()->values()->all()),
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
        ];
    }
}
