<?php

namespace App\Support;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class SignedFrontendUrl
{
    /**
     * @param  array<string, mixed>  $parameters
     */
    public static function make(string $route, array $parameters): string
    {
        $path = URL::temporarySignedRoute(
            $route,
            now()->addMinutes(config()->integer('auth.verification.expire', 60)),
            $parameters,
            absolute: false,
        );

        return config()->string('app.frontend_url').Str::after($path, '/api');
    }
}
