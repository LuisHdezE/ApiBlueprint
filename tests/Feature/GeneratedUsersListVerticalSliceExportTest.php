<?php

namespace Tests\Feature;

use Tests\TestCase;
use ZipArchive;

final class GeneratedUsersListVerticalSliceExportTest extends TestCase
{
    public function test_users_list_exports_one_canonical_admin_slice_with_shared_list_infrastructure(): void
    {
        $response = $this->postJson('/api/v1/blueprint/export', $this->manifest())
            ->assertOk()
            ->assertHeader('content-type', 'application/zip');

        $temporaryFile = tempnam(sys_get_temp_dir(), 'apiblueprint-users-list-');
        $this->assertNotFalse($temporaryFile);
        file_put_contents($temporaryFile, $response->getContent());

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($temporaryFile) === true);

        $controller = $zip->getFromName('users-api/app/Presentation/Http/Controllers/Generated/UsersListController.php');
        $contract = $zip->getFromName('users-api/app/Application/Users/Contracts/UserListRepository.php');
        $useCase = $zip->getFromName('users-api/app/Application/Users/UseCases/ListUsers.php');
        $repository = $zip->getFromName('users-api/app/Infrastructure/Users/DatabaseUserListRepository.php');
        $paginator = $zip->getFromName('users-api/app/Infrastructure/Database/DatabaseQueryPaginator.php');
        $validator = $zip->getFromName('users-api/app/Presentation/Http/Support/ListQueryValidator.php');
        $provider = $zip->getFromName('users-api/app/Providers/AppServiceProvider.php');
        $routes = $zip->getFromName('users-api/routes/api.php');
        $openApi = $zip->getFromName('users-api/openapi/openapi.yaml');
        $verticalSliceTest = $zip->getFromName('users-api/tests/Feature/UsersListVerticalSliceTest.php');
        $manifest = $zip->getFromName('users-api/.apiblueprint.json');

        $this->assertIsString($controller);
        $this->assertStringContainsString('ListUsers', $controller);
        $this->assertStringContainsString('ListQueryValidator', $controller);
        $this->assertStringNotContainsString("'status' => 501", $controller);

        $this->assertIsString($contract);
        $this->assertStringContainsString('interface UserListRepository', $contract);
        $this->assertIsString($useCase);
        $this->assertStringContainsString('final readonly class ListUsers', $useCase);
        $this->assertIsString($repository);
        $this->assertStringContainsString("DB::table('users')->select(['id', 'name', 'email', 'role'])", $repository);
        $this->assertStringNotContainsString('password', $repository);

        $this->assertIsString($paginator);
        $this->assertStringContainsString('final class DatabaseQueryPaginator', $paginator);
        $this->assertStringContainsString('cursorPage', $paginator);
        $this->assertStringContainsString('offsetPage', $paginator);
        $this->assertIsString($validator);
        $this->assertStringContainsString('final class ListQueryValidator', $validator);
        $this->assertFalse($zip->locateName('users-api/app/Presentation/Http/Support/ProductListQueryValidator.php'));

        $this->assertIsString($provider);
        $this->assertStringContainsString('UserListRepository::class, DatabaseUserListRepository::class', $provider);
        $this->assertIsString($routes);
        $this->assertStringContainsString("'auth:sanctum'", $routes);
        $this->assertStringContainsString("'can:admin-api'", $routes);

        $this->assertIsString($openApi);
        $this->assertStringContainsString('UserListItem:', $openApi);
        $this->assertStringContainsString('ListMeta:', $openApi);
        $this->assertStringContainsString('filter[email]', $openApi);
        $this->assertStringContainsString("'403':", $openApi);
        $this->assertStringNotContainsString("'501':", $openApi);

        $this->assertIsString($verticalSliceTest);
        $this->assertStringContainsString('test_non_admin_user_is_forbidden', $verticalSliceTest);
        $this->assertStringContainsString("assertArrayNotHasKey('password'", $verticalSliceTest);

        $this->assertIsString($manifest);
        $manifestData = json_decode($manifest, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('sanctum', $manifestData['governance']['authentication']);
        $this->assertContains('auth.login', array_column($manifestData['endpoints'], 'id'));
        $this->assertContains('users.list', array_column($manifestData['endpoints'], 'id'));

        $this->assertFalse($zip->locateName('users-api/app/Domain/Products/Product.php'));
        $this->assertFalse($zip->locateName('users-api/app/Infrastructure/Products/DatabaseProductListRepository.php'));

        $zip->close();
        @unlink($temporaryFile);
    }

    private function manifest(): array
    {
        return [
            'schema_version' => '0.3',
            'generator' => 'ApiBlueprint',
            'project' => [
                'name' => 'Users API',
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
                ['id' => 'users.list', 'exposure' => 'admin'],
            ],
        ];
    }
}
