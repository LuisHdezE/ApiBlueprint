from pathlib import Path


def replace_once(path: Path, old: str, new: str, label: str) -> None:
    text = path.read_text()
    count = text.count(old)
    if count != 1:
        raise SystemExit(f'{label}: expected 1 match, found {count}')
    path.write_text(text.replace(old, new, 1))


config = Path('config/blueprint.php')
replace_once(
    config,
    "    ['id' => 'users.list', 'capability' => 'users', 'capability_label' => 'Usuarios', 'summary' => 'Listar usuarios', 'method' => 'GET', 'path' => '/api/v1/users', 'default_exposure' => 'admin'],",
    "    ['id' => 'users.list', 'capability' => 'users', 'capability_label' => 'Usuarios', 'summary' => 'Listar usuarios', 'method' => 'GET', 'path' => '/api/v1/users', 'default_exposure' => 'admin', 'implementation_status' => 'implemented', 'exportable' => true, 'openapi_ready' => true, 'tests_ready' => true],",
    'users.list catalog status',
)

openapi = Path('app/Application/Blueprint/Queries/GetMasterOpenApi.php')
replace_once(
    openapi,
    "            if ($catalog['governance']['defaults']['filtering']) {\n                $parameters[] = ['name' => 'filter[id]', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string']];\n                $parameters[] = ['name' => 'filter[name]', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string']];\n            }",
    "            if ($catalog['governance']['defaults']['filtering']) {\n                $filterFields = $feature['id'] === 'users.list'\n                    ? ['id', 'name', 'email', 'role']\n                    : ['id', 'name'];\n                foreach ($filterFields as $filterField) {\n                    $parameters[] = [\n                        'name' => 'filter['.$filterField.']',\n                        'in' => 'query',\n                        'required' => false,\n                        'schema' => ['type' => 'string'],\n                    ];\n                }\n            }",
    'master openapi list filters',
)
replace_once(
    openapi,
    "        if (str_ends_with($feature['id'], '.list')) {\n            return [\n                '200' => ['description' => 'Listado obtenido correctamente.'],",
    "        if ($feature['id'] === 'users.list') {\n            return [\n                '200' => [\n                    'description' => 'Listado paginado de usuarios.',\n                    'content' => [\n                        'application/json' => [\n                            'schema' => [\n                                'type' => 'object',\n                                'required' => ['data', 'meta'],\n                                'properties' => [\n                                    'data' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/UserListItem']],\n                                    'meta' => ['$ref' => '#/components/schemas/ListMeta'],\n                                ],\n                            ],\n                        ],\n                    ],\n                ],\n                '401' => [\n                    'description' => 'Autenticación requerida.',\n                    'content' => ['application/problem+json' => ['schema' => ['$ref' => '#/components/schemas/ProblemDetails']]],\n                ],\n                '403' => [\n                    'description' => 'Se requieren privilegios de administrador.',\n                    'content' => ['application/problem+json' => ['schema' => ['$ref' => '#/components/schemas/ProblemDetails']]],\n                ],\n                '422' => [\n                    'description' => 'Parámetros de consulta inválidos.',\n                    'content' => ['application/problem+json' => ['schema' => ['$ref' => '#/components/schemas/ProblemDetails']]],\n                ],\n            ];\n        }\n\n        if (str_ends_with($feature['id'], '.list')) {\n            return [\n                '200' => ['description' => 'Listado obtenido correctamente.'],",
    'master openapi users responses',
)
replace_once(
    openapi,
    "                    'ProblemDetails' => [\n                        'type' => 'object',",
    "                    'UserListItem' => [\n                        'type' => 'object',\n                        'required' => ['id', 'name', 'email', 'role'],\n                        'properties' => [\n                            'id' => ['type' => 'string'],\n                            'name' => ['type' => 'string'],\n                            'email' => ['type' => 'string', 'format' => 'email'],\n                            'role' => ['type' => 'string'],\n                        ],\n                    ],\n                    'ListMeta' => [\n                        'type' => 'object',\n                        'required' => ['strategy', 'page_size', 'has_more'],\n                        'properties' => [\n                            'strategy' => ['type' => 'string', 'enum' => ['cursor', 'offset']],\n                            'page_size' => ['type' => 'integer'],\n                            'has_more' => ['type' => 'boolean'],\n                            'next_cursor' => ['type' => ['string', 'null']],\n                            'page_number' => ['type' => 'integer'],\n                            'total' => ['type' => 'integer'],\n                            'total_pages' => ['type' => 'integer'],\n                        ],\n                    ],\n                    'ProblemDetails' => [\n                        'type' => 'object',",
    'master openapi user schemas',
)

catalog_test = Path('tests/Feature/MasterCatalogTest.php')
replace_once(
    catalog_test,
    "        $this->assertTrue($features['auth.logout']['tests_ready']);\n\n        $saas = collect($response->json('applications'))->firstWhere('id', 'saas');\n        $this->assertSame('partial', $saas['status']);\n        $this->assertSame(['implemented' => 2, 'total' => 8], $saas['coverage']);",
    "        $this->assertTrue($features['auth.logout']['tests_ready']);\n        $this->assertSame('implemented', $features['users.list']['implementation_status']);\n        $this->assertTrue($features['users.list']['exportable']);\n        $this->assertTrue($features['users.list']['openapi_ready']);\n        $this->assertTrue($features['users.list']['tests_ready']);\n\n        $saas = collect($response->json('applications'))->firstWhere('id', 'saas');\n        $this->assertSame('partial', $saas['status']);\n        $this->assertSame(['implemented' => 3, 'total' => 8], $saas['coverage']);",
    'master catalog SaaS coverage',
)
replace_once(
    catalog_test,
    "        $this->assertArrayHasKey('204', $paths['/api/v1/auth/logout']['post']['responses']);\n        $this->assertCount(4, $paths);",
    "        $this->assertArrayHasKey('204', $paths['/api/v1/auth/logout']['post']['responses']);\n        $this->assertSame('users.list', $paths['/api/v1/users']['get']['x-apiblueprint-feature-id']);\n        $this->assertSame([['bearerAuth' => []]], $paths['/api/v1/users']['get']['security']);\n        $this->assertArrayHasKey('403', $paths['/api/v1/users']['get']['responses']);\n        $this->assertSame('#/components/schemas/UserListItem', $paths['/api/v1/users']['get']['responses']['200']['content']['application/json']['schema']['properties']['data']['items']['$ref']);\n        $this->assertCount(5, $paths);",
    'master openapi users assertions',
)

roadmap = Path('docs/delivery/roadmap.md')
replace_once(roadmap, "### Checkpoint 2 - `auth.logout` 🚧", "### Checkpoint 2 - `auth.logout` ✅", 'logout roadmap status')
replace_once(
    roadmap,
    "- acceptance ejecutando login → logout → token revocado.\n\n### Siguientes checkpoints previstos",
    "- acceptance ejecutando login → logout → token revocado.\n\nIntegrado por PR #10 con CI pre-merge y post-merge verdes. U0.8 queda cerrado con un único ciclo canónico de autenticación reutilizable.\n\n## U0.9 - Users Library 🚧\n\n### Checkpoint 1 - `users.list` 🚧\n\n- reutiliza la identidad `User`, Sanctum y RBAC ya existentes;\n- endpoint canónico `GET /api/v1/users` con exposición `admin`;\n- puerto `UserListRepository` y caso de uso `ListUsers` en Application;\n- salida segura `UserListItem` sin password;\n- filtros `id`, `name`, `email` y `role`;\n- sorting determinista por `id`, `name`, `email` y `role`;\n- cursor keyset y offset reutilizando un único `DatabaseQueryPaginator` compartido con Products;\n- validación reusable mediante `ListQueryValidator`, sin validador duplicado por recurso;\n- 401/403/422 mediante contratos gobernados;\n- Swagger maestro y OpenAPI generado actualizados en el mismo checkpoint;\n- catálogo SaaS actualizado automáticamente desde la feature canónica;\n- acceptance generado prueba autorización admin, ausencia de password, paginación, filtros y sorting.\n\n### Siguientes checkpoints previstos",
    'U0.9 roadmap section',
)

library = Path('docs/product/master-feature-library.md')
replace_once(
    library,
    "- `auth.logout`.\n\n`auth.login` y `auth.logout` forman el ciclo mínimo canónico de autenticación.",
    "- `auth.logout`;\n- `users.list`.\n\n`auth.login` y `auth.logout` forman el ciclo mínimo canónico de autenticación.",
    'feature list users',
)
replace_once(
    library,
    "Cuando una composición selecciona `auth.login`, la estrategia `authentication=none` se reconcilia a `sanctum` y el ajuste queda registrado en el manifest resuelto. `auth.logout` depende de `auth.login` y revoca únicamente el token Sanctum actual, reutilizando la misma identidad, tabla de usuarios y almacenamiento de tokens.\n",
    "Cuando una composición selecciona `auth.login`, la estrategia `authentication=none` se reconcilia a `sanctum` y el ajuste queda registrado en el manifest resuelto. `auth.logout` depende de `auth.login` y revoca únicamente el token Sanctum actual, reutilizando la misma identidad, tabla de usuarios y almacenamiento de tokens.\n\n`users.list` reutiliza esa misma identidad y el gate `admin-api`. La paginación cursor/offset y la validación estructural de listados son infraestructura compartida con `products.list`; una nueva feature de listado no debe copiar esos algoritmos.\n",
    'users reuse documentation',
)
replace_once(
    library,
    "Swagger no es documentación de cierre: se actualiza en el mismo cambio que termina una feature. `auth.login` publica su request body, respuesta con token Bearer y errores 401/422 desde el mismo checkpoint que lo vuelve ejecutable.",
    "Swagger no es documentación de cierre: se actualiza en el mismo cambio que termina una feature. `auth.login` publica su request body, respuesta con token Bearer y errores 401/422 desde el mismo checkpoint que lo vuelve ejecutable. `users.list` publica filtros, sorting, paginación, esquema seguro de usuario y errores 401/403/422 en el mismo cambio que lo incorpora a la biblioteca.",
    'swagger users documentation',
)

root_test = Path('tests/Feature/GeneratedUsersListVerticalSliceExportTest.php')
root_test.write_text(r'''<?php

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
''')

print('U0.9 metadata and root acceptance patch applied')
