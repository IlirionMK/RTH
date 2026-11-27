<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiDocumentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_to_api_documentation(): void
    {
        $this->get('/')->assertRedirect('/docs/api');
    }

    public function test_api_documentation_page_is_public(): void
    {
        $this->get('/docs/api')->assertOk();
    }

    public function test_openapi_specification_is_public(): void
    {
        $this->getJson('/docs/api.json')
            ->assertOk()
            ->assertJsonPath('info.title', 'RTH');
    }

    public function test_account_endpoints_are_documented(): void
    {
        $this->getJson('/docs/api.json')->assertJsonStructure([
            'paths' => [
                '/users' => ['post'],
                '/tokens' => ['post'],
                '/tokens/current' => ['delete'],
                '/profile' => ['get', 'patch'],
                '/profile/password' => ['put'],
            ],
        ]);
    }

    public function test_trip_endpoints_are_documented(): void
    {
        $this->getJson('/docs/api.json')
            ->assertJsonStructure([
                'paths' => [
                    '/trips' => ['get', 'post' => ['responses' => ['201']]],
                    '/trips/{trip}' => ['get', 'patch', 'delete'],
                    '/trips/{trip}/points' => ['post' => ['responses' => ['201']]],
                    '/points/{point}' => ['get', 'patch', 'delete'],
                    '/segments/{segment}' => ['patch'],
                    '/places' => ['get'],
                    '/map' => ['get'],
                    '/statistics' => ['get'],
                    '/public-profiles' => ['get'],
                    '/public-profiles/{user}' => ['get'],
                    '/public-profiles/{user}/trips' => ['get'],
                    '/public-profiles/{user}/map' => ['get'],
                    '/public-profiles/{user}/statistics' => ['get'],
                    '/admin/users' => ['get'],
                    '/admin/users/{user}' => ['patch'],
                    '/admin/trips' => ['get'],
                    '/admin/statistics' => ['get'],
                ],
            ])
            ->assertJsonPath('components.schemas.Transport.enum', ['car', 'plane', 'train', 'other']);
    }

    public function test_every_endpoint_has_title_description_and_section(): void
    {
        $document = $this->getJson('/docs/api.json')->json();
        $sections = array_column($document['tags'], 'description', 'name');

        foreach ($document['paths'] as $path => $operations) {
            foreach ($operations as $method => $operation) {
                $endpoint = strtoupper($method).' '.$path;
                $this->assertNotEmpty($operation['summary'] ?? null, "{$endpoint} has no title");
                $this->assertNotEmpty($operation['description'] ?? null, "{$endpoint} has no description");
                $this->assertCount(1, $operation['tags'] ?? [], "{$endpoint} has no section");
                $this->assertNotEmpty($sections[$operation['tags'][0]] ?? null, "{$endpoint} section is not described");
                $this->assertContains('Accept-Language', array_column($operation['parameters'] ?? [], 'name'), "{$endpoint} has no language header");

                foreach ($operation['parameters'] ?? [] as $parameter) {
                    $this->assertNotEmpty($parameter['description'] ?? null, "{$endpoint} parameter {$parameter['name']} has no description");
                }
            }
        }
    }

    public function test_documentation_explains_usage_license_and_pages(): void
    {
        $response = $this->getJson('/docs/api.json')
            ->assertJsonPath('info.license', ['name' => 'MIT', 'identifier' => 'MIT'])
            ->assertJsonPath('info.contact.url', 'https://github.com/IlirionMK/RTH')
            ->assertSee('Authorization: Bearer')
            ->assertSee('Accept-Language: pl');

        foreach (['/trips', '/public-profiles', '/public-profiles/{user}/trips', '/admin/users', '/admin/trips'] as $list) {
            $this->assertContains('page', $response->json("paths.{$list}.get.parameters.*.name"), "{$list} has no page parameter");
        }
    }

    public function test_language_and_theme_are_documented_as_enums(): void
    {
        $this->getJson('/docs/api.json')
            ->assertJsonPath('components.schemas.Locale.enum', ['pl', 'en'])
            ->assertJsonPath('components.schemas.Theme.enum', ['light', 'dark'])
            ->assertJsonPath('components.schemas.Role.enum', ['user', 'admin'])
            ->assertJsonPath('components.schemas.UserResource.properties.locale.$ref', '#/components/schemas/Locale')
            ->assertJsonPath('components.schemas.UpdateProfileRequest.properties.theme.$ref', '#/components/schemas/Theme');
    }

    public function test_only_registration_and_login_work_without_token_in_documentation(): void
    {
        $this->getJson('/docs/api.json')
            ->assertJsonPath('components.securitySchemes.http.scheme', 'bearer')
            ->assertJsonPath('security.0.http', [])
            ->assertJsonPath('paths./users.post.security', [])
            ->assertJsonPath('paths./tokens.post.security', [])
            ->assertJsonMissingPath('paths./profile.get.security');
    }
}
