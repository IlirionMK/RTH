<?php

namespace App\Http\Resources;

use App\Models\Point;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Point
 */
class PointResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'trip_id' => $this->trip_id,
            'name' => $this->name,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'country_code' => $this->country_code,
            'position' => $this->position,
        ];
    }
}
