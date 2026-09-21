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
            ->assertJsonPath('message', 'ApiBlueprint está operativo.')
            ->assertJsonPath('blueprint_schema', '0.3');
    }

    public function test_catalog_exposes_templates_endpoint_contracts_and_governance_in_spanish(): void
    {
        $response = $this->getJson('/api/v1/blueprint/catalog')
            ->assertOk()
            ->assertJsonPath('schema_version', '0.3')
            ->assertJsonPath('api_version', 'v1')
            ->assertJsonPath('exposures.0.label', 'Público')
            ->assertJsonPath('templates.0.name', 'API en blanco')
            ->assertJsonPath('governance.authentication_strategies.1.label', 'Laravel Sanctum')
            ->assertJsonPath('governance.defaults.pagination.strategy', 'cursor');

        $response->assertJsonCount(4, 'templates');
        $this->assertGreaterThan(10, count($response->json('endpoints')));
    }
}
