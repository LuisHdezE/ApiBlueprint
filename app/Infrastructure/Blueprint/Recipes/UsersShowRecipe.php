<?php

namespace App\Infrastructure\Blueprint\Recipes;

final class UsersShowRecipe extends AbstractLaravelFeatureRecipe
{
    public function id(): string
    {
        return 'users.show';
    }

    public function endpointIds(): array
    {
        return [
            'users.show',
        ];
    }

    public function files(array $manifest): array
    {
        return [
            'app/Application/Users/Contracts/UserReadRepository.php' => $this->userReadRepositoryContractFile(),
            'app/Application/Users/UseCases/GetUser.php' => $this->getUserUseCaseFile(),
            'app/Infrastructure/Users/DatabaseUserReadRepository.php' => $this->databaseUserReadRepositoryFile(),
            'tests/Feature/UsersShowVerticalSliceTest.php' => $this->usersShowVerticalSliceTestFile(),
        ];
    }

    public function controller(array $endpoint, string $className): ?string
    {
        if (($endpoint['id'] ?? null) !== 'users.show') {
            return null;
        }

        return $this->usersShowControllerFile($className);
    }

    public function serviceProviderImports(): array
    {
        return [
            'use App\\Application\\Users\\Contracts\\UserReadRepository;',
            'use App\\Infrastructure\\Users\\DatabaseUserReadRepository;',
        ];
    }

    public function serviceProviderRegistrations(): array
    {
        return [
            '        $this->app->bind(UserReadRepository::class, DatabaseUserReadRepository::class);',
        ];
    }

    public function composerRequireDev(): array
    {
        return [
            'mockery/mockery' => '^1.6',
        ];
    }

    private function usersShowControllerFile(string $className): string
    {
        return <<<PHP
<?php

namespace App\Presentation\Http\Controllers\Generated;

use App\Application\Users\UseCases\GetUser;
use App\Presentation\Http\Support\ProblemDetails;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class $className
{
    public function __construct(private GetUser \$getUser)
    {
        //
    }

    public function __invoke(Request \$request, string \$id): JsonResponse
    {
        \$user = \$this->getUser->execute(\$id);

        if (\$user === null) {
            return ProblemDetails::response(
                request: \$request,
                status: 404,
                title: 'Usuario no encontrado',
                detail: 'No existe un usuario con el identificador solicitado.',
                type: 'https://eliasworks.uy/problems/user-not-found',
            );
        }

        return response()->json(['data' => \$user->toArray()]);
    }
}
PHP;
    }

    private function userReadRepositoryContractFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Application\Users\Contracts;

use App\Application\Users\Data\UserData;

interface UserReadRepository
{
    public function find(string $id): ?UserData;
}
PHP;
    }

    private function getUserUseCaseFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Application\Users\UseCases;

use App\Application\Users\Contracts\UserReadRepository;
use App\Application\Users\Data\UserData;

final readonly class GetUser
{
    public function __construct(private UserReadRepository $users)
    {
        //
    }

    public function execute(string $id): ?UserData
    {
        return $this->users->find($id);
    }
}
PHP;
    }

    private function databaseUserReadRepositoryFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Infrastructure\Users;

use App\Application\Users\Contracts\UserReadRepository;
use App\Application\Users\Data\UserData;
use Illuminate\Support\Facades\DB;

final class DatabaseUserReadRepository implements UserReadRepository
{
    public function find(string $id): ?UserData
    {
        $row = DB::table('users')
            ->select(['id', 'name', 'email', 'role'])
            ->where('id', $id)
            ->first();

        if ($row === null) {
            return null;
        }

        return new UserData(
            id: (string) $row->id,
            name: (string) $row->name,
            email: (string) $row->email,
            role: (string) $row->role,
        );
    }
}
PHP;
    }

    private function usersShowVerticalSliceTestFile(): string
    {
        return <<<'PHP'
<?php

namespace Tests\Feature;

use App\Infrastructure\Identity\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class UsersShowVerticalSliceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_retrieve_a_user_without_password(): void
    {
        $token = $this->tokenFor('admin@example.com', 'admin');
        $target = User::query()->create([
            'name' => 'Usuario objetivo',
            'email' => 'target@example.com',
            'password' => Hash::make('secret-password'),
            'role' => 'user',
        ]);

        $response = $this->withToken($token)
            ->getJson('/api/v1/users/'.$target->getKey())
            ->assertOk()
            ->assertJsonPath('data.id', (string) $target->getKey())
            ->assertJsonPath('data.name', 'Usuario objetivo')
            ->assertJsonPath('data.email', 'target@example.com')
            ->assertJsonPath('data.role', 'user');

        $this->assertArrayNotHasKey('password', $response->json('data'));
    }

    public function test_missing_user_uses_problem_details_in_spanish(): void
    {
        $token = $this->tokenFor('admin-missing@example.com', 'admin');

        $this->withToken($token)
            ->getJson('/api/v1/users/999999')
            ->assertStatus(404)
            ->assertHeader('content-type', 'application/problem+json')
            ->assertJsonPath('title', 'Usuario no encontrado')
            ->assertJsonPath('status', 404);
    }

    public function test_non_admin_user_is_forbidden(): void
    {
        $token = $this->tokenFor('viewer-show@example.com', 'user');

        $this->withToken($token)
            ->getJson('/api/v1/users/1')
            ->assertForbidden();
    }

    private function tokenFor(string $email, string $role): string
    {
        $user = User::query()->create([
            'name' => $role === 'admin' ? 'Administrador' : 'Usuario',
            'email' => $email,
            'password' => Hash::make('secret-password'),
            'role' => $role,
        ]);

        return $user->createToken('tests')->plainTextToken;
    }
}
PHP;
    }}
