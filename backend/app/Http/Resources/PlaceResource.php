<?php

namespace App\Http\Resources;

use App\Support\Place;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Place
 */
class PlaceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'label' => $this->label,
            'country_code' => $this->countryCode,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
