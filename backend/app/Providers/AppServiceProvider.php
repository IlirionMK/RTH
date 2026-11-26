<?php

namespace App\Providers;

use App\Models\User;
use App\Support\SignedFrontendUrl;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::define('viewApiDocs', fn (?User $user): bool => true);

        VerifyEmail::createUrlUsing(fn (User $user): string => SignedFrontendUrl::make('email-verifications.store', [
            'user' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]));

        Scramble::configure()->withDocumentTransformers(function (OpenApi $openApi): void {
            $openApi->secure(SecurityScheme::http('bearer'));
        });
    }
}
