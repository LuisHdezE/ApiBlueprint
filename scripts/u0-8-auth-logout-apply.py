from pathlib import Path


def replace_once(text: str, old: str, new: str, label: str) -> str:
    if new in text:
        return text
    if old not in text:
        raise SystemExit(f'missing marker: {label}')
    return text.replace(old, new, 1)

# config/blueprint.php
path = Path('config/blueprint.php')
text = path.read_text()
text = replace_once(
    text,
    "    ['id' => 'auth.logout', 'capability' => 'authentication', 'capability_label' => 'Autenticación', 'summary' => 'Cerrar sesión', 'method' => 'POST', 'path' => '/api/v1/auth/logout', 'default_exposure' => 'authenticated'],",
    "    ['id' => 'auth.logout', 'capability' => 'authentication', 'capability_label' => 'Autenticación', 'summary' => 'Cerrar sesión', 'method' => 'POST', 'path' => '/api/v1/auth/logout', 'default_exposure' => 'authenticated', 'implementation_status' => 'implemented', 'exportable' => true, 'openapi_ready' => true, 'tests_ready' => true],",
    'catalog auth.logout',
)
path.write_text(text)

# Master OpenAPI
path = Path('app/Application/Blueprint/Queries/GetMasterOpenApi.php')
text = path.read_text()
text = replace_once(
    text,
    "        if (str_ends_with($feature['id'], '.list')) {",
    "        if ($feature['id'] === 'auth.logout') {\n            return [\n                '204' => ['description' => 'Sesión cerrada correctamente.'],\n                '401' => [\n                    'description' => 'Token de acceso ausente o inválido.',\n                    'content' => ['application/problem+json' => ['schema' => ['$ref' => '#/components/schemas/ProblemDetails']]],\n                ],\n            ];\n        }\n\n        if (str_ends_with($feature['id'], '.list')) {",
    'master openapi logout responses',
)
path.write_text(text)

# Exporter
path = Path('app/Infrastructure/Blueprint/LaravelZipBlueprintExporter.php')
text = path.read_text()
text = replace_once(
    text,
    "        $hasAuthLogin = $this->hasEndpoint($manifest, 'auth.login');\n        $hasProductsShow = $this->hasEndpoint($manifest, 'products.show');",
    "        $hasAuthLogin = $this->hasEndpoint($manifest, 'auth.login');\n        $hasAuthLogout = $this->hasEndpoint($manifest, 'auth.logout');\n        $hasProductsShow = $this->hasEndpoint($manifest, 'products.show');",
    'has auth logout',
)
text = replace_once(
    text,
    "        if ($hasProductsShow || $hasProductsList) {",
    "        if ($hasAuthLogout) {\n            $files['app/Application/Authentication/Contracts/TokenRevocationGateway.php'] = $this->tokenRevocationGatewayContractFile();\n            $files['app/Application/Authentication/UseCases/LogoutUser.php'] = $this->logoutUserUseCaseFile();\n            $files['app/Infrastructure/Authentication/SanctumTokenRevocationGateway.php'] = $this->sanctumTokenRevocationGatewayFile();\n            $files['tests/Feature/AuthLogoutVerticalSliceTest.php'] = $this->authLogoutVerticalSliceTestFile();\n        }\n\n        if ($hasProductsShow || $hasProductsList) {",
    'logout files',
)
text = replace_once(
    text,
    "        if ($governance['idempotency'] && in_array($endpoint['method'], ['POST', 'PUT', 'PATCH'], true) && $endpoint['id'] !== 'auth.login') {",
    "        if ($governance['idempotency'] && in_array($endpoint['method'], ['POST', 'PUT', 'PATCH'], true) && ! in_array($endpoint['id'], ['auth.login', 'auth.logout'], true)) {",
    'idempotency auth exclusions',
)
text = replace_once(
    text,
    "        if ($endpoint['id'] === 'products.list') {",
    "        if ($endpoint['id'] === 'auth.logout') {\n            return $this->authLogoutControllerFile($className);\n        }\n        if ($endpoint['id'] === 'products.list') {",
    'logout controller dispatch',
)
text = replace_once(
    text,
    "        if ($this->hasEndpoint($manifest, 'products.show')) {",
    "        if ($this->hasEndpoint($manifest, 'auth.logout')) {\n            $imports[] = 'use App\\\\Application\\\\Authentication\\\\Contracts\\\\TokenRevocationGateway;';\n            $imports[] = 'use App\\\\Infrastructure\\\\Authentication\\\\SanctumTokenRevocationGateway;';\n            $registerLines[] = '        $this->app->bind(TokenRevocationGateway::class, SanctumTokenRevocationGateway::class);';\n        }\n        if ($this->hasEndpoint($manifest, 'products.show')) {",
    'logout service provider',
)
text = text.replace("['auth.login', 'products.list', 'products.show']", "['auth.login', 'auth.logout', 'products.list', 'products.show']")
text = replace_once(
    text,
    "                    } elseif ($endpoint['id'] === 'products.list') {",
    "                    } elseif ($endpoint['id'] === 'auth.logout') {\n                        $lines[] = \"        '204':\";\n                        $lines[] = '          description: \"Sesión cerrada correctamente.\"';\n                        $lines[] = \"        '401':\";\n                        $lines[] = '          description: \"Token de acceso ausente o inválido.\"';\n                        $lines[] = '          content: { application/problem+json: { schema: { $ref: \"#/components/schemas/ProblemDetails\" } } }';\n                    } elseif ($endpoint['id'] === 'products.list') {",
    'generated openapi logout responses',
)
text = replace_once(
    text,
    "`auth.login`, `products.list` y `products.show` se exportan como vertical slices ejecutables cuando están seleccionados.",
    "`auth.login`, `auth.logout`, `products.list` y `products.show` se exportan como vertical slices ejecutables cuando están seleccionados.",
    'readme executable sentence',
)
methods = r'''
    private function authLogoutControllerFile(string $className): string
    {
        return <<<PHP
<?php

namespace App\Presentation\Http\Controllers\Generated;

use App\Application\Authentication\UseCases\LogoutUser;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class $className
{
    public function __construct(private LogoutUser \$logoutUser)
    {
        //
    }

    public function __invoke(Request \$request): Response
    {
        \$token = (string) \$request->bearerToken();
        \$this->logoutUser->handle(\$token);

        return response()->noContent();
    }
}
PHP;
    }

    private function tokenRevocationGatewayContractFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Application\Authentication\Contracts;

interface TokenRevocationGateway
{
    public function revoke(string $plainTextToken): bool;
}
PHP;
    }

    private function logoutUserUseCaseFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Application\Authentication\UseCases;

use App\Application\Authentication\Contracts\TokenRevocationGateway;

final readonly class LogoutUser
{
    public function __construct(private TokenRevocationGateway $tokens)
    {
        //
    }

    public function handle(string $plainTextToken): bool
    {
        return $plainTextToken !== '' && $this->tokens->revoke($plainTextToken);
    }
}
PHP;
    }

    private function sanctumTokenRevocationGatewayFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Infrastructure\Authentication;

use App\Application\Authentication\Contracts\TokenRevocationGateway;
use Laravel\Sanctum\PersonalAccessToken;

final class SanctumTokenRevocationGateway implements TokenRevocationGateway
{
    public function revoke(string $plainTextToken): bool
    {
        $token = PersonalAccessToken::findToken($plainTextToken);
        if ($token === null) {
            return false;
        }

        return (bool) $token->delete();
    }
}
PHP;
    }

    private function authLogoutVerticalSliceTestFile(): string
    {
        return <<<'PHP'
<?php

namespace Tests\Feature;

use App\Infrastructure\Identity\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

final class AuthLogoutVerticalSliceTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_sanctum_token_is_revoked_without_affecting_the_authentication_model(): void
    {
        $user = User::query()->create([
            'name' => 'Usuario de prueba',
            'email' => 'logout@example.com',
            'password' => Hash::make('secret-password'),
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'logout@example.com',
            'password' => 'secret-password',
            'device_name' => 'logout-test',
        ])->assertOk();

        $plainTextToken = $login->json('data.access_token');
        $this->assertIsString($plainTextToken);
        $this->assertNotNull(PersonalAccessToken::findToken($plainTextToken));

        $this->withToken($plainTextToken)
            ->postJson('/api/v1/auth/logout')
            ->assertNoContent();

        $this->assertNull(PersonalAccessToken::findToken($plainTextToken));
        $this->assertSame((string) $user->getKey(), (string) User::query()->findOrFail($user->getKey())->getKey());

        $this->withToken($plainTextToken)
            ->postJson('/api/v1/auth/logout')
            ->assertUnauthorized();
    }
}
PHP;
    }

'''
marker = "    private function productsListControllerFile(string $className): string\n"
if methods not in text:
    if marker not in text:
        raise SystemExit('missing marker: logout methods insertion')
    text = text.replace(marker, methods + marker, 1)
path.write_text(text)

# Master catalog tests
path = Path('tests/Feature/MasterCatalogTest.php')
text = path.read_text()
text = replace_once(
    text,
    "        $this->assertTrue($features['auth.login']['tests_ready']);\n\n        $commerce =",
    "        $this->assertTrue($features['auth.login']['tests_ready']);\n        $this->assertSame('implemented', $features['auth.logout']['implementation_status']);\n        $this->assertTrue($features['auth.logout']['exportable']);\n        $this->assertTrue($features['auth.logout']['openapi_ready']);\n        $this->assertTrue($features['auth.logout']['tests_ready']);\n\n        $saas = collect($response->json('applications'))->firstWhere('id', 'saas');\n        $this->assertSame('partial', $saas['status']);\n        $this->assertSame(['implemented' => 2, 'total' => 8], $saas['coverage']);\n\n        $commerce =",
    'catalog logout assertions',
)
text = replace_once(
    text,
    "        $this->assertSame('#/components/schemas/AuthLoginRequest', $paths['/api/v1/auth/login']['post']['requestBody']['content']['application/json']['schema']['$ref']);\n        $this->assertCount(3, $paths);",
    "        $this->assertSame('#/components/schemas/AuthLoginRequest', $paths['/api/v1/auth/login']['post']['requestBody']['content']['application/json']['schema']['$ref']);\n        $this->assertSame('auth.logout', $paths['/api/v1/auth/logout']['post']['x-apiblueprint-feature-id']);\n        $this->assertSame([['bearerAuth' => []]], $paths['/api/v1/auth/logout']['post']['security']);\n        $this->assertArrayHasKey('204', $paths['/api/v1/auth/logout']['post']['responses']);\n        $this->assertCount(4, $paths);",
    'master openapi logout assertions',
)
path.write_text(text)

# Root isolated export test
Path('tests/Feature/GeneratedAuthLogoutVerticalSliceExportTest.php').write_text(r'''<?php

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
''')

# docs
path = Path('docs/delivery/roadmap.md')
text = path.read_text()
text = replace_once(
    text,
    "### Checkpoint 1 - `auth.login` 🚧",
    "### Checkpoint 1 - `auth.login` ✅",
    'roadmap login complete',
)
text = replace_once(
    text,
    "### Siguientes checkpoints previstos\n\n- `auth.logout` reutilizando la misma identidad y tokens;",
    "### Checkpoint 2 - `auth.logout` 🚧\n\n- una única feature `auth.logout` reutilizable por cualquier aplicación;\n- dependencia explícita de `auth.login`, sin identidad ni migraciones duplicadas;\n- revocación exclusiva del token Sanctum actual;\n- `TokenRevocationGateway` y `LogoutUser` independientes de Laravel;\n- `SanctumTokenRevocationGateway` como adaptador;\n- endpoint protegido con 204 y 401 documentados;\n- login/logout excluidos de idempotency key;\n- Swagger, catálogo y tests actualizados en el mismo checkpoint;\n- acceptance ejecutando login → logout → token revocado.\n\n### Siguientes checkpoints previstos",
    'roadmap logout checkpoint',
)
path.write_text(text)

path = Path('docs/product/master-feature-library.md')
text = path.read_text()
text = replace_once(
    text,
    "- `auth.login`.\n\n`auth.login` es una única feature canónica compartida",
    "- `auth.login`;\n- `auth.logout`.\n\n`auth.login` y `auth.logout` forman el ciclo mínimo canónico de autenticación. `auth.login` es una única feature compartida",
    'library feature list',
)
text = replace_once(
    text,
    "Cuando una composición selecciona `auth.login`, la estrategia `authentication=none` se reconcilia a `sanctum` y el ajuste queda registrado en el manifest resuelto.",
    "Cuando una composición selecciona `auth.login`, la estrategia `authentication=none` se reconcilia a `sanctum` y el ajuste queda registrado en el manifest resuelto. `auth.logout` depende de `auth.login` y revoca únicamente el token Sanctum actual, reutilizando la misma identidad, tabla de usuarios y almacenamiento de tokens.",
    'library logout semantics',
)
path.write_text(text)

print('U0.8 auth.logout patch applied')
