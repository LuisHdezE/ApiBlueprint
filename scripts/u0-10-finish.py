from pathlib import Path


def replace_once(path: Path, old: str, new: str) -> None:
    text = path.read_text()
    if old not in text:
        raise SystemExit(f'anchor not found in {path}: {old[:140]!r}')
    path.write_text(text.replace(old, new, 1))


# Canonical catalog metadata: users.show becomes executable, exportable and visible in Swagger/Admin.
config = Path('config/blueprint.php')
replace_once(
    config,
    "    ['id' => 'users.show', 'capability' => 'users', 'capability_label' => 'Usuarios', 'summary' => 'Obtener usuario', 'method' => 'GET', 'path' => '/api/v1/users/{id}', 'default_exposure' => 'admin'],",
    "    ['id' => 'users.show', 'capability' => 'users', 'capability_label' => 'Usuarios', 'summary' => 'Obtener usuario', 'method' => 'GET', 'path' => '/api/v1/users/{id}', 'default_exposure' => 'admin', 'implementation_status' => 'implemented', 'exportable' => true, 'openapi_ready' => true, 'tests_ready' => true],",
)

# Master Swagger uses one canonical UserData schema for list/show and a dedicated admin show contract.
openapi = Path('app/Application/Blueprint/Queries/GetMasterOpenApi.php')
text = openapi.read_text().replace('UserListItem', 'UserData')
marker = "        if (str_ends_with($feature['id'], '.list')) {\n"
if marker not in text:
    raise SystemExit('master openapi list marker not found')
users_show_response = """        if ($feature['id'] === 'users.show') {
            return [
                '200' => [
                    'description' => 'Usuario encontrado.',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['data'],
                                'properties' => [
                                    'data' => ['$ref' => '#/components/schemas/UserData'],
                                ],
                            ],
                        ],
                    ],
                ],
                '401' => [
                    'description' => 'Autenticación requerida.',
                    'content' => ['application/problem+json' => ['schema' => ['$ref' => '#/components/schemas/ProblemDetails']]],
                ],
                '403' => [
                    'description' => 'Se requieren privilegios de administrador.',
                    'content' => ['application/problem+json' => ['schema' => ['$ref' => '#/components/schemas/ProblemDetails']]],
                ],
                '404' => [
                    'description' => 'Usuario no encontrado.',
                    'content' => ['application/problem+json' => ['schema' => ['$ref' => '#/components/schemas/ProblemDetails']]],
                ],
            ];
        }

"""
text = text.replace(marker, users_show_response + marker, 1)
openapi.write_text(text)

# Existing users.list acceptance follows the shared UserData rename.
users_list_test = Path('tests/Feature/GeneratedUsersListVerticalSliceExportTest.php')
users_list_test.write_text(users_list_test.read_text().replace('UserListItem', 'UserData'))

# Catalog invariants and master Swagger assertions now include users.show.
master_test = Path('tests/Feature/MasterCatalogTest.php')
text = master_test.read_text().replace('UserListItem', 'UserData')
old = """        $this->assertSame('implemented', $features['users.list']['implementation_status']);
        $this->assertTrue($features['users.list']['exportable']);
        $this->assertTrue($features['users.list']['openapi_ready']);
        $this->assertTrue($features['users.list']['tests_ready']);

        $saas = collect($response->json('applications'))->firstWhere('id', 'saas');
        $this->assertSame('partial', $saas['status']);
        $this->assertSame(['implemented' => 3, 'total' => 8], $saas['coverage']);
"""
new = """        $this->assertSame('implemented', $features['users.list']['implementation_status']);
        $this->assertTrue($features['users.list']['exportable']);
        $this->assertTrue($features['users.list']['openapi_ready']);
        $this->assertTrue($features['users.list']['tests_ready']);
        $this->assertSame('implemented', $features['users.show']['implementation_status']);
        $this->assertTrue($features['users.show']['exportable']);
        $this->assertTrue($features['users.show']['openapi_ready']);
        $this->assertTrue($features['users.show']['tests_ready']);

        $saas = collect($response->json('applications'))->firstWhere('id', 'saas');
        $this->assertSame('partial', $saas['status']);
        $this->assertSame(['implemented' => 4, 'total' => 8], $saas['coverage']);
"""
if old not in text:
    raise SystemExit('master catalog coverage anchor not found')
text = text.replace(old, new, 1)
old = """        $this->assertSame('#/components/schemas/UserData', $paths['/api/v1/users']['get']['responses']['200']['content']['application/json']['schema']['properties']['data']['items']['$ref']);
        $this->assertCount(5, $paths);
"""
new = """        $this->assertSame('#/components/schemas/UserData', $paths['/api/v1/users']['get']['responses']['200']['content']['application/json']['schema']['properties']['data']['items']['$ref']);
        $this->assertSame('users.show', $paths['/api/v1/users/{id}']['get']['x-apiblueprint-feature-id']);
        $this->assertSame([['bearerAuth' => []]], $paths['/api/v1/users/{id}']['get']['security']);
        $this->assertSame('#/components/schemas/UserData', $paths['/api/v1/users/{id}']['get']['responses']['200']['content']['application/json']['schema']['properties']['data']['$ref']);
        $this->assertArrayHasKey('403', $paths['/api/v1/users/{id}']['get']['responses']);
        $this->assertArrayHasKey('404', $paths['/api/v1/users/{id}']['get']['responses']);
        $this->assertCount(6, $paths);
"""
if old not in text:
    raise SystemExit('master openapi assertions anchor not found')
master_test.write_text(text.replace(old, new, 1))

# A show-only export proves no dormant list/product infrastructure leaks into the solution.
show_test = Path('tests/Feature/GeneratedUsersShowVerticalSliceExportTest.php')
show_test.write_text(r'''<?php

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
        $this->assertStringContainsString("'role' => $this->role", $data);
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
''')

# Living product documentation.
feature_doc = Path('docs/product/master-feature-library.md')
text = feature_doc.read_text()
replace = "- `users.list`.\n"
if replace not in text:
    raise SystemExit('feature list anchor not found')
text = text.replace(replace, "- `users.list`;\n- `users.show`.\n", 1)
old = "`users.list` reutiliza esa misma identidad y el gate `admin-api`. La paginación cursor/offset y la validación estructural de listados son infraestructura compartida con `products.list`; una nueva feature de listado no debe copiar esos algoritmos."
new = "`users.list` y `users.show` reutilizan esa misma identidad y el gate `admin-api`. Ambas comparten `UserData` como representación pública segura, sin password. La paginación cursor/offset y la validación estructural de listados siguen siendo infraestructura compartida entre `users.list` y `products.list`; una nueva feature de listado no debe copiar esos algoritmos."
if old not in text:
    raise SystemExit('feature users paragraph anchor not found')
text = text.replace(old, new, 1)
old = "Swagger no es documentación de cierre: se actualiza en el mismo cambio que termina una feature. `auth.login` publica su request body, respuesta con token Bearer y errores 401/422 desde el mismo checkpoint que lo vuelve ejecutable. `users.list` publica filtros, sorting, paginación, esquema seguro de usuario y errores 401/403/422 en el mismo cambio que lo incorpora a la biblioteca."
new = "Swagger no es documentación de cierre: se actualiza en el mismo cambio que termina una feature. `auth.login` publica su request body, respuesta con token Bearer y errores 401/422 desde el mismo checkpoint que lo vuelve ejecutable. `users.list` publica filtros, sorting, paginación y errores 401/403/422; `users.show` publica `UserData` y los contratos 200/401/403/404 en el mismo cambio que lo incorpora a la biblioteca."
if old not in text:
    raise SystemExit('swagger documentation anchor not found')
feature_doc.write_text(text.replace(old, new, 1))

roadmap = Path('docs/delivery/roadmap.md')
text = roadmap.read_text()
text = text.replace('### Checkpoint 1 - `users.list` 🚧', '### Checkpoint 1 - `users.list` ✅', 1)
text = text.replace('- salida segura `UserListItem` sin password;', '- salida segura `UserData` sin password;', 1)
anchor = """- acceptance generado prueba autorización admin, ausencia de password, paginación, filtros y sorting.

### Siguientes checkpoints previstos
"""
replacement = """- acceptance generado prueba autorización admin, ausencia de password, paginación, filtros y sorting.

Integrado por PR #11 con CI pre-merge #76 y post-merge #77 verdes.

## U0.10 - Users Library: `users.show` 🚧

- reutiliza `User`, Sanctum y el gate `admin-api` existentes;
- `UserData` pasa a ser la representación pública compartida de lectura de Usuarios;
- `UserReadRepository` y `GetUser` en Application;
- `DatabaseUserReadRepository` en Infrastructure sin exponer password;
- controlador real con 200 y 404 RFC 9457 en español;
- 401/403 gobernados por Sanctum/RBAC;
- Swagger maestro y OpenAPI generado actualizados en el mismo checkpoint;
- export exclusivo de `users.show` no arrastra paginación, listados ni Products;
- acceptance SaaS debe ejecutar list + show sobre la misma identidad y `UserData`.

### Siguientes checkpoints previstos
"""
if anchor not in text:
    raise SystemExit('roadmap users checkpoint anchor not found')
roadmap.write_text(text.replace(anchor, replacement, 1))

print('U0.10 catalog, OpenAPI, tests and docs patch applied')
