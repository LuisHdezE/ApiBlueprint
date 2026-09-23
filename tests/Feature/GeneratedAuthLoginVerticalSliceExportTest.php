<?php

namespace Tests\Feature;

use Tests\TestCase;
use ZipArchive;

final class GeneratedAuthLoginVerticalSliceExportTest extends TestCase
{
    public function test_auth_login_exports_one_reusable_executable_sanctum_slice(): void
    {
        $response = $this->postJson('/api/v1/blueprint/export', $this->manifest())
            ->assertOk()
            ->assertHeader('content-type', 'application/zip');

        $temporaryFile = tempnam(sys_get_temp_dir(), 'apiblueprint-auth-login-');
        $this->assertNotFalse($temporaryFile);
        file_put_contents($temporaryFile, $response->getContent());

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($temporaryFile) === true);

        $manifest = $this->zipJson($zip, 'auth-api/.apiblueprint.json');
        $composer = $this->zipJson($zip, 'auth-api/composer.json');
        $controller = $zip->getFromName('auth-api/app/Presentation/Http/Controllers/Generated/AuthLoginController.php');
        $contract = $zip->getFromName('auth-api/app/Application/Authentication/Contracts/AuthenticationGateway.php');
        $useCase = $zip->getFromName('auth-api/app/Application/Authentication/UseCases/LoginUser.php');
        $adapter = $zip->getFromName('auth-api/app/Infrastructure/Authentication/SanctumAuthenticationGateway.php');
        $user = $zip->getFromName('auth-api/app/Infrastructure/Identity/User.php');
        $usersMigration = $zip->getFromName('auth-api/database/migrations/2026_01_01_000000_create_users_table.php');
        $tokensMigration = $zip->getFromName('auth-api/database/migrations/2026_01_01_000001_create_personal_access_tokens_table.php');
        $provider = $zip->getFromName('auth-api/app/Providers/AppServiceProvider.php');
        $test = $zip->getFromName('auth-api/tests/Feature/AuthLoginVerticalSliceTest.php');
        $openApi = $zip->getFromName('auth-api/openapi/openapi.yaml');
        $readme = $zip->getFromName('auth-api/README.md');

        $this->assertSame('sanctum', $manifest['governance']['authentication']);
        $this->assertSame('authentication', $manifest['resolution']['governance_adjustments'][0]['capability'] ?? null);
        $this->assertSame('^4.3', $composer['require']['laravel/sanctum'] ?? null);
        $this->assertSame('^1.6', $composer['require-dev']['mockery/mockery'] ?? null);

        $this->assertIsString($controller);
        $this->assertStringContainsString('LoginUser', $controller);
        $this->assertStringContainsString('Credenciales inválidas', $controller);
        $this->assertStringNotContainsString("'status' => 501", $controller);

        $this->assertIsString($contract);
        $this->assertStringContainsString('interface AuthenticationGateway', $contract);
        $this->assertIsString($useCase);
        $this->assertStringContainsString('final readonly class LoginUser', $useCase);
        $this->assertIsString($adapter);
        $this->assertStringContainsString('createToken($tokenName)', $adapter);
        $this->assertIsString($user);
        $this->assertStringContainsString('use HasApiTokens;', $user);
        $this->assertIsString($usersMigration);
        $this->assertStringContainsString("Schema::create('users'", $usersMigration);
        $this->assertIsString($tokensMigration);
        $this->assertStringContainsString("Schema::create('personal_access_tokens'", $tokensMigration);
        $this->assertIsString($provider);
        $this->assertStringContainsString('AuthenticationGateway::class, SanctumAuthenticationGateway::class', $provider);

        $this->assertIsString($test);
        $this->assertStringContainsString('RefreshDatabase', $test);
        $this->assertStringContainsString('PersonalAccessToken::findToken', $test);

        $this->assertIsString($openApi);
        $this->assertStringContainsString('AuthLoginRequest:', $openApi);
        $this->assertStringContainsString('AuthLoginResponse:', $openApi);
        $this->assertStringContainsString("'401':", $openApi);
        $this->assertStringContainsString("'422':", $openApi);
        $this->assertStringNotContainsString("'501':", $openApi);

        $this->assertIsString($readme);
        $this->assertStringContainsString('`auth.login`', $readme);
        $this->assertStringContainsString('php artisan migrate', $readme);

        $this->assertFalse($zip->locateName('auth-api/app/Domain/Products/Product.php'));
        $this->assertFalse($zip->locateName('auth-api/database/migrations/2026_01_01_000000_create_products_table.php'));

        $zip->close();
        @unlink($temporaryFile);
    }

    private function manifest(): array
    {
        return [
            'schema_version' => '0.3',
            'generator' => 'ApiBlueprint',
            'project' => [
                'name' => 'Auth API',
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
                ['id' => 'auth.login', 'exposure' => 'public'],
            ],
        ];
    }

    private function zipJson(ZipArchive $zip, string $path): array
    {
        $content = $zip->getFromName($path);
        $this->assertIsString($content);

        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }
}
