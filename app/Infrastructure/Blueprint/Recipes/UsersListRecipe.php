<?php

namespace App\Infrastructure\Blueprint\Recipes;

final class UsersListRecipe extends AbstractLaravelFeatureRecipe
{
    public function id(): string
    {
        return 'users.list';
    }

    public function endpointIds(): array
    {
        return [
            'users.list',
        ];
    }

    public function files(array $manifest): array
    {
        return [
            'app/Application/Users/Contracts/UserListRepository.php' => $this->userListRepositoryContractFile(),
            'app/Application/Users/Data/UserPage.php' => $this->userPageFile(),
            'app/Application/Users/UseCases/ListUsers.php' => $this->listUsersUseCaseFile(),
            'app/Infrastructure/Users/DatabaseUserListRepository.php' => $this->databaseUserListRepositoryFile(),
            'tests/Feature/UsersListVerticalSliceTest.php' => $this->usersListVerticalSliceTestFile($manifest),
        ];
    }

    public function controller(array $endpoint, string $className): ?string
    {
        if (($endpoint['id'] ?? null) !== 'users.list') {
            return null;
        }

        return $this->usersListControllerFile($className);
    }

    public function serviceProviderImports(): array
    {
        return [
            'use App\\Application\\Users\\Contracts\\UserListRepository;',
            'use App\\Infrastructure\\Users\\DatabaseUserListRepository;',
        ];
    }

    public function serviceProviderRegistrations(): array
    {
        return [
            '        $this->app->bind(UserListRepository::class, DatabaseUserListRepository::class);',
        ];
    }

    public function composerRequireDev(): array
    {
        return [
            'mockery/mockery' => '^1.6',
        ];
    }

    private function usersListControllerFile(string $className): string
    {
        return <<<PHP
<?php

namespace App\Presentation\Http\Controllers\Generated;

use App\Application\Users\UseCases\ListUsers;
use App\Presentation\Http\Support\ListQueryValidator;
use App\Presentation\Http\Support\ProblemDetails;
use App\Presentation\Http\Support\QueryOptionsParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

final readonly class $className
{
    public function __construct(
        private ListUsers \$listUsers,
        private QueryOptionsParser \$queryOptionsParser,
        private ListQueryValidator \$queryValidator,
    ) {
        //
    }

    public function __invoke(Request \$request): JsonResponse
    {
        \$this->queryValidator->validate(
            \$request,
            ['id', 'name', 'email', 'role'],
            ['id', 'name', 'email', 'role'],
        );

        try {
            \$page = \$this->listUsers->handle(\$this->queryOptionsParser->parse(\$request));
        } catch (InvalidArgumentException) {
            return ProblemDetails::response(
                request: \$request,
                status: 422,
                title: 'Cursor de paginación inválido',
                detail: 'El cursor no corresponde al orden solicitado o tiene un formato inválido.',
                type: 'https://eliasworks.uy/problems/invalid-pagination-cursor',
            );
        }

        return response()->json(\$page->toArray());
    }
}
PHP;
    }

    private function userListRepositoryContractFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Application\Users\Contracts;

use App\Application\Shared\Query\QueryOptions;
use App\Application\Users\Data\UserPage;

interface UserListRepository
{
    public function paginate(QueryOptions $options): UserPage;
}
PHP;
    }

    private function userPageFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Application\Users\Data;

final readonly class UserPage
{
    /** @param list<UserData> $items */
    public function __construct(
        public array $items,
        public array $meta,
    ) {
        //
    }

    public function toArray(): array
    {
        return [
            'data' => array_map(static fn (UserData $user): array => $user->toArray(), $this->items),
            'meta' => $this->meta,
        ];
    }
}
PHP;
    }

    private function listUsersUseCaseFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Application\Users\UseCases;

use App\Application\Shared\Query\QueryOptions;
use App\Application\Users\Contracts\UserListRepository;
use App\Application\Users\Data\UserPage;

final readonly class ListUsers
{
    public function __construct(private UserListRepository $users)
    {
        //
    }

    public function handle(QueryOptions $options): UserPage
    {
        return $this->users->paginate($options);
    }
}
PHP;
    }

    private function databaseUserListRepositoryFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Infrastructure\Users;

use App\Application\Shared\Query\QueryOptions;
use App\Application\Users\Contracts\UserListRepository;
use App\Application\Users\Data\UserData;
use App\Application\Users\Data\UserPage;
use App\Infrastructure\Database\DatabaseQueryPaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final readonly class DatabaseUserListRepository implements UserListRepository
{
    public function __construct(private DatabaseQueryPaginator $paginator)
    {
        //
    }

    public function paginate(QueryOptions $options): UserPage
    {
        $query = DB::table('users')->select(['id', 'name', 'email', 'role']);
        $this->applyFilters($query, $options->filters);
        $sorts = $this->paginator->sorts($options->sort, ['id', 'name', 'email', 'role']);
        $page = $this->paginator->paginate($query, $sorts, $options);

        return new UserPage(
            items: array_map(
                static fn (object $row): UserData => new UserData(
                    id: (string) $row->id,
                    name: (string) $row->name,
                    email: (string) $row->email,
                    role: (string) $row->role,
                ),
                $page['items'],
            ),
            meta: $page['meta'],
        );
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        foreach ($filters as $field => $value) {
            if (! is_scalar($value) || trim((string) $value) === '') {
                continue;
            }

            if ($field === 'id') {
                $query->where('id', (string) $value);
            } elseif ($field === 'name') {
                $query->where('name', 'like', '%'.(string) $value.'%');
            } elseif ($field === 'email') {
                $query->where('email', 'like', '%'.(string) $value.'%');
            } elseif ($field === 'role') {
                $query->where('role', (string) $value);
            }
        }
    }
}
PHP;
    }

    private function usersListVerticalSliceTestFile(array $manifest): string
    {
        $strategy = $manifest['governance']['pagination']['strategy'];
        $paginationTest = $strategy === 'cursor'
            ? <<<'PHP'
    public function test_admin_can_page_users_with_a_keyset_cursor_without_passwords(): void
    {
        $this->seedRegularUsers();
        $token = $this->tokenFor('admin@example.com', 'admin');

        $first = $this->withToken($token)
            ->getJson('/api/v1/users?filter[role]=user&page[size]=2&sort=name')
            ->assertOk()
            ->assertJsonPath('meta.strategy', 'cursor')
            ->assertJsonPath('meta.has_more', true)
            ->assertJsonPath('data.0.name', 'Alpha')
            ->assertJsonPath('data.1.name', 'Beta');

        $this->assertArrayNotHasKey('password', $first->json('data.0'));
        $cursor = $first->json('meta.next_cursor');
        $this->assertIsString($cursor);
        $this->assertNotSame('', $cursor);

        $this->withToken($token)
            ->getJson('/api/v1/users?filter[role]=user&page[size]=2&sort=name&page[cursor]='.rawurlencode($cursor))
            ->assertOk()
            ->assertJsonPath('meta.has_more', false)
            ->assertJsonPath('data.0.name', 'Delta')
            ->assertJsonPath('data.1.name', 'Gamma');
    }
PHP
            : <<<'PHP'
    public function test_admin_can_page_users_with_offset_totals_without_passwords(): void
    {
        $this->seedRegularUsers();
        $token = $this->tokenFor('admin@example.com', 'admin');

        $response = $this->withToken($token)
            ->getJson('/api/v1/users?filter[role]=user&page[size]=2&page[number]=2&sort=name')
            ->assertOk()
            ->assertJsonPath('meta.strategy', 'offset')
            ->assertJsonPath('meta.page_number', 2)
            ->assertJsonPath('meta.total', 4)
            ->assertJsonPath('meta.total_pages', 2)
            ->assertJsonPath('data.0.name', 'Delta')
            ->assertJsonPath('data.1.name', 'Gamma');

        $this->assertArrayNotHasKey('password', $response->json('data.0'));
    }
PHP;

        return <<<PHP
<?php

namespace Tests\Feature;

use App\Infrastructure\Identity\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class UsersListVerticalSliceTest extends TestCase
{
    use RefreshDatabase;

$paginationTest

    public function test_non_admin_user_is_forbidden(): void
    {
        \$token = \$this->tokenFor('viewer@example.com', 'user');

        \$this->withToken(\$token)
            ->getJson('/api/v1/users')
            ->assertStatus(403)
            ->assertHeader('content-type', 'application/problem+json')
            ->assertJsonPath('title', 'Acceso denegado');
    }

    public function test_admin_can_filter_users_by_email_and_role(): void
    {
        \$this->seedRegularUsers();
        \$token = \$this->tokenFor('admin@example.com', 'admin');

        \$this->withToken(\$token)
            ->getJson('/api/v1/users?filter[email]=beta&filter[role]=user')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.email', 'beta@example.com');
    }

    public function test_unknown_user_sort_field_is_rejected(): void
    {
        \$token = \$this->tokenFor('admin@example.com', 'admin');

        \$this->withToken(\$token)
            ->getJson('/api/v1/users?sort=password')
            ->assertStatus(422)
            ->assertHeader('content-type', 'application/problem+json')
            ->assertJsonPath('title', 'Error de validación');
    }

    private function seedRegularUsers(): void
    {
        foreach ([
            ['Gamma', 'gamma@example.com'],
            ['Alpha', 'alpha@example.com'],
            ['Delta', 'delta@example.com'],
            ['Beta', 'beta@example.com'],
        ] as [\$name, \$email]) {
            User::query()->create([
                'name' => \$name,
                'email' => \$email,
                'password' => Hash::make('secret-password'),
                'role' => 'user',
            ]);
        }
    }

    private function tokenFor(string \$email, string \$role): string
    {
        User::query()->create([
            'name' => \$role === 'admin' ? 'Administrador' : 'Usuario',
            'email' => \$email,
            'password' => Hash::make('secret-password'),
            'role' => \$role,
        ]);

        \$response = \$this->postJson('/api/v1/auth/login', [
            'email' => \$email,
            'password' => 'secret-password',
            'device_name' => 'users-list-test',
        ])->assertOk();

        \$token = \$response->json('data.access_token');
        \$this->assertIsString(\$token);

        return \$token;
    }
}
PHP;
    }
}
