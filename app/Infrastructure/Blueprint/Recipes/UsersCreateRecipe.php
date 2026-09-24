<?php

namespace App\Infrastructure\Blueprint\Recipes;

final class UsersCreateRecipe extends AbstractLaravelFeatureRecipe
{
    public function id(): string
    {
        return 'users.create';
    }

    public function endpointIds(): array
    {
        return [
            'users.create',
        ];
    }

    public function files(array $manifest): array
    {
        return [
            'app/Application/Users/Data/CreateUserData.php' => $this->createUserDataFile(),
            'app/Application/Users/Contracts/UserCreateRepository.php' => $this->userCreateRepositoryContractFile(),
            'app/Application/Users/UseCases/CreateUser.php' => $this->createUserUseCaseFile(),
            'app/Infrastructure/Users/DatabaseUserCreateRepository.php' => $this->databaseUserCreateRepositoryFile(),
            'app/Presentation/Http/Support/CreateUserRequestValidator.php' => $this->createUserRequestValidatorFile(),
            'tests/Feature/UsersCreateVerticalSliceTest.php' => $this->usersCreateVerticalSliceTestFile(),
        ];
    }

    public function controller(array $endpoint, string $className): ?string
    {
        if (($endpoint['id'] ?? null) !== 'users.create') {
            return null;
        }

        return $this->usersCreateControllerFile($className);
    }

    public function serviceProviderImports(): array
    {
        return [
            'use App\\Application\\Users\\Contracts\\UserCreateRepository;',
            'use App\\Infrastructure\\Users\\DatabaseUserCreateRepository;',
        ];
    }

    public function serviceProviderRegistrations(): array
    {
        return [
            '        $this->app->bind(UserCreateRepository::class, DatabaseUserCreateRepository::class);',
        ];
    }

    public function composerRequireDev(): array
    {
        return [
            'mockery/mockery' => '^1.6',
        ];
    }

    private function createUserDataFile(): string
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

}
