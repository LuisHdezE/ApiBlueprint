<?php

namespace Tests\Feature;

use Tests\TestCase;

final class MasterCatalogTest extends TestCase
{
    public function test_catalog_exposes_canonical_applications_and_features_without_breaking_legacy_projection(): void
    {
        $response = $this->getJson('/api/v1/blueprint/catalog')
            ->assertOk()
            ->assertJsonPath('catalog_version', '0.1')
            ->assertJsonCount(4, 'applications')
            ->assertJsonCount(4, 'templates');

        $features = collect($response->json('features'))->keyBy('id');

        $this->assertSame('implemented', $features['products.list']['implementation_status']);
        $this->assertTrue($features['products.list']['exportable']);
        $this->assertTrue($features['products.list']['openapi_ready']);
        $this->assertTrue($features['products.list']['tests_ready']);
        $this->assertSame('implemented', $features['products.show']['implementation_status']);
        $this->assertSame('implemented', $features['auth.login']['implementation_status']);
        $this->assertTrue($features['auth.login']['exportable']);
        $this->assertTrue($features['auth.login']['openapi_ready']);
        $this->assertTrue($features['auth.login']['tests_ready']);
        $this->assertSame('implemented', $features['auth.logout']['implementation_status']);
        $this->assertTrue($features['auth.logout']['exportable']);
        $this->assertTrue($features['auth.logout']['openapi_ready']);
        $this->assertTrue($features['auth.logout']['tests_ready']);
        $this->assertSame('implemented', $features['users.list']['implementation_status']);
        $this->assertTrue($features['users.list']['exportable']);
        $this->assertTrue($features['users.list']['openapi_ready']);
        $this->assertTrue($features['users.list']['tests_ready']);
        $this->assertSame('implemented', $features['users.show']['implementation_status']);
        $this->assertTrue($features['users.show']['exportable']);
        $this->assertTrue($features['users.show']['openapi_ready']);
        $this->assertTrue($features['users.show']['tests_ready']);

        $saas = collect($response->json('applications'))->firstWhere('id', 'saas');
        $this->assertSame('partial', $saas['status']);
        $this->assertSame(['implemented' => 4, 'total' => 8], $saas['coverage']);

        $commerce = collect($response->json('applications'))->firstWhere('id', 'commerce');
        $this->assertSame('partial', $commerce['status']);
        $this->assertSame(['implemented' => 3, 'total' => 9], $commerce['coverage']);
    }

    public function test_every_application_reuses_unique_canonical_feature_ids(): void
    {
        $catalog = $this->getJson('/api/v1/blueprint/catalog')->assertOk()->json();
        $features = $catalog['features'];
        $featureIds = array_column($features, 'id');
        $signatures = array_map(
            static fn (array $feature): string => $feature['method'].' '.$feature['path'],
            $features,
        );

        $this->assertSameSize(array_unique($featureIds), $featureIds, 'La Feature Library no puede repetir IDs.');
        $this->assertSameSize(array_unique($signatures), $signatures, 'La Feature Library no puede repetir el mismo método y ruta.');

        foreach ($catalog['applications'] as $application) {
            $this->assertSameSize(
                array_unique($application['features']),
                $application['features'],
                "La aplicación {$application['id']} no puede repetir una feature.",
            );

            foreach ($application['features'] as $featureId) {
                $this->assertContains(
                    $featureId,
                    $featureIds,
                    "La aplicación {$application['id']} referencia una feature inexistente: {$featureId}.",
                );
            }
        }
    }

    public function test_master_openapi_only_exposes_implemented_openapi_ready_features(): void
    {
        $response = $this->getJson('/api/v1/blueprint/openapi')
            ->assertOk()
            ->assertJsonPath('openapi', '3.1.0')
            ->assertJsonPath('info.title', 'ApiBlueprint · Master Feature Library');

        $paths = $response->json('paths');

        $this->assertSame('products.list', $paths['/api/v1/products']['get']['x-apiblueprint-feature-id']);
        $this->assertSame('products.show', $paths['/api/v1/products/{id}']['get']['x-apiblueprint-feature-id']);
        $this->assertSame('auth.login', $paths['/api/v1/auth/login']['post']['x-apiblueprint-feature-id']);
        $this->assertSame('#/components/schemas/AuthLoginRequest', $paths['/api/v1/auth/login']['post']['requestBody']['content']['application/json']['schema']['$ref']);
        $this->assertSame('auth.logout', $paths['/api/v1/auth/logout']['post']['x-apiblueprint-feature-id']);
        $this->assertSame([['bearerAuth' => []]], $paths['/api/v1/auth/logout']['post']['security']);
        $this->assertArrayHasKey('204', $paths['/api/v1/auth/logout']['post']['responses']);
        $this->assertSame('users.list', $paths['/api/v1/users']['get']['x-apiblueprint-feature-id']);
        $this->assertSame([['bearerAuth' => []]], $paths['/api/v1/users']['get']['security']);
        $this->assertArrayHasKey('403', $paths['/api/v1/users']['get']['responses']);
        $this->assertSame('#/components/schemas/UserData', $paths['/api/v1/users']['get']['responses']['200']['content']['application/json']['schema']['properties']['data']['items']['$ref']);
        $this->assertSame('users.show', $paths['/api/v1/users/{id}']['get']['x-apiblueprint-feature-id']);
        $this->assertSame([['bearerAuth' => []]], $paths['/api/v1/users/{id}']['get']['security']);
        $this->assertSame('#/components/schemas/UserData', $paths['/api/v1/users/{id}']['get']['responses']['200']['content']['application/json']['schema']['properties']['data']['$ref']);
        $this->assertArrayHasKey('403', $paths['/api/v1/users/{id}']['get']['responses']);
        $this->assertArrayHasKey('404', $paths['/api/v1/users/{id}']['get']['responses']);
        $this->assertCount(6, $paths);
    }

    public function test_catalog_administration_and_swagger_views_are_available(): void
    {
        $this->get('/catalogo')
            ->assertOk()
            ->assertSee('Administración de la biblioteca')
            ->assertSee('Master Feature Library');

        $this->get('/swagger')
            ->assertOk()
            ->assertSee('ApiBlueprint · Swagger vivo')
            ->assertSee('/api/v1/blueprint/openapi', false);
    }
}
