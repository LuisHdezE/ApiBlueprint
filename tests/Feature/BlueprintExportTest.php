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
            ->assertJsonPath('governance.authentication', 'sanctum')
            ->assertJsonPath('resolution.auto_added.0.id', 'auth.login');

        $this->assertSame('public', $response->json('endpoints.0.exposure'));
    }

    public function test_protected_surface_rejects_missing_authentication_strategy(): void
    {
        $manifest = $this->manifest([
            ['id' => 'customers.list', 'exposure' => 'authenticated'],
        ]);
        $manifest['governance']['authentication'] = 'none';

        $this->postJson('/api/v1/blueprint/resolve', $manifest)
            ->assertStatus(422)
            ->assertJsonPath('title', 'Manifest de ApiBlueprint no válido')
            ->assertJsonPath('errors.governance.authentication.0', 'La superficie seleccionada contiene endpoints protegidos y requiere una estrategia de autenticación.');
    }

    public function test_privileged_surface_requires_rbac(): void
    {
        $manifest = $this->manifest([
            ['id' => 'users.list', 'exposure' => 'admin'],
        ]);
        $manifest['governance']['rbac'] = false;

        $this->postJson('/api/v1/blueprint/resolve', $manifest)
            ->assertStatus(422)
            ->assertJsonPath('errors.governance.rbac.0', 'Los endpoints Administrador o Interno requieren RBAC habilitado.');
    }

    public function test_audit_endpoint_enables_audit_capability_explicitly(): void
    {
        $manifest = $this->manifest([
            ['id' => 'audit.list', 'exposure' => 'internal'],
        ]);
        $manifest['governance']['audit'] = false;

        $this->postJson('/api/v1/blueprint/resolve', $manifest)
            ->assertOk()
            ->assertJsonPath('governance.audit', true)
            ->assertJsonPath('resolution.governance_adjustments.0.capability', 'audit');
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

    public function test_export_contains_governed_runtime_and_only_resolved_endpoint_surface(): void
    {
        $manifest = $this->manifest([
            ['id' => 'products.list', 'exposure' => 'public'],
        ]);
        $manifest['governance']['authentication'] = 'none';

        $response = $this->postJson('/api/v1/blueprint/export', $manifest);

        $response->assertOk()->assertHeader('content-type', 'application/zip');
        $this->assertStringStartsWith('PK', (string) $response->getContent());

        $temporaryFile = tempnam(sys_get_temp_dir(), 'apiblueprint-test-');
        $this->assertNotFalse($temporaryFile);
        file_put_contents($temporaryFile, $response->getContent());

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($temporaryFile) === true);

        $routes = $zip->getFromName('api-prueba/routes/api.php');
        $resolvedManifest = $zip->getFromName('api-prueba/.apiblueprint.json');
        $openApi = $zip->getFromName('api-prueba/openapi/openapi.yaml');
        $composer = $zip->getFromName('api-prueba/composer.json');
        $correlation = $zip->getFromName('api-prueba/app/Presentation/Http/Middleware/CorrelationIdMiddleware.php');
        $queryParser = $zip->getFromName('api-prueba/app/Presentation/Http/Support/QueryOptionsParser.php');
        $audit = $zip->getFromName('api-prueba/app/Presentation/Http/Middleware/AuditRequestMiddleware.php');

        $this->assertIsString($routes);
        $this->assertStringContainsString('/v1/products', $routes);
        $this->assertStringContainsString('throttle:api', $routes);
        $this->assertStringContainsString('audit.request', $routes);
        $this->assertStringNotContainsString('/v1/customers', $routes);
        $this->assertIsString($resolvedManifest);
        $this->assertStringContainsString('products.list', $resolvedManifest);
        $this->assertStringContainsString('"authentication": "none"', $resolvedManifest);
        $this->assertIsString($openApi);
        $this->assertStringContainsString('Listar productos', $openApi);
        $this->assertStringContainsString('ProblemDetails', $openApi);
        $this->assertStringContainsString('page[size]', $openApi);
        $this->assertIsString($composer);
        $this->assertStringNotContainsString('laravel/sanctum', $composer);
        $this->assertIsString($correlation);
        $this->assertIsString($queryParser);
        $this->assertIsString($audit);

        $zip->close();
        @unlink($temporaryFile);
    }

    public function test_sanctum_and_rbac_are_exported_for_admin_endpoint(): void
    {
        $response = $this->postJson('/api/v1/blueprint/export', $this->manifest([
            ['id' => 'users.list', 'exposure' => 'admin'],
        ]));

        $temporaryFile = tempnam(sys_get_temp_dir(), 'apiblueprint-test-');
        $this->assertNotFalse($temporaryFile);
        file_put_contents($temporaryFile, $response->getContent());

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($temporaryFile) === true);
        $routes = $zip->getFromName('api-prueba/routes/api.php');
        $composer = $zip->getFromName('api-prueba/composer.json');
        $provider = $zip->getFromName('api-prueba/app/Providers/AppServiceProvider.php');

        $this->assertIsString($routes);
        $this->assertStringContainsString('auth:sanctum', $routes);
        $this->assertStringContainsString('can:admin-api', $routes);
        $this->assertIsString($composer);
        $this->assertStringContainsString('laravel/sanctum', $composer);
        $this->assertIsString($provider);
        $this->assertStringContainsString("Gate::define('admin-api'", $provider);

        $zip->close();
        @unlink($temporaryFile);
    }

    private function manifest(array $endpoints): array
    {
        return [
            'schema_version' => '0.3',
            'generator' => 'ApiBlueprint',
            'project' => [
                'name' => 'API Prueba',
                'api_version' => 'v1',
            ],
            'template' => 'custom',
            'governance' => [
                'authentication' => 'sanctum',
                'rbac' => true,
                'correlation_id' => true,
                'rate_limiting' => [
                    'enabled' => true,
                    'requests_per_minute' => 60,
                ],
                'pagination' => [
                    'strategy' => 'cursor',
                    'default_size' => 25,
                    'max_size' => 100,
                ],
                'filtering' => true,
                'sorting' => true,
                'idempotency' => true,
                'audit' => true,
            ],
            'endpoints' => $endpoints,
        ];
    }
}
