<?php

namespace Tests\Feature;

use Tests\TestCase;
use ZipArchive;

final class GeneratedArchitectureBoundaryExportTest extends TestCase
{
    public function test_export_contains_executable_clean_architecture_boundary_test(): void
    {
        $response = $this->postJson('/api/v1/blueprint/export', $this->manifest())
            ->assertOk()
            ->assertHeader('content-type', 'application/zip');

        $temporaryFile = tempnam(sys_get_temp_dir(), 'apiblueprint-architecture-export-');
        $this->assertNotFalse($temporaryFile);
        file_put_contents($temporaryFile, $response->getContent());

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($temporaryFile) === true);

        $architectureTest = $zip->getFromName('architecture-api/tests/Unit/ArchitectureBoundaryTest.php');

        $this->assertIsString($architectureTest);
        $this->assertStringContainsString('final class ArchitectureBoundaryTest extends TestCase', $architectureTest);
        $this->assertStringContainsString("'Domain' => [", $architectureTest);
        $this->assertStringContainsString("'Application' => [", $architectureTest);
        $this->assertStringContainsString("'Infrastructure' => [", $architectureTest);
        $this->assertStringContainsString("'Presentation' => [", $architectureTest);
        $this->assertStringContainsString("'Illuminate\\\\'", $architectureTest);
        $this->assertStringContainsString("'App\\\\Infrastructure\\\\'", $architectureTest);
        $this->assertStringContainsString("'App\\\\Presentation\\\\'", $architectureTest);
        $this->assertStringContainsString('violates Clean Architecture', $architectureTest);

        $zip->close();
        @unlink($temporaryFile);
    }

    private function manifest(): array
    {
        return [
            'schema_version' => '0.3',
            'generator' => 'ApiBlueprint',
            'project' => [
                'name' => 'Architecture API',
                'api_version' => 'v1',
            ],
            'template' => 'custom',
            'governance' => [
                'authentication' => 'none',
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
            'endpoints' => [
                ['id' => 'products.show', 'exposure' => 'public'],
            ],
        ];
    }
}
