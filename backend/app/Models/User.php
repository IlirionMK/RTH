<?php

namespace App\Models;

use App\Enums\Locale;
use App\Enums\Role;
use App\Enums\Theme;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'pending_email', 'password', 'is_public', 'locale', 'theme'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $attributes = [
        'role' => 'user',
        'is_public' => false,
        'locale' => 'pl',
        'theme' => 'light',
    ];

    /**
     * @return HasMany<Trip, $this>
     */
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function hasPublicProfile(): bool
    {
        return $this->is_public && $this->blocked_at === null;
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function withPublicProfile(Builder $query): void
    {
        $query->where('is_public', true)->whereNull('blocked_at');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'blocked_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_public' => 'boolean',
            'locale' => Locale::class,
            'theme' => Theme::class,
        ];
    }
}
