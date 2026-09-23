<?php

namespace Tests\Feature;

use Tests\TestCase;
use ZipArchive;

final class GeneratedSemanticAuditExportTest extends TestCase
{
    public function test_audit_enabled_export_contains_semantic_append_only_contract(): void
    {
        $response = $this->postJson('/api/v1/blueprint/export', $this->manifest(true))
            ->assertOk()
            ->assertHeader('content-type', 'application/zip');

        $temporaryFile = tempnam(sys_get_temp_dir(), 'apiblueprint-semantic-audit-');
        $this->assertNotFalse($temporaryFile);
        file_put_contents($temporaryFile, $response->getContent());

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($temporaryFile) === true);

        $prefix = 'operational-audit-api/';
        $config = $zip->getFromName($prefix.'config/apiblueprint_audit.php');
        $middleware = $zip->getFromName($prefix.'app/Presentation/Http/Middleware/AuditRequestMiddleware.php');
        $sink = $zip->getFromName($prefix.'app/Infrastructure/Audit/LogAuditTrail.php');
        $test = $zip->getFromName($prefix.'tests/Feature/AuditSemanticContractTest.php');

        $this->assertIsString($config);
        $this->assertStringContainsString("'api.v1.products.show' => 'audit.products.show'", $config);

        $this->assertIsString($middleware);
        $this->assertStringContainsString("'event_code'", $middleware);
        $this->assertStringContainsString("'outcome'", $middleware);
        $this->assertStringContainsString("'correlation_id'", $middleware);
        $this->assertStringContainsString("'actor_id'", $middleware);
        $this->assertStringNotContainsString('$request->all()', $middleware);
        $this->assertStringNotContainsString('headers->all()', $middleware);

        $this->assertIsString($sink);
        $this->assertStringContainsString("storage_path('logs/audit.jsonl')", $sink);
        $this->assertStringContainsString('FILE_APPEND | LOCK_EX', $sink);
        $this->assertStringContainsString("'[REDACTED]'", $sink);
        $this->assertStringNotContainsString('LoggerInterface', $sink);

        $this->assertIsString($test);
        $this->assertStringContainsString('final class AuditSemanticContractTest extends TestCase', $test);
        $this->assertStringContainsString('semantic-audit-password-should-not-leak', $test);
        $this->assertStringContainsString('sink-token-should-not-leak', $test);

        $zip->close();
        @unlink($temporaryFile);
    }

    public function test_audit_disabled_export_contains_no_semantic_audit_infrastructure(): void
    {
        $response = $this->postJson('/api/v1/blueprint/export', $this->manifest(false))
            ->assertOk();

        $temporaryFile = tempnam(sys_get_temp_dir(), 'apiblueprint-no-audit-');
        $this->assertNotFalse($temporaryFile);
        file_put_contents($temporaryFile, $response->getContent());

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($temporaryFile) === true);

        $prefix = 'operational-audit-api/';
        $this->assertFalse($zip->locateName($prefix.'config/apiblueprint_audit.php'));
        $this->assertFalse($zip->locateName($prefix.'app/Application/Shared/Contracts/AuditTrail.php'));
        $this->assertFalse($zip->locateName($prefix.'app/Infrastructure/Audit/LogAuditTrail.php'));
        $this->assertFalse($zip->locateName($prefix.'app/Presentation/Http/Middleware/AuditRequestMiddleware.php'));
        $this->assertFalse($zip->locateName($prefix.'tests/Feature/AuditSemanticContractTest.php'));

        $zip->close();
        @unlink($temporaryFile);
    }

    private function manifest(bool $audit): array
    {
        return [
            'schema_version' => '0.3',
            'generator' => 'ApiBlueprint',
            'project' => [
                'name' => 'Operational Audit API',
                'api_version' => 'v1',
            ],
            'template' => 'custom',
            'governance' => [
                'authentication' => 'none',
                'rbac' => false,
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
                'audit' => $audit,
            ],
            'endpoints' => [
                ['id' => 'products.show', 'exposure' => 'public'],
            ],
        ];
    }
}
