<?php

namespace App\Http\Resources;

use App\Support\SystemStatistics;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SystemStatistics
 */
class SystemStatisticsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'users_count' => $this->usersCount,
            'trips_count' => $this->tripsCount,
            'months' => $this->months,
        ];
    }
}
