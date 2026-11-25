<?php

namespace App\Http\Middleware;

use App\Enums\Locale;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locales = array_column(Locale::cases(), 'value');

        app()->setLocale($request->getPreferredLanguage($locales) ?? Locale::Polish->value);

        return $next($request);
    }
}
