<?php

namespace Tests\Feature;

use Tests\TestCase;
use ZipArchive;

final class BlueprintExportTest extends TestCase
{
    public function test_resolver_canonicalizes_endpoint_and_adds_required_authentication(): void
    {
        $response = $this->postJson('/api/v1/blueprint/resolve', $this->manifest([
            ['id' => 'customers.list', 'exposure' => 'authenticated', 'method' => 'DELETE', 'path' => '/tampered'],
        ]))
            ->assertOk()
            ->assertJsonPath('endpoints.0.id', 'auth.login')
            ->assertJsonPath('endpoints.1.id', 'customers.list')
            ->assertJsonPath('endpoints.1.method', 'GET')
            ->assertJsonPath('endpoints.1.path', '/api/v1/customers')
            ->assertJsonPath('resolution.auto_added.0.id', 'auth.login');

        $this->assertSame('public', $response->json('endpoints.0.exposure'));
    }

    public function test_invalid_endpoint_returns_problem_details_in_spanish(): void
    {
        $this->postJson('/api/v1/blueprint/resolve', $this->manifest([
            ['id' => 'unknown.endpoint', 'exposure' => 'public'],
        ]))
            ->assertStatus(422)
            ->assertHeader('content-type', 'application/problem+json')
            ->assertJsonPath('title', 'Manifest de ApiBlueprint no válido')
            ->assertJsonPath('status', 422);
    }

    public function test_export_contains_only_resolved_endpoint_surface(): void
    {
        $response = $this->postJson('/api/v1/blueprint/export', $this->manifest([
            ['id' => 'products.list', 'exposure' => 'public'],
        ]));

        $response->assertOk()->assertHeader('content-type', 'application/zip');
        $this->assertStringStartsWith('PK', (string) $response->getContent());

        $temporaryFile = tempnam(sys_get_temp_dir(), 'apiblueprint-test-');
        $this->assertNotFalse($temporaryFile);
        file_put_contents($temporaryFile, $response->getContent());

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($temporaryFile) === true);

        $routes = $zip->getFromName('api-prueba/routes/api.php');
        $manifest = $zip->getFromName('api-prueba/.apiblueprint.json');
        $openApi = $zip->getFromName('api-prueba/openapi/openapi.yaml');

        $this->assertIsString($routes);
        $this->assertStringContainsString("/v1/products", $routes);
        $this->assertStringNotContainsString('/v1/customers', $routes);
        $this->assertIsString($manifest);
        $this->assertStringContainsString('products.list', $manifest);
        $this->assertIsString($openApi);
        $this->assertStringContainsString('Listar productos', $openApi);

        $zip->close();
        @unlink($temporaryFile);
    }

    /**
     * @param list<array<string, string>> $endpoints
     * @return array<string, mixed>
     */
    private function manifest(array $endpoints): array
    {
        return [
            'schema_version' => '0.2',
            'generator' => 'ApiBlueprint',
            'project' => [
                'name' => 'API Prueba',
                'api_version' => 'v1',
            ],
            'template' => 'custom',
            'endpoints' => $endpoints,
        ];
    }
}
