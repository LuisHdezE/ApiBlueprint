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
        $this->assertSame('planned', $features['auth.login']['implementation_status']);

        $commerce = collect($response->json('applications'))->firstWhere('id', 'commerce');
        $this->assertSame('partial', $commerce['status']);
        $this->assertSame(['implemented' => 2, 'total' => 9], $commerce['coverage']);
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
            ->assertJsonPath('info.title', 'ApiBlueprint · Master Feature Library')
            ->assertJsonPath('paths./api~1v1~1products.get.x-apiblueprint-feature-id', 'products.list')
            ->assertJsonPath('paths./api~1v1~1products~1{id}.get.x-apiblueprint-feature-id', 'products.show');

        $paths = $response->json('paths');
        $this->assertArrayNotHasKey('/api/v1/auth/login', $paths);
        $this->assertCount(2, $paths);
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
