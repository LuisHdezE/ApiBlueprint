<?php

namespace Tests\Feature;

use Tests\TestCase;

final class BlueprintCatalogTest extends TestCase
{
    public function test_status_endpoint_is_available(): void
    {
        $this->getJson('/api/v1/meta/status')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('blueprint_schema', '0.1');
    }

    public function test_catalog_exposes_templates_and_endpoint_contracts(): void
    {
        $response = $this->getJson('/api/v1/blueprint/catalog')
            ->assertOk()
            ->assertJsonPath('schema_version', '0.1')
            ->assertJsonPath('api_version', 'v1');

        $response->assertJsonCount(4, 'templates');
        $this->assertGreaterThan(10, count($response->json('endpoints')));
    }
}
