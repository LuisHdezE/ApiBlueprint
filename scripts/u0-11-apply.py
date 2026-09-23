from pathlib import Path


def replace_once(path: str, old: str, new: str) -> None:
    p = Path(path)
    text = p.read_text()
    count = text.count(old)
    if count != 1:
        raise SystemExit(f"{path}: expected one match, found {count}: {old[:120]!r}")
    p.write_text(text.replace(old, new, 1))


# Canonical catalog
replace_once(
    'config/blueprint.php',
    "    ['id' => 'users.create', 'capability' => 'users', 'capability_label' => 'Usuarios', 'summary' => 'Crear usuario', 'method' => 'POST', 'path' => '/api/v1/users', 'default_exposure' => 'admin'],",
    "    ['id' => 'users.create', 'capability' => 'users', 'capability_label' => 'Usuarios', 'summary' => 'Crear usuario', 'method' => 'POST', 'path' => '/api/v1/users', 'default_exposure' => 'admin', 'implementation_status' => 'implemented', 'exportable' => true, 'openapi_ready' => true, 'tests_ready' => true],",
)

# Master OpenAPI
replace_once(
    'app/Application/Blueprint/Queries/GetMasterOpenApi.php',
    "            if ($feature['id'] === 'auth.login') {\n                $operation['requestBody'] = [\n                    'required' => true,\n                    'content' => [\n                        'application/json' => [\n                            'schema' => ['$ref' => '#/components/schemas/AuthLoginRequest'],\n                        ],\n                    ],\n                ];\n            }",
    "            if ($feature['id'] === 'auth.login') {\n                $operation['requestBody'] = [\n                    'required' => true,\n                    'content' => [\n                        'application/json' => [\n                            'schema' => ['$ref' => '#/components/schemas/AuthLoginRequest'],\n                        ],\n                    ],\n                ];\n            } elseif ($feature['id'] === 'users.create') {\n                $operation['requestBody'] = [\n                    'required' => true,\n                    'content' => [\n                        'application/json' => [\n                            'schema' => ['$ref' => '#/components/schemas/CreateUserRequest'],\n                        ],\n                    ],\n                ];\n            }",
)

replace_once(
    'app/Application/Blueprint/Queries/GetMasterOpenApi.php',
    "                    'UserData' => [\n                        'type' => 'object',",
    "                    'CreateUserRequest' => [\n                        'type' => 'object',\n                        'required' => ['name', 'email', 'password'],\n                        'properties' => [\n                            'name' => ['type' => 'string', 'maxLength' => 120],\n                            'email' => ['type' => 'string', 'format' => 'email', 'maxLength' => 255],\n                            'password' => ['type' => 'string', 'format' => 'password', 'minLength' => 8],\n                            'role' => ['type' => 'string', 'enum' => ['user', 'admin'], 'default' => 'user'],\n                        ],\n                    ],\n                    'UserData' => [\n                        'type' => 'object',",
)

replace_once(
    'app/Application/Blueprint/Queries/GetMasterOpenApi.php',
    "        if ($feature['id'] === 'users.show') {\n            return [",
    "        if ($feature['id'] === 'users.create') {\n            return [\n                '201' => [\n                    'description' => 'Usuario creado correctamente.',\n                    'content' => [\n                        'application/json' => [\n                            'schema' => [\n                                'type' => 'object',\n                                'required' => ['data'],\n                                'properties' => [\n                                    'data' => ['$ref' => '#/components/schemas/UserData'],\n                                ],\n                            ],\n                        ],\n                    ],\n                ],\n                '401' => [\n                    'description' => 'Autenticación requerida.',\n                    'content' => ['application/problem+json' => ['schema' => ['$ref' => '#/components/schemas/ProblemDetails']]],\n                ],\n                '403' => [\n                    'description' => 'Se requieren privilegios de administrador.',\n                    'content' => ['application/problem+json' => ['schema' => ['$ref' => '#/components/schemas/ProblemDetails']]],\n                ],\n                '422' => [\n                    'description' => 'Datos de usuario inválidos.',\n                    'content' => ['application/problem+json' => ['schema' => ['$ref' => '#/components/schemas/ProblemDetails']]],\n                ],\n            ];\n        }\n\n        if ($feature['id'] === 'users.show') {\n            return [",
)

# Exporter flags and feature files
replace_once(
    'app/Infrastructure/Blueprint/LaravelZipBlueprintExporter.php',
    "        $hasUsersList = $this->hasEndpoint($manifest, 'users.list');\n        $hasUsersShow = $this->hasEndpoint($manifest, 'users.show');\n        $hasProductsShow = $this->hasEndpoint($manifest, 'products.show');",
    "        $hasUsersList = $this->hasEndpoint($manifest, 'users.list');\n        $hasUsersShow = $this->hasEndpoint($manifest, 'users.show');\n        $hasUsersCreate = $this->hasEndpoint($manifest, 'users.create');\n        $hasProductsShow = $this->hasEndpoint($manifest, 'products.show');",
)

replace_once(
    'app/Infrastructure/Blueprint/LaravelZipBlueprintExporter.php',
    "        if ($hasUsersList || $hasUsersShow) {\n            $files['app/Application/Users/Data/UserData.php'] = $this->userDataFile();\n        }",
    "        if ($hasUsersList || $hasUsersShow || $hasUsersCreate) {\n            $files['app/Application/Users/Data/UserData.php'] = $this->userDataFile();\n        }",
)

replace_once(
    'app/Infrastructure/Blueprint/LaravelZipBlueprintExporter.php',
    "        if ($hasUsersShow) {\n            $files['app/Application/Users/Contracts/UserReadRepository.php'] = $this->userReadRepositoryContractFile();\n            $files['app/Application/Users/UseCases/GetUser.php'] = $this->getUserUseCaseFile();\n            $files['app/Infrastructure/Users/DatabaseUserReadRepository.php'] = $this->databaseUserReadRepositoryFile();\n            $files['tests/Feature/UsersShowVerticalSliceTest.php'] = $this->usersShowVerticalSliceTestFile();\n        }",
    "        if ($hasUsersShow) {\n            $files['app/Application/Users/Contracts/UserReadRepository.php'] = $this->userReadRepositoryContractFile();\n            $files['app/Application/Users/UseCases/GetUser.php'] = $this->getUserUseCaseFile();\n            $files['app/Infrastructure/Users/DatabaseUserReadRepository.php'] = $this->databaseUserReadRepositoryFile();\n            $files['tests/Feature/UsersShowVerticalSliceTest.php'] = $this->usersShowVerticalSliceTestFile();\n        }\n\n        if ($hasUsersCreate) {\n            $files['app/Application/Users/Data/CreateUserData.php'] = $this->createUserDataFile();\n            $files['app/Application/Users/Contracts/UserCreateRepository.php'] = $this->userCreateRepositoryContractFile();\n            $files['app/Application/Users/UseCases/CreateUser.php'] = $this->createUserUseCaseFile();\n            $files['app/Infrastructure/Users/DatabaseUserCreateRepository.php'] = $this->databaseUserCreateRepositoryFile();\n            $files['app/Presentation/Http/Support/CreateUserRequestValidator.php'] = $this->createUserRequestValidatorFile();\n            $files['tests/Feature/UsersCreateVerticalSliceTest.php'] = $this->usersCreateVerticalSliceTestFile();\n        }",
)

for old, new in [
    ("$this->hasEndpoint($manifest, 'users.show') || $this->hasEndpoint($manifest, 'products.show')", "$this->hasEndpoint($manifest, 'users.show') || $this->hasEndpoint($manifest, 'users.create') || $this->hasEndpoint($manifest, 'products.show')"),
]:
    p = Path('app/Infrastructure/Blueprint/LaravelZipBlueprintExporter.php')
    text = p.read_text()
    if old not in text:
        raise SystemExit(f'exporter: missing composer/phpunit users.show anchor: {old}')
    p.write_text(text.replace(old, new))

replace_once(
    'app/Infrastructure/Blueprint/LaravelZipBlueprintExporter.php',
    "        if ($endpoint['id'] === 'users.show') {\n            return $this->usersShowControllerFile($className);\n        }\n        if ($endpoint['id'] === 'products.list') {",
    "        if ($endpoint['id'] === 'users.show') {\n            return $this->usersShowControllerFile($className);\n        }\n        if ($endpoint['id'] === 'users.create') {\n            return $this->usersCreateControllerFile($className);\n        }\n        if ($endpoint['id'] === 'products.list') {",
)

replace_once(
    'app/Infrastructure/Blueprint/LaravelZipBlueprintExporter.php',
    "        if ($this->hasEndpoint($manifest, 'users.show')) {\n            $imports[] = 'use App\\\\Application\\\\Users\\\\Contracts\\\\UserReadRepository;';\n            $imports[] = 'use App\\\\Infrastructure\\\\Users\\\\DatabaseUserReadRepository;';\n            $registerLines[] = '        $this->app->bind(UserReadRepository::class, DatabaseUserReadRepository::class);';\n        }\n        if ($this->hasEndpoint($manifest, 'products.show')) {",
    "        if ($this->hasEndpoint($manifest, 'users.show')) {\n            $imports[] = 'use App\\\\Application\\\\Users\\\\Contracts\\\\UserReadRepository;';\n            $imports[] = 'use App\\\\Infrastructure\\\\Users\\\\DatabaseUserReadRepository;';\n            $registerLines[] = '        $this->app->bind(UserReadRepository::class, DatabaseUserReadRepository::class);';\n        }\n        if ($this->hasEndpoint($manifest, 'users.create')) {\n            $imports[] = 'use App\\\\Application\\\\Users\\\\Contracts\\\\UserCreateRepository;';\n            $imports[] = 'use App\\\\Infrastructure\\\\Users\\\\DatabaseUserCreateRepository;';\n            $registerLines[] = '        $this->app->bind(UserCreateRepository::class, DatabaseUserCreateRepository::class);';\n        }\n        if ($this->hasEndpoint($manifest, 'products.show')) {",
)

p = Path('app/Infrastructure/Blueprint/LaravelZipBlueprintExporter.php')
text = p.read_text()
text = text.replace("['auth.login', 'auth.logout', 'users.list', 'users.show', 'products.list', 'products.show']", "['auth.login', 'auth.logout', 'users.list', 'users.show', 'users.create', 'products.list', 'products.show']")
p.write_text(text)

# Generated OpenAPI request/response/schema
replace_once(
    'app/Infrastructure/Blueprint/LaravelZipBlueprintExporter.php',
    "                    if ($endpoint['id'] === 'auth.login') {\n                        $lines[] = '      requestBody:';\n                        $lines[] = '        required: true';\n                        $lines[] = '        content:';\n                        $lines[] = '          application/json:';\n                        $lines[] = '            schema: { $ref: \\\"#/components/schemas/AuthLoginRequest\\\" }';\n                    }",
    "                    if ($endpoint['id'] === 'auth.login') {\n                        $lines[] = '      requestBody:';\n                        $lines[] = '        required: true';\n                        $lines[] = '        content:';\n                        $lines[] = '          application/json:';\n                        $lines[] = '            schema: { $ref: \\\"#/components/schemas/AuthLoginRequest\\\" }';\n                    } elseif ($endpoint['id'] === 'users.create') {\n                        $lines[] = '      requestBody:';\n                        $lines[] = '        required: true';\n                        $lines[] = '        content:';\n                        $lines[] = '          application/json:';\n                        $lines[] = '            schema: { $ref: \\\"#/components/schemas/CreateUserRequest\\\" }';\n                    }",
)

replace_once(
    'app/Infrastructure/Blueprint/LaravelZipBlueprintExporter.php',
    "                    } elseif ($endpoint['id'] === 'users.show') {\n                        $lines[] = \"        '200':\";",
    "                    } elseif ($endpoint['id'] === 'users.create') {\n                        $lines[] = \"        '201':\";\n                        $lines[] = '          description: \\\"Usuario creado correctamente.\\\"';\n                        $lines[] = '          content: { application/json: { schema: { type: object, required: [data], properties: { data: { $ref: \\\"#/components/schemas/UserData\\\" } } } } }';\n                        $lines[] = \"        '401':\";\n                        $lines[] = '          description: \\\"Autenticación requerida.\\\"';\n                        $lines[] = '          content: { application/problem+json: { schema: { $ref: \\\"#/components/schemas/ProblemDetails\\\" } } }';\n                        if ($governance['rbac']) {\n                            $lines[] = \"        '403':\";\n                            $lines[] = '          description: \\\"Se requieren privilegios de administrador.\\\"';\n                            $lines[] = '          content: { application/problem+json: { schema: { $ref: \\\"#/components/schemas/ProblemDetails\\\" } } }';\n                        }\n                        $lines[] = \"        '422':\";\n                        $lines[] = '          description: \\\"Datos de usuario inválidos.\\\"';\n                        $lines[] = '          content: { application/problem+json: { schema: { $ref: \\\"#/components/schemas/ProblemDetails\\\" } } }';\n                    } elseif ($endpoint['id'] === 'users.show') {\n                        $lines[] = \"        '200':\";",
)

replace_once(
    'app/Infrastructure/Blueprint/LaravelZipBlueprintExporter.php',
    "        if ($this->hasEndpoint($manifest, 'users.list') || $this->hasEndpoint($manifest, 'users.show')) {\n            $lines[] = '    UserData:';",
    "        if ($this->hasEndpoint($manifest, 'users.create')) {\n            $lines[] = '    CreateUserRequest:';\n            $lines[] = '      type: object';\n            $lines[] = '      required: [name, email, password]';\n            $lines[] = '      properties:';\n            $lines[] = '        name: { type: string, maxLength: 120 }';\n            $lines[] = '        email: { type: string, format: email, maxLength: 255 }';\n            $lines[] = '        password: { type: string, format: password, minLength: 8 }';\n            $lines[] = '        role: { type: string, enum: [user, admin], default: user }';\n        }\n        if ($this->hasEndpoint($manifest, 'users.list') || $this->hasEndpoint($manifest, 'users.show') || $this->hasEndpoint($manifest, 'users.create')) {\n            $lines[] = '    UserData:';",
)

# New generated feature recipe methods
marker = "    private function usersShowControllerFile(string $className): string\n    {"
methods = r'''    private function createUserDataFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Application\Users\Data;

final readonly class CreateUserData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public string $role = 'user',
    ) {
        //
    }
}
PHP;
    }

    private function userCreateRepositoryContractFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Application\Users\Contracts;

use App\Application\Users\Data\CreateUserData;
use App\Application\Users\Data\UserData;

interface UserCreateRepository
{
    public function create(CreateUserData $data): UserData;
}
PHP;
    }

    private function createUserUseCaseFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Application\Users\UseCases;

use App\Application\Users\Contracts\UserCreateRepository;
use App\Application\Users\Data\CreateUserData;
use App\Application\Users\Data\UserData;

final readonly class CreateUser
{
    public function __construct(private UserCreateRepository $users)
    {
        //
    }

    public function execute(CreateUserData $data): UserData
    {
        return $this->users->create($data);
    }
}
PHP;
    }

    private function databaseUserCreateRepositoryFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Infrastructure\Users;

use App\Application\Users\Contracts\UserCreateRepository;
use App\Application\Users\Data\CreateUserData;
use App\Application\Users\Data\UserData;
use App\Infrastructure\Identity\User;
use Illuminate\Support\Facades\Hash;

final class DatabaseUserCreateRepository implements UserCreateRepository
{
    public function create(CreateUserData $data): UserData
    {
        $user = new User;
        $user->name = $data->name;
        $user->email = $data->email;
        $user->password = Hash::make($data->password);
        $user->role = $data->role;
        $user->save();

        return new UserData(
            id: (string) $user->getKey(),
            name: (string) $user->name,
            email: (string) $user->email,
            role: (string) $user->role,
        );
    }
}
PHP;
    }

    private function createUserRequestValidatorFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Presentation\Http\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class CreateUserRequestValidator
{
    public function validate(Request $request): array
    {
        return Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'role' => ['sometimes', 'string', Rule::in(['user', 'admin'])],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no es válido.',
            'email.unique' => 'Ya existe un usuario con ese correo electrónico.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'role.in' => 'El rol debe ser user o admin.',
        ])->validate();
    }
}
PHP;
    }

    private function usersCreateControllerFile(string $className): string
    {
        return <<<PHP
<?php

namespace App\Presentation\Http\Controllers\Generated;

use App\Application\Users\Data\CreateUserData;
use App\Application\Users\UseCases\CreateUser;
use App\Presentation\Http\Support\CreateUserRequestValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class {$className}
{
    public function __construct(
        private readonly CreateUser \$createUser,
        private readonly CreateUserRequestValidator \$validator,
    ) {
        //
    }

    public function __invoke(Request \$request): JsonResponse
    {
        \$payload = \$this->validator->validate(\$request);
        \$user = \$this->createUser->execute(new CreateUserData(
            name: \$payload['name'],
            email: \$payload['email'],
            password: \$payload['password'],
            role: \$payload['role'] ?? 'user',
        ));

        return response()->json(['data' => \$user->toArray()], 201);
    }
}
PHP;
    }

    private function usersCreateVerticalSliceTestFile(): string
    {
        return <<<'PHP'
<?php

namespace Tests\Feature;

use App\Infrastructure\Identity\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class UsersCreateVerticalSliceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_user_without_exposing_password(): void
    {
        $admin = $this->user('admin', 'admin-create@example.test');
        $token = $admin->createToken('users-create-test')->plainTextToken;

        $response = $this->withToken($token)
            ->withHeader('Idempotency-Key', 'users-create-success')
            ->postJson('/api/v1/users', [
                'name' => 'Nuevo Usuario',
                'email' => 'nuevo@example.test',
                'password' => 'Password123!',
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Nuevo Usuario')
            ->assertJsonPath('data.email', 'nuevo@example.test')
            ->assertJsonPath('data.role', 'user')
            ->assertJsonMissingPath('data.password');

        $created = User::query()->where('email', 'nuevo@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('Password123!', (string) $created->password));
        $this->assertSame((string) $created->getKey(), $response->json('data.id'));
    }

    public function test_duplicate_email_uses_problem_details_in_spanish(): void
    {
        $admin = $this->user('admin', 'admin-duplicate@example.test');
        $this->user('user', 'duplicado@example.test');

        $this->withToken($admin->createToken('users-create-duplicate')->plainTextToken)
            ->withHeader('Idempotency-Key', 'users-create-duplicate')
            ->postJson('/api/v1/users', [
                'name' => 'Duplicado',
                'email' => 'duplicado@example.test',
                'password' => 'Password123!',
            ])
            ->assertStatus(422)
            ->assertHeader('content-type', 'application/problem+json')
            ->assertJsonPath('title', 'Error de validación')
            ->assertJsonPath('status', 422)
            ->assertJsonPath('errors.email.0', 'Ya existe un usuario con ese correo electrónico.');
    }

    public function test_non_admin_user_is_forbidden(): void
    {
        $user = $this->user('user', 'regular-create@example.test');

        $this->withToken($user->createToken('users-create-forbidden')->plainTextToken)
            ->withHeader('Idempotency-Key', 'users-create-forbidden')
            ->postJson('/api/v1/users', [
                'name' => 'No permitido',
                'email' => 'forbidden@example.test',
                'password' => 'Password123!',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'forbidden@example.test']);
    }

    private function user(string $role, string $email): User
    {
        $user = new User;
        $user->name = ucfirst($role).' Test';
        $user->email = $email;
        $user->password = Hash::make('Password123!');
        $user->role = $role;
        $user->save();

        return $user;
    }
}
PHP;
    }

'''
replace_once(
    'app/Infrastructure/Blueprint/LaravelZipBlueprintExporter.php',
    marker,
    methods + marker,
)

# Root export test
Path('tests/Feature/GeneratedUsersCreateVerticalSliceExportTest.php').write_text(r'''<?php

namespace Tests\Feature;

use Tests\TestCase;
use ZipArchive;

final class GeneratedUsersCreateVerticalSliceExportTest extends TestCase
{
    public function test_users_create_exports_one_canonical_admin_write_slice_without_dormant_read_or_list_infrastructure(): void
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
        $contract = $zip->getFromName('users-create-api/app/Application/Users/Contracts/UserCreateRepository.php');
        $useCase = $zip->getFromName('users-create-api/app/Application/Users/UseCases/CreateUser.php');
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
        $this->assertStringContainsString("response()->json(['data' => \\$user->toArray()], 201)", $controller);
        $this->assertStringNotContainsString("'status' => 501", $controller);

        $this->assertIsString($data);
        $this->assertStringContainsString('final readonly class UserData', $data);
        $this->assertStringNotContainsString('password', $data);
        $this->assertIsString($input);
        $this->assertStringContainsString('final readonly class CreateUserData', $input);
        $this->assertIsString($contract);
        $this->assertStringContainsString('interface UserCreateRepository', $contract);
        $this->assertIsString($useCase);
        $this->assertStringContainsString('final readonly class CreateUser', $useCase);
        $this->assertIsString($repository);
        $this->assertStringContainsString('Hash::make($data->password)', $repository);
        $this->assertIsString($validator);
        $this->assertStringContainsString("Rule::unique('users', 'email')", $validator);
        $this->assertStringContainsString("Rule::in(['user', 'admin'])", $validator);

        $this->assertIsString($provider);
        $this->assertStringContainsString('UserCreateRepository::class, DatabaseUserCreateRepository::class', $provider);
        $this->assertIsString($routes);
        $this->assertStringContainsString("'auth:sanctum'", $routes);
        $this->assertStringContainsString("'can:admin-api'", $routes);
        $this->assertStringContainsString("'idempotency'", $routes);

        $this->assertIsString($openApi);
        $this->assertStringContainsString('CreateUserRequest:', $openApi);
        $this->assertStringContainsString('UserData:', $openApi);
        $this->assertStringContainsString("'201':", $openApi);
        $this->assertStringContainsString("'401':", $openApi);
        $this->assertStringContainsString("'403':", $openApi);
        $this->assertStringContainsString("'422':", $openApi);
        $this->assertStringNotContainsString("'501':", $openApi);

        $this->assertIsString($verticalSliceTest);
        $this->assertStringContainsString('test_admin_can_create_a_user_without_exposing_password', $verticalSliceTest);
        $this->assertStringContainsString('test_duplicate_email_uses_problem_details_in_spanish', $verticalSliceTest);
        $this->assertStringContainsString('test_non_admin_user_is_forbidden', $verticalSliceTest);

        $this->assertIsString($manifest);
        $manifestData = json_decode($manifest, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('sanctum', $manifestData['governance']['authentication']);
        $this->assertContains('auth.login', array_column($manifestData['endpoints'], 'id'));
        $this->assertContains('users.create', array_column($manifestData['endpoints'], 'id'));

        $this->assertFalse($zip->locateName('users-create-api/app/Presentation/Http/Controllers/Generated/UsersListController.php'));
        $this->assertFalse($zip->locateName('users-create-api/app/Presentation/Http/Controllers/Generated/UsersShowController.php'));
        $this->assertFalse($zip->locateName('users-create-api/app/Application/Users/Contracts/UserListRepository.php'));
        $this->assertFalse($zip->locateName('users-create-api/app/Application/Users/Contracts/UserReadRepository.php'));
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
            'project' => [
                'name' => 'Users Create API',
                'api_version' => 'v1',
            ],
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
            'endpoints' => [
                ['id' => 'users.create', 'exposure' => 'admin'],
            ],
        ];
    }
}
''')

# Living documentation
roadmap = Path('docs/delivery/roadmap.md')
text = roadmap.read_text()
text = text.replace('## U0.10 - Users Library: `users.show` 🚧', '## U0.10 - Users Library: `users.show` ✅')
anchor = "### Siguientes checkpoints previstos\n"
section = """## U0.11 - Users Library: `users.create` 🚧\n\n- reutiliza `User`, `UserData`, Sanctum y el gate `admin-api`;\n- `CreateUserData`, `UserCreateRepository` y `CreateUser` mantienen Application independiente de Laravel;\n- `DatabaseUserCreateRepository` persiste la identidad existente y hashea password;\n- `CreateUserRequestValidator` aplica email único, password mínimo y rol compatible con el RBAC actual;\n- `POST /api/v1/users` responde 201 y nunca expone password;\n- 401/403/422 gobernados, con validación RFC 9457 en español;\n- idempotencia existente se reutiliza para la operación POST;\n- Swagger maestro, OpenAPI generado y catálogo se actualizan en el mismo checkpoint;\n- export exclusivo de `users.create` no arrastra infraestructura de list/show ni Products;\n- acceptance SaaS debe ejecutar list + show + create sobre una única identidad compartida.\n\n"""
if anchor not in text:
    raise SystemExit('roadmap anchor not found')
text = text.replace(anchor, section + anchor, 1)
roadmap.write_text(text)

Path('docs/delivery/users-create.md').write_text('''# `users.create` canónico\n\n`users.create` amplía la Users Library sin crear una segunda identidad ni un segundo mecanismo de autorización. Reutiliza `User`, `UserData`, Sanctum, RBAC admin, Problem Details, idempotencia y la migración de usuarios que ya pertenecen a la biblioteca maestra.\n\n## Contrato\n\n- `POST /api/v1/users`\n- exposición `admin`\n- request: `name`, `email`, `password`, `role` opcional\n- `role` por defecto: `user`\n- roles admitidos por la identidad actual: `user`, `admin`\n- 201: `UserData` sin password\n- 401: autenticación requerida\n- 403: privilegios de administrador requeridos\n- 422: validación mediante RFC 9457, incluida unicidad de email\n\n## No duplicación\n\nLa feature no genera `users.list` ni `users.show` si no fueron seleccionadas. Tampoco crea otro modelo User, otra tabla users ni otro sistema de autenticación. La dependencia de exposición admin resuelve `auth.login`, que aporta la identidad y Sanctum canónicos.\n\n## Gate\n\nLa feature se considera completa solo cuando catálogo, Swagger maestro, OpenAPI generado, test raíz de exportación y acceptance de la solución SaaS permanecen verdes.\n''')

print('U0.11 patch applied')
