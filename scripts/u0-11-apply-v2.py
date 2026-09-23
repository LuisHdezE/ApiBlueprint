from pathlib import Path


def read(path):
    return Path(path).read_text()


def write(path, text):
    Path(path).write_text(text)


def once(path, old, new):
    text = read(path)
    count = text.count(old)
    if count != 1:
        raise SystemExit(f'{path}: expected 1 match, got {count}: {old[:100]!r}')
    write(path, text.replace(old, new, 1))


def insert_before(path, anchor, block):
    once(path, anchor, block + anchor)


def insert_after(path, anchor, block):
    once(path, anchor, anchor + block)


CATALOG = 'config/blueprint.php'
MASTER = 'app/Application/Blueprint/Queries/GetMasterOpenApi.php'
EXPORTER = 'app/Infrastructure/Blueprint/LaravelZipBlueprintExporter.php'
ROADMAP = 'docs/delivery/roadmap.md'

# 1) Canonical catalog
once(
    CATALOG,
    "    ['id' => 'users.create', 'capability' => 'users', 'capability_label' => 'Usuarios', 'summary' => 'Crear usuario', 'method' => 'POST', 'path' => '/api/v1/users', 'default_exposure' => 'admin'],",
    "    ['id' => 'users.create', 'capability' => 'users', 'capability_label' => 'Usuarios', 'summary' => 'Crear usuario', 'method' => 'POST', 'path' => '/api/v1/users', 'default_exposure' => 'admin', 'implementation_status' => 'implemented', 'exportable' => true, 'openapi_ready' => true, 'tests_ready' => true],",
)

# 2) Master OpenAPI
insert_before(
    MASTER,
    "\n            if ($feature['default_exposure'] !== 'public') {",
    """
            if ($feature['id'] === 'users.create') {
                $operation['requestBody'] = [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => ['$ref' => '#/components/schemas/CreateUserRequest'],
                        ],
                    ],
                ];
            }
""",
)
insert_before(
    MASTER,
    "                    'UserData' => [",
    """                    'CreateUserRequest' => [
                        'type' => 'object',
                        'required' => ['name', 'email', 'password'],
                        'properties' => [
                            'name' => ['type' => 'string', 'maxLength' => 120],
                            'email' => ['type' => 'string', 'format' => 'email', 'maxLength' => 255],
                            'password' => ['type' => 'string', 'format' => 'password', 'minLength' => 8],
                            'role' => ['type' => 'string', 'enum' => ['user', 'admin'], 'default' => 'user'],
                        ],
                    ],
""",
)
insert_before(
    MASTER,
    "        if ($feature['id'] === 'users.show') {",
    """        if ($feature['id'] === 'users.create') {
            return [
                '201' => [
                    'description' => 'Usuario creado correctamente.',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['data'],
                                'properties' => ['data' => ['$ref' => '#/components/schemas/UserData']],
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
                '422' => [
                    'description' => 'Datos de usuario inválidos.',
                    'content' => ['application/problem+json' => ['schema' => ['$ref' => '#/components/schemas/ProblemDetails']]],
                ],
            ];
        }

""",
)

# 3) Exporter wiring
once(
    EXPORTER,
    "        $hasUsersShow = $this->hasEndpoint($manifest, 'users.show');\n        $hasProductsShow = $this->hasEndpoint($manifest, 'products.show');",
    "        $hasUsersShow = $this->hasEndpoint($manifest, 'users.show');\n        $hasUsersCreate = $this->hasEndpoint($manifest, 'users.create');\n        $hasProductsShow = $this->hasEndpoint($manifest, 'products.show');",
)
once(
    EXPORTER,
    "        if ($hasUsersList || $hasUsersShow) {\n            $files['app/Application/Users/Data/UserData.php'] = $this->userDataFile();\n        }",
    "        if ($hasUsersList || $hasUsersShow || $hasUsersCreate) {\n            $files['app/Application/Users/Data/UserData.php'] = $this->userDataFile();\n        }",
)
insert_before(
    EXPORTER,
    "\n        if ($hasProductsShow || $hasProductsList) {",
    """
        if ($hasUsersCreate) {
            $files['app/Application/Users/Data/CreateUserData.php'] = $this->createUserDataFile();
            $files['app/Application/Users/Contracts/UserCreateRepository.php'] = $this->userCreateRepositoryContractFile();
            $files['app/Application/Users/UseCases/CreateUser.php'] = $this->createUserUseCaseFile();
            $files['app/Infrastructure/Users/DatabaseUserCreateRepository.php'] = $this->databaseUserCreateRepositoryFile();
            $files['app/Presentation/Http/Support/CreateUserRequestValidator.php'] = $this->createUserRequestValidatorFile();
            $files['tests/Feature/UsersCreateVerticalSliceTest.php'] = $this->usersCreateVerticalSliceTestFile();
        }
""",
)
text = read(EXPORTER)
old = "$this->hasEndpoint($manifest, 'users.show') || $this->hasEndpoint($manifest, 'products.show')"
if text.count(old) != 2:
    raise SystemExit(f'expected composer/phpunit anchor twice, got {text.count(old)}')
text = text.replace(old, "$this->hasEndpoint($manifest, 'users.show') || $this->hasEndpoint($manifest, 'users.create') || $this->hasEndpoint($manifest, 'products.show')")
write(EXPORTER, text)

insert_before(
    EXPORTER,
    "        if ($endpoint['id'] === 'products.list') {\n            return $this->productsListControllerFile($className);\n        }",
    """        if ($endpoint['id'] === 'users.create') {
            return $this->usersCreateControllerFile($className);
        }
""",
)
insert_before(
    EXPORTER,
    "        if ($this->hasEndpoint($manifest, 'products.show')) {\n            $imports[] = 'use App\\\\Application\\\\Products\\\\Contracts\\\\ProductReadRepository;';",
    """        if ($this->hasEndpoint($manifest, 'users.create')) {
            $imports[] = 'use App\\\\Application\\\\Users\\\\Contracts\\\\UserCreateRepository;';
            $imports[] = 'use App\\\\Infrastructure\\\\Users\\\\DatabaseUserCreateRepository;';
            $registerLines[] = '        $this->app->bind(UserCreateRepository::class, DatabaseUserCreateRepository::class);';
        }
""",
)
text = read(EXPORTER)
old_exec = "['auth.login', 'auth.logout', 'users.list', 'users.show', 'products.list', 'products.show']"
if text.count(old_exec) != 2:
    raise SystemExit(f'expected executable endpoint list twice, got {text.count(old_exec)}')
write(EXPORTER, text.replace(old_exec, "['auth.login', 'auth.logout', 'users.list', 'users.show', 'users.create', 'products.list', 'products.show']"))

# Generated OpenAPI request body. Insert immediately after the auth-login request schema line/block.
insert_after(
    EXPORTER,
    "                        $lines[] = '            schema: { $ref: \"#/components/schemas/AuthLoginRequest\" }';\n                    }",
    """ elseif ($endpoint['id'] === 'users.create') {
                        $lines[] = '      requestBody:';
                        $lines[] = '        required: true';
                        $lines[] = '        content:';
                        $lines[] = '          application/json:';
                        $lines[] = '            schema: { $ref: \"#/components/schemas/CreateUserRequest\" }';
                    }""",
)
insert_before(
    EXPORTER,
    "                    } elseif ($endpoint['id'] === 'users.show') {\n                        $lines[] = \"        '200':\";",
    """                    } elseif ($endpoint['id'] === 'users.create') {
                        $lines[] = "        '201':";
                        $lines[] = '          description: "Usuario creado correctamente."';
                        $lines[] = '          content: { application/json: { schema: { type: object, required: [data], properties: { data: { $ref: "#/components/schemas/UserData" } } } } }';
                        $lines[] = "        '401':";
                        $lines[] = '          description: "Autenticación requerida."';
                        $lines[] = '          content: { application/problem+json: { schema: { $ref: "#/components/schemas/ProblemDetails" } } }';
                        if ($governance['rbac']) {
                            $lines[] = "        '403':";
                            $lines[] = '          description: "Se requieren privilegios de administrador."';
                            $lines[] = '          content: { application/problem+json: { schema: { $ref: "#/components/schemas/ProblemDetails" } } }';
                        }
                        $lines[] = "        '422':";
                        $lines[] = '          description: "Datos de usuario inválidos."';
                        $lines[] = '          content: { application/problem+json: { schema: { $ref: "#/components/schemas/ProblemDetails" } } }';
""",
)
once(
    EXPORTER,
    "        if ($this->hasEndpoint($manifest, 'users.list') || $this->hasEndpoint($manifest, 'users.show')) {\n            $lines[] = '    UserData:';",
    """        if ($this->hasEndpoint($manifest, 'users.create')) {
            $lines[] = '    CreateUserRequest:';
            $lines[] = '      type: object';
            $lines[] = '      required: [name, email, password]';
            $lines[] = '      properties:';
            $lines[] = '        name: { type: string, maxLength: 120 }';
            $lines[] = '        email: { type: string, format: email, maxLength: 255 }';
            $lines[] = '        password: { type: string, format: password, minLength: 8 }';
            $lines[] = '        role: { type: string, enum: [user, admin], default: user }';
        }
        if ($this->hasEndpoint($manifest, 'users.list') || $this->hasEndpoint($manifest, 'users.show') || $this->hasEndpoint($manifest, 'users.create')) {
            $lines[] = '    UserData:';""",
)

# 4) Generated feature recipe methods
METHODS = r'''    private function createUserDataFile(): string
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
insert_before(EXPORTER, "    private function usersShowControllerFile(string $className): string\n    {", METHODS)

# 5) Root export test
Path('tests/Feature/GeneratedUsersCreateVerticalSliceExportTest.php').write_text(r'''<?php

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
''')

# 6) Living docs
text = read(ROADMAP)
text = text.replace('## U0.10 - Users Library: `users.show` 🚧', '## U0.10 - Users Library: `users.show` ✅')
anchor = '### Siguientes checkpoints previstos\n'
if anchor not in text:
    raise SystemExit('roadmap anchor missing')
section = '''## U0.11 - Users Library: `users.create` 🚧\n\n- reutiliza `User`, `UserData`, Sanctum y el gate `admin-api`;\n- `CreateUserData`, `UserCreateRepository` y `CreateUser` mantienen Application independiente de Laravel;\n- `DatabaseUserCreateRepository` persiste la identidad existente y hashea password;\n- validación reusable de email único, password mínimo y rol compatible con RBAC;\n- `POST /api/v1/users` responde 201 y nunca expone password;\n- 401/403/422 gobernados, con validación RFC 9457 en español;\n- reutiliza la idempotencia existente para la operación POST;\n- Swagger maestro, OpenAPI generado y catálogo se actualizan juntos;\n- export exclusivo no arrastra list/show ni Products;\n- acceptance SaaS ejecuta list + show + create sobre una identidad compartida.\n\n'''
write(ROADMAP, text.replace(anchor, section + anchor, 1))
Path('docs/delivery/users-create.md').write_text('''# `users.create` canónico\n\n`users.create` amplía la Users Library sin crear una segunda identidad, tabla de usuarios, autenticación o RBAC. Reutiliza `User`, `UserData`, Sanctum, `admin-api`, Problem Details e idempotencia.\n\n## Contrato\n\n- `POST /api/v1/users`\n- exposición `admin`\n- request: `name`, `email`, `password`, `role` opcional\n- `role` por defecto: `user`\n- roles admitidos actualmente: `user`, `admin`\n- 201: `UserData` sin password\n- 401/403/422 gobernados\n- email único y password hasheado\n\n## Gate\n\nCatálogo, Swagger maestro, OpenAPI generado, test raíz de exportación y acceptance SaaS deben permanecer sincronizados y verdes.\n''')

print('U0.11 v2 patch applied')
