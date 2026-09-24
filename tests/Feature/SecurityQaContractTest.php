<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

final class SecurityQaContractTest extends TestCase
{
    public function test_unexpected_root_api_exception_does_not_leak_exception_details(): void
    {
        Route::get('/api/v1/__security-500', static function (): never {
            throw new RuntimeException('sensitive-stack-marker');
        });

        $response = $this->getJson('/api/v1/__security-500');

        $response
            ->assertStatus(500)
            ->assertHeader('content-type', 'application/problem+json')
            ->assertJsonPath('title', 'Error interno del servidor')
            ->assertJsonPath('detail', 'Ocurrió un error inesperado al procesar la solicitud.');

        $this->assertStringNotContainsString('sensitive-stack-marker', (string) $response->getContent());
        $this->assertStringNotContainsString('RuntimeException', (string) $response->getContent());
    }

    public function test_hostile_project_name_cannot_escape_export_filename_or_archive_root(): void
    {
        $manifest = json_decode(
            (string) file_get_contents(base_path('deployment/security-qa-manifest.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $manifest['project']['name'] = "../../evil\r\nX-Evil: injected";

        $response = $this->postJson('/api/v1/blueprint/export', $manifest);

        $response
            ->assertOk()
            ->assertHeader('content-type', 'application/zip')
            ->assertHeader('content-disposition', 'attachment; filename="evil-x-evil-injected.zip"');

        $this->assertFalse($response->headers->has('X-Evil'));

        $temporary = tempnam(sys_get_temp_dir(), 'bp-security-');
        $this->assertNotFalse($temporary);
        file_put_contents($temporary, (string) $response->getContent());

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($temporary) === true);

        try {
            $this->assertGreaterThan(0, $zip->numFiles);

            for ($index = 0; $index < $zip->numFiles; $index++) {
                $name = (string) $zip->getNameIndex($index);
                $this->assertStringStartsWith('evil-x-evil-injected/', $name);
                $this->assertStringNotContainsString('../', $name);
            }
        } finally {
            $zip->close();
            @unlink($temporary);
        }
    }

    public function test_security_qa_matrix_keeps_required_operational_scenarios(): void
    {
        $collection = json_decode(
            (string) file_get_contents(base_path('postman/ApiBlueprint.generated.postman_collection.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $names = $this->itemNames($collection['item'] ?? []);

        foreach ([
            'negative_auth_login_invalid',
            'negative_users_list_unauthenticated',
            'negative_users_list_forbidden',
            'negative_users_create_missing_idempotency',
            'negative_users_create_validation',
            'idempotency_replay_users_create',
        ] as $requiredScenario) {
            $this->assertContains($requiredScenario, $names);
        }

        $this->assertFileExists(base_path('scripts/run-security-qa.sh'));
        $this->assertFileExists(base_path('docs/architecture/security-model.md'));
        $this->assertFileExists(base_path('docs/architecture/threat-model.md'));
        $this->assertFileExists(base_path('docs/governance/audit-retention-policy.md'));
    }

    private function itemNames(array $items): array
    {
        $names = [];

        foreach ($items as $item) {
            if (isset($item['name']) && is_string($item['name'])) {
                $names[] = $item['name'];
            }

            if (isset($item['item']) && is_array($item['item'])) {
                array_push($names, ...$this->itemNames($item['item']));
            }
        }

        return $names;
    }
}
