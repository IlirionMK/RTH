<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class AdminUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'is_public' => $this->is_public,
            'is_blocked' => $this->blocked_at !== null,
            'blocked_at' => $this->blocked_at,
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'trips_count' => $this->whenCounted('trips'),
        ];
    }
}
