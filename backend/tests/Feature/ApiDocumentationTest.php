<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiDocumentationTest extends TestCase
{
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
