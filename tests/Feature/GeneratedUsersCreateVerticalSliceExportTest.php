<?php

namespace Tests\Feature;

use Tests\TestCase;
use ZipArchive;

final class GeneratedUsersCreateVerticalSliceExportTest extends TestCase
{
    public function test_users_create_exports_canonical_admin_write_slice_without_dormant_read_or_list_infrastructure(): void
    {
        $response = $this->postJson('/api/v1/blueprint/export', $this->manifest())
            ->assertOk()
            ->assertHeader('content-type', 'application/zip');

        $temporaryFile = tempnam(sys_get_temp_dir(), 'apiblueprint-users-create-');
        $this->assertNotFalse($temporaryFile);
        file_put_contents($temporaryFile, $response->getContent());

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($temporaryFile) === true);

        $controller = $zip->getFromName('users-create-api/app/Presentation/Http/Controllers/Generated/UsersCreateController.php');
        $data = $zip->getFromName('users-create-api/app/Application/Users/Data/UserData.php');
        $input = $zip->getFromName('users-create-api/app/Application/Users/Data/CreateUserData.php');
        $repository = $zip->getFromName('users-create-api/app/Infrastructure/Users/DatabaseUserCreateRepository.php');
        $validator = $zip->getFromName('users-create-api/app/Presentation/Http/Support/CreateUserRequestValidator.php');
        $provider = $zip->getFromName('users-create-api/app/Providers/AppServiceProvider.php');
        $routes = $zip->getFromName('users-create-api/routes/api.php');
        $openApi = $zip->getFromName('users-create-api/openapi/openapi.yaml');
        $verticalSliceTest = $zip->getFromName('users-create-api/tests/Feature/UsersCreateVerticalSliceTest.php');
        $manifest = $zip->getFromName('users-create-api/.apiblueprint.json');

        $this->assertIsString($controller);
        $this->assertStringContainsString('CreateUser', $controller);
        $this->assertStringContainsString('CreateUserRequestValidator', $controller);
        $this->assertStringNotContainsString("'status' => 501", $controller);
        $this->assertIsString($data);
        $this->assertStringContainsString('final readonly class UserData', $data);
        $this->assertStringNotContainsString('password', $data);
        $this->assertIsString($input);
        $this->assertStringContainsString('final readonly class CreateUserData', $input);
        $this->assertIsString($repository);
        $this->assertStringContainsString('Hash::make($data->password)', $repository);
        $this->assertIsString($validator);
        $this->assertStringContainsString("Rule::unique('users', 'email')", $validator);
        $this->assertIsString($provider);
        $this->assertStringContainsString('UserCreateRepository::class, DatabaseUserCreateRepository::class', $provider);
        $this->assertIsString($routes);
        $this->assertStringContainsString("'auth:sanctum'", $routes);
        $this->assertStringContainsString("'can:admin-api'", $routes);
        $this->assertStringContainsString("'idempotency'", $routes);
        $this->assertIsString($openApi);
        $this->assertStringContainsString('CreateUserRequest:', $openApi);
        $this->assertStringContainsString("'201':", $openApi);
        $this->assertStringContainsString("'422':", $openApi);
        $this->assertStringNotContainsString("'501':", $openApi);
        $this->assertIsString($verticalSliceTest);
        $this->assertStringContainsString('test_admin_can_create_a_user_without_exposing_password', $verticalSliceTest);

        $manifestData = json_decode($manifest, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('sanctum', $manifestData['governance']['authentication']);
        $this->assertContains('auth.login', array_column($manifestData['endpoints'], 'id'));
        $this->assertContains('users.create', array_column($manifestData['endpoints'], 'id'));

        $this->assertFalse($zip->locateName('users-create-api/app/Presentation/Http/Controllers/Generated/UsersListController.php'));
        $this->assertFalse($zip->locateName('users-create-api/app/Presentation/Http/Controllers/Generated/UsersShowController.php'));
        $this->assertFalse($zip->locateName('users-create-api/app/Infrastructure/Database/DatabaseQueryPaginator.php'));
        $this->assertFalse($zip->locateName('users-create-api/app/Domain/Products/Product.php'));

        $zip->close();
        @unlink($temporaryFile);
    }

    private function manifest(): array
    {
        return [
            'schema_version' => '0.3',
            'generator' => 'ApiBlueprint',
            'project' => ['name' => 'Users Create API', 'api_version' => 'v1'],
            'template' => 'custom',
            'governance' => [
                'authentication' => 'none',
                'rbac' => true,
                'correlation_id' => true,
                'rate_limiting' => ['enabled' => true, 'requests_per_minute' => 60],
                'pagination' => ['strategy' => 'cursor', 'default_size' => 25, 'max_size' => 100],
                'filtering' => true,
                'sorting' => true,
                'idempotency' => true,
                'audit' => true,
            ],
            'endpoints' => [['id' => 'users.create', 'exposure' => 'admin']],
        ];
    }
}
