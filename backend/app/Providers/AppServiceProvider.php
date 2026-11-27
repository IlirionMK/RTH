<?php

namespace App\Providers;

use App\Models\User;
use App\Support\SignedFrontendUrl;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\Contact;
use Dedoc\Scramble\Support\Generator\License;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Dedoc\Scramble\Support\Generator\Tag;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    private const API_SECTIONS = [
        'Auth' => 'Registration, email confirmation, sign-in and sign-out.',
        'Profile' => 'The signed-in user: name, email address, password, visibility, language and theme.',
        'Trips' => 'Trips of the signed-in user with their points and the segments between them.',
        'Places' => 'Search for places by name to add them as trip points.',
        'Maps and statistics' => 'Routes of all trips on one map and travel statistics of the signed-in user.',
        'Public profiles' => 'Read-only trips, maps and statistics of users with a public profile.',
        'Admin' => 'Accounts, trips and system statistics. Only for administrators.',
    ];

    public function register(): void {}

    public function boot(): void
    {
        Gate::define('viewApiDocs', fn (?User $user): bool => true);
        Gate::define('admin', fn (User $user): bool => $user->isAdmin());

        Route::bind('public_profile', fn (string $value): User => User::query()->withPublicProfile()->findOrFail($value));

        VerifyEmail::createUrlUsing(fn (User $user): string => SignedFrontendUrl::make('email-verifications.store', [
            'user' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]));

        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi): void {
                $openApi->secure(SecurityScheme::http('bearer'));
                $openApi->info
                    ->setDescription((string) file_get_contents(resource_path('docs/api.md')))
                    ->setContact(new Contact('RTH', 'https://github.com/IlirionMK/RTH'))
                    ->setLicense(new License('MIT', 'MIT'));
                $openApi->tags = array_map(
                    fn (string $name): Tag => new Tag($name, self::API_SECTIONS[$name]),
                    array_keys(self::API_SECTIONS),
                );
            })
            ->withOperationTransformers(function (Operation $operation): void {
                $operation->addParameters([
                    Parameter::make('Accept-Language', 'header')
                        ->description('Language of messages and errors: `pl` (default) or `en`.')
                        ->setSchema(Schema::fromType((new StringType)->enum(['pl', 'en']))),
                ]);
            });
    }
}
