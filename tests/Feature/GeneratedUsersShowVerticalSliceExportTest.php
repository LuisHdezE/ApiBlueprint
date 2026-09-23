<?php

namespace Tests\Feature;

use Tests\TestCase;
use ZipArchive;

final class GeneratedUsersShowVerticalSliceExportTest extends TestCase
{
    public function test_users_show_exports_one_canonical_admin_slice_without_dormant_list_infrastructure(): void
    {
        $response = $this->postJson('/api/v1/blueprint/export', $this->manifest())
            ->assertOk()
            ->assertHeader('content-type', 'application/zip');

        $temporaryFile = tempnam(sys_get_temp_dir(), 'apiblueprint-users-show-');
        $this->assertNotFalse($temporaryFile);
        file_put_contents($temporaryFile, $response->getContent());

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($temporaryFile) === true);

        $controller = $zip->getFromName('users-show-api/app/Presentation/Http/Controllers/Generated/UsersShowController.php');
        $data = $zip->getFromName('users-show-api/app/Application/Users/Data/UserData.php');
        $contract = $zip->getFromName('users-show-api/app/Application/Users/Contracts/UserReadRepository.php');
        $useCase = $zip->getFromName('users-show-api/app/Application/Users/UseCases/GetUser.php');
        $repository = $zip->getFromName('users-show-api/app/Infrastructure/Users/DatabaseUserReadRepository.php');
        $provider = $zip->getFromName('users-show-api/app/Providers/AppServiceProvider.php');
        $routes = $zip->getFromName('users-show-api/routes/api.php');
        $openApi = $zip->getFromName('users-show-api/openapi/openapi.yaml');
        $verticalSliceTest = $zip->getFromName('users-show-api/tests/Feature/UsersShowVerticalSliceTest.php');
        $manifest = $zip->getFromName('users-show-api/.apiblueprint.json');

        $this->assertIsString($controller);
        $this->assertStringContainsString('GetUser', $controller);
        $this->assertStringContainsString('Usuario no encontrado', $controller);
        $this->assertStringNotContainsString("'status' => 501", $controller);

        $this->assertIsString($data);
        $this->assertStringContainsString('final readonly class UserData', $data);
        $this->assertStringContainsString("'role' => \$this->role", $data);
        $this->assertStringNotContainsString('password', $data);

        $this->assertIsString($contract);
        $this->assertStringContainsString('interface UserReadRepository', $contract);
        $this->assertIsString($useCase);
        $this->assertStringContainsString('final readonly class GetUser', $useCase);
        $this->assertIsString($repository);
        $this->assertStringContainsString("DB::table('users')", $repository);
        $this->assertStringContainsString("select(['id', 'name', 'email', 'role'])", $repository);
        $this->assertStringNotContainsString('password', $repository);

        $this->assertIsString($provider);
        $this->assertStringContainsString('UserReadRepository::class, DatabaseUserReadRepository::class', $provider);
        $this->assertIsString($routes);
        $this->assertStringContainsString("'auth:sanctum'", $routes);
        $this->assertStringContainsString("'can:admin-api'", $routes);

        $this->assertIsString($openApi);
        $this->assertStringContainsString('UserData:', $openApi);
        $this->assertStringContainsString("'200':", $openApi);
        $this->assertStringContainsString("'401':", $openApi);
        $this->assertStringContainsString("'403':", $openApi);
        $this->assertStringContainsString("'404':", $openApi);
        $this->assertStringNotContainsString("'501':", $openApi);

        $this->assertIsString($verticalSliceTest);
        $this->assertStringContainsString('test_admin_can_retrieve_a_user_without_password', $verticalSliceTest);
        $this->assertStringContainsString('test_missing_user_uses_problem_details_in_spanish', $verticalSliceTest);
        $this->assertStringContainsString('test_non_admin_user_is_forbidden', $verticalSliceTest);

        $this->assertIsString($manifest);
        $manifestData = json_decode($manifest, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('sanctum', $manifestData['governance']['authentication']);
        $this->assertContains('auth.login', array_column($manifestData['endpoints'], 'id'));
        $this->assertContains('users.show', array_column($manifestData['endpoints'], 'id'));

        $this->assertFalse($zip->locateName('users-show-api/app/Presentation/Http/Controllers/Generated/UsersListController.php'));
        $this->assertFalse($zip->locateName('users-show-api/app/Application/Users/Contracts/UserListRepository.php'));
        $this->assertFalse($zip->locateName('users-show-api/app/Infrastructure/Database/DatabaseQueryPaginator.php'));
        $this->assertFalse($zip->locateName('users-show-api/app/Presentation/Http/Support/QueryOptionsParser.php'));
        $this->assertFalse($zip->locateName('users-show-api/app/Domain/Products/Product.php'));

        $zip->close();
        @unlink($temporaryFile);
    }

    private function manifest(): array
    {
        return [
            'schema_version' => '0.3',
            'generator' => 'ApiBlueprint',
            'project' => [
                'name' => 'Users Show API',
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
                ['id' => 'users.show', 'exposure' => 'admin'],
            ],
        ];
    }
}
