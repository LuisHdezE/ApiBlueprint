from pathlib import Path

path = Path('app/Infrastructure/Blueprint/LaravelZipBlueprintExporter.php')
text = path.read_text()


def replace_once(old: str, new: str) -> None:
    global text
    if old not in text:
        raise SystemExit(f'anchor not found: {old[:120]!r}')
    text = text.replace(old, new, 1)


# UserData is the canonical public read model for users.list/users.show and future writes.
text = text.replace('UserListItem', 'UserData')
text = text.replace('userListItemFile', 'userDataFile')

replace_once(
    "        $hasUsersList = $this->hasEndpoint($manifest, 'users.list');\n        $hasProductsShow = $this->hasEndpoint($manifest, 'products.show');",
    "        $hasUsersList = $this->hasEndpoint($manifest, 'users.list');\n        $hasUsersShow = $this->hasEndpoint($manifest, 'users.show');\n        $hasProductsShow = $this->hasEndpoint($manifest, 'products.show');",
)

replace_once(
    """        if ($hasUsersList) {
            $files['app/Application/Users/Contracts/UserListRepository.php'] = $this->userListRepositoryContractFile();
            $files['app/Application/Users/Data/UserData.php'] = $this->userDataFile();
            $files['app/Application/Users/Data/UserPage.php'] = $this->userPageFile();
            $files['app/Application/Users/UseCases/ListUsers.php'] = $this->listUsersUseCaseFile();
            $files['app/Infrastructure/Users/DatabaseUserListRepository.php'] = $this->databaseUserListRepositoryFile();
            $files['tests/Feature/UsersListVerticalSliceTest.php'] = $this->usersListVerticalSliceTestFile($manifest);
        }
""",
    """        if ($hasUsersList || $hasUsersShow) {
            $files['app/Application/Users/Data/UserData.php'] = $this->userDataFile();
        }

        if ($hasUsersList) {
            $files['app/Application/Users/Contracts/UserListRepository.php'] = $this->userListRepositoryContractFile();
            $files['app/Application/Users/Data/UserPage.php'] = $this->userPageFile();
            $files['app/Application/Users/UseCases/ListUsers.php'] = $this->listUsersUseCaseFile();
            $files['app/Infrastructure/Users/DatabaseUserListRepository.php'] = $this->databaseUserListRepositoryFile();
            $files['tests/Feature/UsersListVerticalSliceTest.php'] = $this->usersListVerticalSliceTestFile($manifest);
        }

        if ($hasUsersShow) {
            $files['app/Application/Users/Contracts/UserReadRepository.php'] = $this->userReadRepositoryContractFile();
            $files['app/Application/Users/UseCases/GetUser.php'] = $this->getUserUseCaseFile();
            $files['app/Infrastructure/Users/DatabaseUserReadRepository.php'] = $this->databaseUserReadRepositoryFile();
            $files['tests/Feature/UsersShowVerticalSliceTest.php'] = $this->usersShowVerticalSliceTestFile();
        }
""",
)

# Persistence-backed executable features need the generated test DB tooling.
old_condition = "$this->hasEndpoint($manifest, 'auth.login') || $this->hasEndpoint($manifest, 'users.list') || $this->hasEndpoint($manifest, 'products.show') || $this->hasEndpoint($manifest, 'products.list')"
new_condition = "$this->hasEndpoint($manifest, 'auth.login') || $this->hasEndpoint($manifest, 'users.list') || $this->hasEndpoint($manifest, 'users.show') || $this->hasEndpoint($manifest, 'products.show') || $this->hasEndpoint($manifest, 'products.list')"
if old_condition not in text:
    raise SystemExit('persistence condition anchor not found')
text = text.replace(old_condition, new_condition)

replace_once(
    """        if ($endpoint['id'] === 'users.list') {
            return $this->usersListControllerFile($className);
        }
        if ($endpoint['id'] === 'products.list') {
""",
    """        if ($endpoint['id'] === 'users.list') {
            return $this->usersListControllerFile($className);
        }
        if ($endpoint['id'] === 'users.show') {
            return $this->usersShowControllerFile($className);
        }
        if ($endpoint['id'] === 'products.list') {
""",
)

replace_once(
    """        if ($this->hasEndpoint($manifest, 'users.list')) {
            $imports[] = 'use App\\\\Application\\\\Users\\\\Contracts\\\\UserListRepository;';
            $imports[] = 'use App\\\\Infrastructure\\\\Users\\\\DatabaseUserListRepository;';
            $registerLines[] = '        $this->app->bind(UserListRepository::class, DatabaseUserListRepository::class);';
        }
        if ($this->hasEndpoint($manifest, 'products.show')) {
""",
    """        if ($this->hasEndpoint($manifest, 'users.list')) {
            $imports[] = 'use App\\\\Application\\\\Users\\\\Contracts\\\\UserListRepository;';
            $imports[] = 'use App\\\\Infrastructure\\\\Users\\\\DatabaseUserListRepository;';
            $registerLines[] = '        $this->app->bind(UserListRepository::class, DatabaseUserListRepository::class);';
        }
        if ($this->hasEndpoint($manifest, 'users.show')) {
            $imports[] = 'use App\\\\Application\\\\Users\\\\Contracts\\\\UserReadRepository;';
            $imports[] = 'use App\\\\Infrastructure\\\\Users\\\\DatabaseUserReadRepository;';
            $registerLines[] = '        $this->app->bind(UserReadRepository::class, DatabaseUserReadRepository::class);';
        }
        if ($this->hasEndpoint($manifest, 'products.show')) {
""",
)

old_exec = "['auth.login', 'auth.logout', 'users.list', 'products.list', 'products.show']"
new_exec = "['auth.login', 'auth.logout', 'users.list', 'users.show', 'products.list', 'products.show']"
if old_exec not in text:
    raise SystemExit('executable endpoint list anchor not found')
text = text.replace(old_exec, new_exec)

# Generated OpenAPI: users.show has its own admin/read contract.
replace_once(
    """                    } elseif ($endpoint['id'] === 'products.show') {
                        $lines[] = \"        '200':\";
""",
    """                    } elseif ($endpoint['id'] === 'users.show') {
                        $lines[] = \"        '200':\";
                        $lines[] = '          description: \"Usuario encontrado.\"';
                        $lines[] = '          content: { application/json: { schema: { type: object, required: [data], properties: { data: { $ref: \"#/components/schemas/UserData\" } } } } }';
                        $lines[] = \"        '401':\";
                        $lines[] = '          description: \"Autenticación requerida.\"';
                        $lines[] = '          content: { application/problem+json: { schema: { $ref: \"#/components/schemas/ProblemDetails\" } } }';
                        if ($governance['rbac']) {
                            $lines[] = \"        '403':\";
                            $lines[] = '          description: \"Se requieren privilegios de administrador.\"';
                            $lines[] = '          content: { application/problem+json: { schema: { $ref: \"#/components/schemas/ProblemDetails\" } } }';
                        }
                        $lines[] = \"        '404':\";
                        $lines[] = '          description: \"Usuario no encontrado.\"';
                        $lines[] = '          content: { application/problem+json: { schema: { $ref: \"#/components/schemas/ProblemDetails\" } } }';
                    } elseif ($endpoint['id'] === 'products.show') {
                        $lines[] = \"        '200':\";
""",
)

replace_once(
    """        if ($this->hasEndpoint($manifest, 'users.list')) {
            $lines[] = '    UserData:';
""",
    """        if ($this->hasEndpoint($manifest, 'users.list') || $this->hasEndpoint($manifest, 'users.show')) {
            $lines[] = '    UserData:';
""",
)

# New users.show controller reuses shared ProblemDetails and UserData through the use case.
controller_marker = "    private function usersListControllerFile(string $className): string\n"
if controller_marker not in text:
    raise SystemExit('users controller marker not found')
users_show_controller = r'''    private function usersShowControllerFile(string $className): string
    {
        return <<<PHP
<?php

namespace App\\Presentation\\Http\\Controllers\\Generated;

use App\\Application\\Users\\UseCases\\GetUser;
use App\\Presentation\\Http\\Support\\ProblemDetails;
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;

final readonly class $className
{
    public function __construct(private GetUser \\$getUser)
    {
        //
    }

    public function __invoke(Request \\$request, string \\$id): JsonResponse
    {
        \\$user = \\$this->getUser->execute(\\$id);

        if (\\$user === null) {
            return ProblemDetails::response(
                request: \\$request,
                status: 404,
                title: 'Usuario no encontrado',
                detail: 'No existe un usuario con el identificador solicitado.',
                type: 'https://eliasworks.uy/problems/user-not-found',
            );
        }

        return response()->json(['data' => \\$user->toArray()]);
    }
}
PHP;
    }

'''
text = text.replace(controller_marker, users_show_controller + controller_marker, 1)

# Shared user read model plus users.show application/infrastructure slice.
contract_marker = "    private function userListRepositoryContractFile(): string\n"
if contract_marker not in text:
    raise SystemExit('user list contract marker not found')
users_show_application = r'''    private function userReadRepositoryContractFile(): string
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

'''
text = text.replace(contract_marker, users_show_application + contract_marker, 1)

test_marker = "    private function usersListVerticalSliceTestFile(array $manifest): string\n"
if test_marker not in text:
    raise SystemExit('users list test marker not found')
users_show_test = r'''    private function usersShowVerticalSliceTestFile(): string
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
    }

'''
text = text.replace(test_marker, users_show_test + test_marker, 1)

path.write_text(text)
print('U0.10 exporter patch applied')
