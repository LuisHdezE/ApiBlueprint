from pathlib import Path


def replace_once(path: str, old: str, new: str) -> None:
    file = Path(path)
    text = file.read_text()
    if text.count(old) != 1:
        raise SystemExit(f"Expected exactly one match in {path}, found {text.count(old)}")
    file.write_text(text.replace(old, new, 1))


# 1. Canonical feature metadata.
replace_once(
    "config/blueprint.php",
    "    ['id' => 'auth.login', 'capability' => 'authentication', 'capability_label' => 'Autenticación', 'summary' => 'Iniciar sesión', 'method' => 'POST', 'path' => '/api/v1/auth/login', 'default_exposure' => 'public'],",
    "    ['id' => 'auth.login', 'capability' => 'authentication', 'capability_label' => 'Autenticación', 'summary' => 'Iniciar sesión', 'method' => 'POST', 'path' => '/api/v1/auth/login', 'default_exposure' => 'public', 'implementation_status' => 'implemented', 'exportable' => true, 'openapi_ready' => true, 'tests_ready' => true],",
)

# 2. Selecting login with authentication=none must produce a coherent Sanctum solution.
replace_once(
    "app/Application/Blueprint/Services/ResolveBlueprintManifest.php",
    "        $this->validateGovernanceAgainstSurface($governance, $selected, $errors);\n        $governanceAdjustments = [];\n\n        if (isset($selected['audit.list']) && $governance['audit'] === false) {",
    "        $governanceAdjustments = [];\n\n        if (isset($selected['auth.login']) && $governance['authentication'] === 'none') {\n            $governance['authentication'] = 'sanctum';\n            $governanceAdjustments[] = [\n                'capability' => 'authentication',\n                'reason' => 'El inicio de sesión requiere Laravel Sanctum como estrategia de autenticación.',\n            ];\n        }\n\n        $this->validateGovernanceAgainstSurface($governance, $selected, $errors);\n\n        if (isset($selected['audit.list']) && $governance['audit'] === false) {",
)

# 3. Exporter wiring and generated files.
exporter = "app/Infrastructure/Blueprint/LaravelZipBlueprintExporter.php"
replace_once(
    exporter,
    "        $hasProductsShow = $this->hasEndpoint($manifest, 'products.show');\n        $hasProductsList = $this->hasEndpoint($manifest, 'products.list');\n\n        if ($hasProductsShow || $hasProductsList) {",
    "        $hasAuthLogin = $this->hasEndpoint($manifest, 'auth.login');\n        $hasProductsShow = $this->hasEndpoint($manifest, 'products.show');\n        $hasProductsList = $this->hasEndpoint($manifest, 'products.list');\n\n        if ($hasAuthLogin) {\n            $files['database/database.sqlite'] = '';\n            $files['database/migrations/2026_01_01_000000_create_users_table.php'] = $this->usersMigrationFile();\n            $files['database/migrations/2026_01_01_000001_create_personal_access_tokens_table.php'] = $this->personalAccessTokensMigrationFile();\n            $files['app/Infrastructure/Identity/User.php'] = $this->userModelFile();\n            $files['app/Application/Authentication/Data/AuthenticatedSession.php'] = $this->authenticatedSessionFile();\n            $files['app/Application/Authentication/Contracts/AuthenticationGateway.php'] = $this->authenticationGatewayContractFile();\n            $files['app/Application/Authentication/UseCases/LoginUser.php'] = $this->loginUserUseCaseFile();\n            $files['app/Infrastructure/Authentication/SanctumAuthenticationGateway.php'] = $this->sanctumAuthenticationGatewayFile();\n            $files['app/Presentation/Http/Support/LoginRequestValidator.php'] = $this->loginRequestValidatorFile();\n            $files['tests/Feature/AuthLoginVerticalSliceTest.php'] = $this->authLoginVerticalSliceTestFile();\n        }\n\n        if ($hasProductsShow || $hasProductsList) {",
)

replace_once(
    exporter,
    "        if ($this->hasEndpoint($manifest, 'products.show') || $this->hasEndpoint($manifest, 'products.list')) {\n            $requireDev['mockery/mockery'] = '^1.6';\n        }",
    "        if ($this->hasEndpoint($manifest, 'auth.login') || $this->hasEndpoint($manifest, 'products.show') || $this->hasEndpoint($manifest, 'products.list')) {\n            $requireDev['mockery/mockery'] = '^1.6';\n        }",
)

replace_once(
    exporter,
    "        if ($this->hasEndpoint($manifest, 'products.show')) {\n            $imports[] = 'use App\\\\Application\\\\Products\\\\Contracts\\\\ProductReadRepository;';",
    "        if ($this->hasEndpoint($manifest, 'auth.login')) {\n            $imports[] = 'use App\\\\Application\\\\Authentication\\\\Contracts\\\\AuthenticationGateway;';\n            $imports[] = 'use App\\\\Infrastructure\\\\Authentication\\\\SanctumAuthenticationGateway;';\n            $registerLines[] = '        $this->app->bind(AuthenticationGateway::class, SanctumAuthenticationGateway::class);';\n        }\n        if ($this->hasEndpoint($manifest, 'products.show')) {\n            $imports[] = 'use App\\\\Application\\\\Products\\\\Contracts\\\\ProductReadRepository;';",
)

replace_once(
    exporter,
    "            static fn (array $endpoint): bool => ! in_array($endpoint['id'], ['products.list', 'products.show'], true),",
    "            static fn (array $endpoint): bool => ! in_array($endpoint['id'], ['auth.login', 'products.list', 'products.show'], true),",
)

replace_once(
    exporter,
    "        $databaseEnvironment = ($this->hasEndpoint($manifest, 'products.show') || $this->hasEndpoint($manifest, 'products.list'))",
    "        $databaseEnvironment = ($this->hasEndpoint($manifest, 'auth.login') || $this->hasEndpoint($manifest, 'products.show') || $this->hasEndpoint($manifest, 'products.list'))",
)

replace_once(
    exporter,
    "        if ($endpoint['id'] === 'products.list') {\n            return $this->productsListControllerFile($className);\n        }",
    "        if ($endpoint['id'] === 'auth.login') {\n            return $this->authLoginControllerFile($className);\n        }\n        if ($endpoint['id'] === 'products.list') {\n            return $this->productsListControllerFile($className);\n        }",
)

# Generated OpenAPI request contract.
replace_once(
    exporter,
    "                    if ($endpoint['exposure'] !== 'public' && $governance['authentication'] === 'sanctum') {\n                        $lines[] = '      security:';\n                        $lines[] = '        - bearerAuth: []';\n                    }\n\n                    $parameters = [];",
    "                    if ($endpoint['exposure'] !== 'public' && $governance['authentication'] === 'sanctum') {\n                        $lines[] = '      security:';\n                        $lines[] = '        - bearerAuth: []';\n                    }\n\n                    if ($endpoint['id'] === 'auth.login') {\n                        $lines[] = '      requestBody:';\n                        $lines[] = '        required: true';\n                        $lines[] = '        content:';\n                        $lines[] = '          application/json:';\n                        $lines[] = '            schema: { $ref: \"#/components/schemas/AuthLoginRequest\" }';\n                    }\n\n                    $parameters = [];",
)

replace_once(
    exporter,
    "                    $lines[] = '      responses:';\n                    if ($endpoint['id'] === 'products.list') {",
    "                    $lines[] = '      responses:';\n                    if ($endpoint['id'] === 'auth.login') {\n                        $lines[] = \"        '200':\";\n                        $lines[] = '          description: \"Sesión iniciada correctamente.\"';\n                        $lines[] = '          content: { application/json: { schema: { $ref: \"#/components/schemas/AuthLoginResponse\" } } }';\n                        $lines[] = \"        '401':\";\n                        $lines[] = '          description: \"Credenciales inválidas.\"';\n                        $lines[] = '          content: { application/problem+json: { schema: { $ref: \"#/components/schemas/ProblemDetails\" } } }';\n                        $lines[] = \"        '422':\";\n                        $lines[] = '          description: \"Datos de inicio de sesión inválidos.\"';\n                        $lines[] = '          content: { application/problem+json: { schema: { $ref: \"#/components/schemas/ProblemDetails\" } } }';\n                    } elseif ($endpoint['id'] === 'products.list') {",
)

replace_once(
    exporter,
    "        if ($this->hasEndpoint($manifest, 'products.show') || $this->hasEndpoint($manifest, 'products.list')) {\n            $lines[] = '    Product:';",
    "        if ($this->hasEndpoint($manifest, 'auth.login')) {\n            $lines[] = '    AuthLoginRequest:';\n            $lines[] = '      type: object';\n            $lines[] = '      required: [email, password]';\n            $lines[] = '      properties:';\n            $lines[] = '        email: { type: string, format: email }';\n            $lines[] = '        password: { type: string, format: password }';\n            $lines[] = '        device_name: { type: string, maxLength: 100 }';\n            $lines[] = '    AuthLoginResponse:';\n            $lines[] = '      type: object';\n            $lines[] = '      required: [data]';\n            $lines[] = '      properties:';\n            $lines[] = '        data:';\n            $lines[] = '          type: object';\n            $lines[] = '          required: [user, access_token, token_type]';\n            $lines[] = '          properties:';\n            $lines[] = '            user:';\n            $lines[] = '              type: object';\n            $lines[] = '              required: [id, name, email]';\n            $lines[] = '              properties:';\n            $lines[] = '                id: { type: string }';\n            $lines[] = '                name: { type: string }';\n            $lines[] = '                email: { type: string, format: email }';\n            $lines[] = '            access_token: { type: string }';\n            $lines[] = '            token_type: { type: string, enum: [Bearer] }';\n        }\n\n        if ($this->hasEndpoint($manifest, 'products.show') || $this->hasEndpoint($manifest, 'products.list')) {\n            $lines[] = '    Product:';",
)

# README recognizes login as executable and database-backed.
replace_once(
    exporter,
    "            $status = in_array($endpoint['id'], ['products.list', 'products.show'], true) ? 'Ejecutable' : 'Stub 501';",
    "            $status = in_array($endpoint['id'], ['auth.login', 'products.list', 'products.show'], true) ? 'Ejecutable' : 'Stub 501';",
)
replace_once(
    exporter,
    "        $migrationStep = ($this->hasEndpoint($manifest, 'products.show') || $this->hasEndpoint($manifest, 'products.list')) ? \"php artisan migrate\\n\" : '';",
    "        $migrationStep = ($this->hasEndpoint($manifest, 'auth.login') || $this->hasEndpoint($manifest, 'products.show') || $this->hasEndpoint($manifest, 'products.list')) ? \"php artisan migrate\\n\" : '';",
)
replace_once(
    exporter,
    "`products.list` y `products.show` se exportan como vertical slices ejecutables cuando están seleccionados. Los demás endpoints conservan HTTP 501 hasta que su receta ejecutable sea incorporada.",
    "`auth.login`, `products.list` y `products.show` se exportan como vertical slices ejecutables cuando están seleccionados. Los demás endpoints conservan HTTP 501 hasta que su receta ejecutable sea incorporada.",
)

# Auth generated templates.
auth_methods = r'''    private function authLoginControllerFile(string $className): string
    {
        return <<<PHP
<?php

namespace App\Presentation\Http\Controllers\Generated;

use App\Application\Authentication\UseCases\LoginUser;
use App\Presentation\Http\Support\LoginRequestValidator;
use App\Presentation\Http\Support\ProblemDetails;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class $className
{
    public function __construct(
        private LoginUser \$loginUser,
        private LoginRequestValidator \$validator,
    ) {
        //
    }

    public function __invoke(Request \$request): JsonResponse
    {
        \$credentials = \$this->validator->validate(\$request);
        \$session = \$this->loginUser->handle(
            email: \$credentials['email'],
            password: \$credentials['password'],
            tokenName: \$credentials['device_name'] ?? 'api-client',
        );

        if (\$session === null) {
            return ProblemDetails::response(
                request: \$request,
                status: 401,
                title: 'Credenciales inválidas',
                detail: 'El correo electrónico o la contraseña no son correctos.',
                type: 'https://eliasworks.uy/problems/invalid-credentials',
            );
        }

        return response()->json(['data' => \$session->toArray()]);
    }
}
PHP;
    }

    private function loginRequestValidatorFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Presentation\Http\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

final class LoginRequestValidator
{
    public function validate(Request $request): array
    {
        return Validator::make($request->all(), [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'device_name' => ['sometimes', 'string', 'max:100'],
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
            'password.required' => 'La contraseña es obligatoria.',
            'device_name.max' => 'El nombre del dispositivo no puede superar 100 caracteres.',
        ])->validate();
    }
}
PHP;
    }

    private function authenticatedSessionFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Application\Authentication\Data;

final readonly class AuthenticatedSession
{
    public function __construct(
        public string $userId,
        public string $name,
        public string $email,
        public string $accessToken,
    ) {
        //
    }

    public function toArray(): array
    {
        return [
            'user' => [
                'id' => $this->userId,
                'name' => $this->name,
                'email' => $this->email,
            ],
            'access_token' => $this->accessToken,
            'token_type' => 'Bearer',
        ];
    }
}
PHP;
    }

    private function authenticationGatewayContractFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Application\Authentication\Contracts;

use App\Application\Authentication\Data\AuthenticatedSession;

interface AuthenticationGateway
{
    public function authenticate(string $email, string $password, string $tokenName): ?AuthenticatedSession;
}
PHP;
    }

    private function loginUserUseCaseFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Application\Authentication\UseCases;

use App\Application\Authentication\Contracts\AuthenticationGateway;
use App\Application\Authentication\Data\AuthenticatedSession;

final readonly class LoginUser
{
    public function __construct(private AuthenticationGateway $authentication)
    {
        //
    }

    public function handle(string $email, string $password, string $tokenName): ?AuthenticatedSession
    {
        return $this->authentication->authenticate(
            email: mb_strtolower(trim($email)),
            password: $password,
            tokenName: trim($tokenName) === '' ? 'api-client' : trim($tokenName),
        );
    }
}
PHP;
    }

    private function sanctumAuthenticationGatewayFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Infrastructure\Authentication;

use App\Application\Authentication\Contracts\AuthenticationGateway;
use App\Application\Authentication\Data\AuthenticatedSession;
use App\Infrastructure\Identity\User;
use Illuminate\Support\Facades\Hash;

final class SanctumAuthenticationGateway implements AuthenticationGateway
{
    public function authenticate(string $email, string $password, string $tokenName): ?AuthenticatedSession
    {
        $user = User::query()->where('email', $email)->first();
        if ($user === null || ! Hash::check($password, (string) $user->password)) {
            return null;
        }

        $token = $user->createToken($tokenName);

        return new AuthenticatedSession(
            userId: (string) $user->getKey(),
            name: (string) $user->name,
            email: (string) $user->email,
            accessToken: $token->plainTextToken,
        );
    }
}
PHP;
    }

    private function userModelFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Infrastructure\Identity;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

final class User extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
    ];
}
PHP;
    }

    private function usersMigrationFile(): string
    {
        return <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('user');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
PHP;
    }

    private function personalAccessTokensMigrationFile(): string
    {
        return <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->morphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
PHP;
    }

    private function authLoginVerticalSliceTestFile(): string
    {
        return <<<'PHP'
<?php

namespace Tests\Feature;

use App\Infrastructure\Identity\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

final class AuthLoginVerticalSliceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_receive_a_real_sanctum_token(): void
    {
        $user = User::query()->create([
            'name' => 'Usuario de prueba',
            'email' => 'user@example.com',
            'password' => Hash::make('secret-password'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'USER@example.com',
            'password' => 'secret-password',
            'device_name' => 'integration-test',
        ])->assertOk()
            ->assertJsonPath('data.user.id', (string) $user->getKey())
            ->assertJsonPath('data.user.email', 'user@example.com')
            ->assertJsonPath('data.token_type', 'Bearer');

        $plainTextToken = $response->json('data.access_token');
        $this->assertIsString($plainTextToken);
        $this->assertNotSame('', $plainTextToken);

        $storedToken = PersonalAccessToken::findToken($plainTextToken);
        $this->assertNotNull($storedToken);
        $this->assertSame((string) $user->getKey(), (string) $storedToken->tokenable_id);
    }

    public function test_invalid_credentials_use_problem_details(): void
    {
        User::query()->create([
            'name' => 'Usuario de prueba',
            'email' => 'user@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(401)
            ->assertHeader('content-type', 'application/problem+json')
            ->assertJsonPath('title', 'Credenciales inválidas');
    }

    public function test_login_validates_required_credentials_in_spanish(): void
    {
        $this->postJson('/api/v1/auth/login', [])
            ->assertStatus(422)
            ->assertHeader('content-type', 'application/problem+json')
            ->assertJsonPath('title', 'Error de validación')
            ->assertJsonPath('errors.email.0', 'El correo electrónico es obligatorio.')
            ->assertJsonPath('errors.password.0', 'La contraseña es obligatoria.');
    }
}
PHP;
    }

'''
replace_once(
    exporter,
    "    private function productsListControllerFile(string $className): string\n    {",
    auth_methods + "    private function productsListControllerFile(string $className): string\n    {",
)

# 4. Master OpenAPI: request, responses, schemas and accurate bearer format.
master_openapi = "app/Application/Blueprint/Queries/GetMasterOpenApi.php"
replace_once(
    master_openapi,
    "            if ($feature['default_exposure'] !== 'public') {\n                $operation['security'] = [['bearerAuth' => []]];\n            }\n\n            $paths[$path][$method] = $operation;",
    "            if ($feature['id'] === 'auth.login') {\n                $operation['requestBody'] = [\n                    'required' => true,\n                    'content' => [\n                        'application/json' => [\n                            'schema' => ['$ref' => '#/components/schemas/AuthLoginRequest'],\n                        ],\n                    ],\n                ];\n            }\n\n            if ($feature['default_exposure'] !== 'public') {\n                $operation['security'] = [['bearerAuth' => []]];\n            }\n\n            $paths[$path][$method] = $operation;",
)
replace_once(
    master_openapi,
    "                        'bearerFormat' => 'JWT',",
    "                        'bearerFormat' => 'Sanctum personal access token',",
)
replace_once(
    master_openapi,
    "                    'ProblemDetails' => [\n                        'type' => 'object',",
    "                    'AuthLoginRequest' => [\n                        'type' => 'object',\n                        'required' => ['email', 'password'],\n                        'properties' => [\n                            'email' => ['type' => 'string', 'format' => 'email'],\n                            'password' => ['type' => 'string', 'format' => 'password'],\n                            'device_name' => ['type' => 'string', 'maxLength' => 100],\n                        ],\n                    ],\n                    'AuthLoginResponse' => [\n                        'type' => 'object',\n                        'required' => ['data'],\n                        'properties' => [\n                            'data' => [\n                                'type' => 'object',\n                                'required' => ['user', 'access_token', 'token_type'],\n                                'properties' => [\n                                    'user' => [\n                                        'type' => 'object',\n                                        'required' => ['id', 'name', 'email'],\n                                        'properties' => [\n                                            'id' => ['type' => 'string'],\n                                            'name' => ['type' => 'string'],\n                                            'email' => ['type' => 'string', 'format' => 'email'],\n                                        ],\n                                    ],\n                                    'access_token' => ['type' => 'string'],\n                                    'token_type' => ['type' => 'string', 'enum' => ['Bearer']],\n                                ],\n                            ],\n                        ],\n                    ],\n                    'ProblemDetails' => [\n                        'type' => 'object',",
)
replace_once(
    master_openapi,
    "    private function responsesFor(array $feature): array\n    {\n        if (str_ends_with($feature['id'], '.list')) {",
    "    private function responsesFor(array $feature): array\n    {\n        if ($feature['id'] === 'auth.login') {\n            return [\n                '200' => [\n                    'description' => 'Sesión iniciada correctamente.',\n                    'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/AuthLoginResponse']]],\n                ],\n                '401' => [\n                    'description' => 'Credenciales inválidas.',\n                    'content' => ['application/problem+json' => ['schema' => ['$ref' => '#/components/schemas/ProblemDetails']]],\n                ],\n                '422' => [\n                    'description' => 'Datos de inicio de sesión inválidos.',\n                    'content' => ['application/problem+json' => ['schema' => ['$ref' => '#/components/schemas/ProblemDetails']]],\n                ],\n            ];\n        }\n\n        if (str_ends_with($feature['id'], '.list')) {",
)

# 5. Catalog assertions evolve with the canonical feature.
master_test = "tests/Feature/MasterCatalogTest.php"
replace_once(master_test, "        $this->assertSame('planned', $features['auth.login']['implementation_status']);", "        $this->assertSame('implemented', $features['auth.login']['implementation_status']);\n        $this->assertTrue($features['auth.login']['exportable']);\n        $this->assertTrue($features['auth.login']['openapi_ready']);\n        $this->assertTrue($features['auth.login']['tests_ready']);")
replace_once(master_test, "        $this->assertSame(['implemented' => 2, 'total' => 9], $commerce['coverage']);", "        $this->assertSame(['implemented' => 3, 'total' => 9], $commerce['coverage']);")
replace_once(
    master_test,
    "        $this->assertArrayNotHasKey('/api/v1/auth/login', $paths);\n        $this->assertCount(2, $paths);",
    "        $this->assertSame('auth.login', $paths['/api/v1/auth/login']['post']['x-apiblueprint-feature-id']);\n        $this->assertSame('#/components/schemas/AuthLoginRequest', $paths['/api/v1/auth/login']['post']['requestBody']['content']['application/json']['schema']['$ref']);\n        $this->assertCount(3, $paths);",
)

print('U0.8 auth.login patch applied successfully.')
