<?php

namespace Tests\Feature;

use Tests\TestCase;
use ZipArchive;

final class GeneratedAuthLogoutVerticalSliceExportTest extends TestCase
{
    public function test_auth_logout_reuses_login_identity_and_exports_current_token_revocation(): void
    {
        $response = $this->postJson('/api/v1/blueprint/export', $this->manifest())
            ->assertOk()
            ->assertHeader('content-type', 'application/zip');

        $temporaryFile = tempnam(sys_get_temp_dir(), 'apiblueprint-auth-logout-');
        $this->assertNotFalse($temporaryFile);
        file_put_contents($temporaryFile, $response->getContent());

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($temporaryFile) === true);

        $manifest = $this->zipJson($zip, 'auth-logout-api/.apiblueprint.json');
        $controller = $zip->getFromName('auth-logout-api/app/Presentation/Http/Controllers/Generated/AuthLogoutController.php');
        $contract = $zip->getFromName('auth-logout-api/app/Application/Authentication/Contracts/TokenRevocationGateway.php');
        $useCase = $zip->getFromName('auth-logout-api/app/Application/Authentication/UseCases/LogoutUser.php');
        $adapter = $zip->getFromName('auth-logout-api/app/Infrastructure/Authentication/SanctumTokenRevocationGateway.php');
        $provider = $zip->getFromName('auth-logout-api/app/Providers/AppServiceProvider.php');
        $test = $zip->getFromName('auth-logout-api/tests/Feature/AuthLogoutVerticalSliceTest.php');
        $openApi = $zip->getFromName('auth-logout-api/openapi/openapi.yaml');

        $endpointIds = array_column($manifest['endpoints'], 'id');
        $this->assertSame(['auth.login', 'auth.logout'], $endpointIds);
        $this->assertTrue($manifest['endpoints'][0]['auto_added']);
        $this->assertSame('sanctum', $manifest['governance']['authentication']);

        $this->assertIsString($controller);
        $this->assertStringContainsString('LogoutUser', $controller);
        $this->assertStringNotContainsString("'status' => 501", $controller);
        $this->assertIsString($contract);
        $this->assertStringContainsString('interface TokenRevocationGateway', $contract);
        $this->assertIsString($useCase);
        $this->assertStringContainsString('final readonly class LogoutUser', $useCase);
        $this->assertIsString($adapter);
        $this->assertStringContainsString('PersonalAccessToken::findToken', $adapter);
        $this->assertIsString($provider);
        $this->assertStringContainsString('TokenRevocationGateway::class, SanctumTokenRevocationGateway::class', $provider);
        $this->assertIsString($test);
        $this->assertStringContainsString("postJson('/api/v1/auth/login'", $test);
        $this->assertStringContainsString("postJson('/api/v1/auth/logout'", $test);
        $this->assertStringContainsString('assertNull(PersonalAccessToken::findToken', $test);

        $this->assertIsString($openApi);
        $this->assertStringContainsString('/api/v1/auth/logout', $openApi);
        $this->assertStringContainsString("'204':", $openApi);
        $this->assertStringContainsString("'401':", $openApi);

        $this->assertFalse($zip->locateName('auth-logout-api/app/Domain/Products/Product.php'));
        $zip->close();
        @unlink($temporaryFile);
    }

    private function manifest(): array
    {
        return [
            'schema_version' => '0.3',
            'generator' => 'ApiBlueprint',
            'project' => ['name' => 'Auth Logout API', 'api_version' => 'v1'],
            'template' => 'custom',
            'governance' => [
                'authentication' => 'none', 'rbac' => true, 'correlation_id' => true,
                'rate_limiting' => ['enabled' => true, 'requests_per_minute' => 60],
                'pagination' => ['strategy' => 'cursor', 'default_size' => 25, 'max_size' => 100],
                'filtering' => true, 'sorting' => true, 'idempotency' => true, 'audit' => true,
            ],
            'endpoints' => [['id' => 'auth.logout', 'exposure' => 'authenticated']],
        ];
    }

    private function zipJson(ZipArchive $zip, string $path): array
    {
        $content = $zip->getFromName($path);
        $this->assertIsString($content);

        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }
}
