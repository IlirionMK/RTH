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
}
