from pathlib import Path

path = Path('app/Infrastructure/Blueprint/LaravelZipBlueprintExporter.php')
text = path.read_text()


def replace_once(old: str, new: str, label: str) -> None:
    global text
    count = text.count(old)
    if count != 1:
        raise SystemExit(f'{label}: expected 1 match, found {count}')
    text = text.replace(old, new, 1)


def replace_block(start: str, end: str, new: str, label: str) -> None:
    global text
    start_index = text.find(start)
    if start_index < 0:
        raise SystemExit(f'{label}: start marker not found')
    end_index = text.find(end, start_index)
    if end_index < 0:
        raise SystemExit(f'{label}: end marker not found')
    text = text[:start_index] + new + text[end_index:]


replace_once(
    "        $hasAuthLogout = $this->hasEndpoint($manifest, 'auth.logout');\n        $hasProductsShow = $this->hasEndpoint($manifest, 'products.show');\n        $hasProductsList = $this->hasEndpoint($manifest, 'products.list');\n",
    "        $hasAuthLogout = $this->hasEndpoint($manifest, 'auth.logout');\n        $hasUsersList = $this->hasEndpoint($manifest, 'users.list');\n        $hasProductsShow = $this->hasEndpoint($manifest, 'products.show');\n        $hasProductsList = $this->hasEndpoint($manifest, 'products.list');\n\n        if ($hasProductsList || $hasUsersList) {\n            $files['app/Infrastructure/Database/DatabaseQueryPaginator.php'] = $this->databaseQueryPaginatorFile();\n            $files['app/Presentation/Http/Support/ListQueryValidator.php'] = $this->listQueryValidatorFile($manifest);\n        }\n",
    'build flags',
)

replace_once(
    "        if ($hasAuthLogout) {\n            $files['app/Application/Authentication/Contracts/TokenRevocationGateway.php'] = $this->tokenRevocationGatewayContractFile();\n            $files['app/Application/Authentication/UseCases/LogoutUser.php'] = $this->logoutUserUseCaseFile();\n            $files['app/Infrastructure/Authentication/SanctumTokenRevocationGateway.php'] = $this->sanctumTokenRevocationGatewayFile();\n            $files['tests/Feature/AuthLogoutVerticalSliceTest.php'] = $this->authLogoutVerticalSliceTestFile();\n        }\n\n        if ($hasProductsShow || $hasProductsList) {",
    "        if ($hasAuthLogout) {\n            $files['app/Application/Authentication/Contracts/TokenRevocationGateway.php'] = $this->tokenRevocationGatewayContractFile();\n            $files['app/Application/Authentication/UseCases/LogoutUser.php'] = $this->logoutUserUseCaseFile();\n            $files['app/Infrastructure/Authentication/SanctumTokenRevocationGateway.php'] = $this->sanctumTokenRevocationGatewayFile();\n            $files['tests/Feature/AuthLogoutVerticalSliceTest.php'] = $this->authLogoutVerticalSliceTestFile();\n        }\n\n        if ($hasUsersList) {\n            $files['app/Application/Users/Contracts/UserListRepository.php'] = $this->userListRepositoryContractFile();\n            $files['app/Application/Users/Data/UserListItem.php'] = $this->userListItemFile();\n            $files['app/Application/Users/Data/UserPage.php'] = $this->userPageFile();\n            $files['app/Application/Users/UseCases/ListUsers.php'] = $this->listUsersUseCaseFile();\n            $files['app/Infrastructure/Users/DatabaseUserListRepository.php'] = $this->databaseUserListRepositoryFile();\n            $files['tests/Feature/UsersListVerticalSliceTest.php'] = $this->usersListVerticalSliceTestFile($manifest);\n        }\n\n        if ($hasProductsShow || $hasProductsList) {",
    'users build files',
)

replace_once(
    "            $files['app/Infrastructure/Products/DatabaseProductListRepository.php'] = $this->databaseProductListRepositoryFile();\n            $files['app/Presentation/Http/Support/ProductListQueryValidator.php'] = $this->productListQueryValidatorFile($manifest);\n            $files['tests/Feature/ProductsListVerticalSliceTest.php'] = $this->productsListVerticalSliceTestFile($manifest);",
    "            $files['app/Infrastructure/Products/DatabaseProductListRepository.php'] = $this->databaseProductListRepositoryFile();\n            $files['tests/Feature/ProductsListVerticalSliceTest.php'] = $this->productsListVerticalSliceTestFile($manifest);",
    'remove product-specific validator output',
)

replace_once(
    "        if ($this->hasEndpoint($manifest, 'auth.login') || $this->hasEndpoint($manifest, 'products.show') || $this->hasEndpoint($manifest, 'products.list')) {\n            $requireDev['mockery/mockery'] = '^1.6';\n        }",
    "        if ($this->hasEndpoint($manifest, 'auth.login') || $this->hasEndpoint($manifest, 'users.list') || $this->hasEndpoint($manifest, 'products.show') || $this->hasEndpoint($manifest, 'products.list')) {\n            $requireDev['mockery/mockery'] = '^1.6';\n        }",
    'composer mockery condition',
)

replace_once(
    "        if ($endpoint['id'] === 'auth.logout') {\n            return $this->authLogoutControllerFile($className);\n        }\n        if ($endpoint['id'] === 'products.list') {",
    "        if ($endpoint['id'] === 'auth.logout') {\n            return $this->authLogoutControllerFile($className);\n        }\n        if ($endpoint['id'] === 'users.list') {\n            return $this->usersListControllerFile($className);\n        }\n        if ($endpoint['id'] === 'products.list') {",
    'users controller dispatch',
)

replace_once(
    "        if ($this->hasEndpoint($manifest, 'auth.logout')) {\n            $imports[] = 'use App\\\\Application\\\\Authentication\\\\Contracts\\\\TokenRevocationGateway;';\n            $imports[] = 'use App\\\\Infrastructure\\\\Authentication\\\\SanctumTokenRevocationGateway;';\n            $registerLines[] = '        $this->app->bind(TokenRevocationGateway::class, SanctumTokenRevocationGateway::class);';\n        }\n        if ($this->hasEndpoint($manifest, 'products.show')) {",
    "        if ($this->hasEndpoint($manifest, 'auth.logout')) {\n            $imports[] = 'use App\\\\Application\\\\Authentication\\\\Contracts\\\\TokenRevocationGateway;';\n            $imports[] = 'use App\\\\Infrastructure\\\\Authentication\\\\SanctumTokenRevocationGateway;';\n            $registerLines[] = '        $this->app->bind(TokenRevocationGateway::class, SanctumTokenRevocationGateway::class);';\n        }\n        if ($this->hasEndpoint($manifest, 'users.list')) {\n            $imports[] = 'use App\\\\Application\\\\Users\\\\Contracts\\\\UserListRepository;';\n            $imports[] = 'use App\\\\Infrastructure\\\\Users\\\\DatabaseUserListRepository;';\n            $registerLines[] = '        $this->app->bind(UserListRepository::class, DatabaseUserListRepository::class);';\n        }\n        if ($this->hasEndpoint($manifest, 'products.show')) {",
    'users repository binding',
)

replace_once(
    "            static fn (array $endpoint): bool => ! in_array($endpoint['id'], ['auth.login', 'auth.logout', 'products.list', 'products.show'], true),",
    "            static fn (array $endpoint): bool => ! in_array($endpoint['id'], ['auth.login', 'auth.logout', 'users.list', 'products.list', 'products.show'], true),",
    'contract executable list',
)

replace_once(
    "        $databaseEnvironment = ($this->hasEndpoint($manifest, 'auth.login') || $this->hasEndpoint($manifest, 'products.show') || $this->hasEndpoint($manifest, 'products.list'))",
    "        $databaseEnvironment = ($this->hasEndpoint($manifest, 'auth.login') || $this->hasEndpoint($manifest, 'users.list') || $this->hasEndpoint($manifest, 'products.show') || $this->hasEndpoint($manifest, 'products.list'))",
    'phpunit database condition',
)

replace_once(
    "                            if ($endpoint['id'] === 'products.list') {\n                                $parameters[] = '        - { name: \"filter[id]\", in: query, schema: { type: string }, description: \"Filtra por identificador exacto.\" }';\n                                $parameters[] = '        - { name: \"filter[name]\", in: query, schema: { type: string }, description: \"Filtra por coincidencia parcial del nombre.\" }';\n                            } else {",
    "                            if ($endpoint['id'] === 'products.list') {\n                                $parameters[] = '        - { name: \"filter[id]\", in: query, schema: { type: string }, description: \"Filtra por identificador exacto.\" }';\n                                $parameters[] = '        - { name: \"filter[name]\", in: query, schema: { type: string }, description: \"Filtra por coincidencia parcial del nombre.\" }';\n                            } elseif ($endpoint['id'] === 'users.list') {\n                                $parameters[] = '        - { name: \"filter[id]\", in: query, schema: { type: string }, description: \"Filtra por identificador exacto.\" }';\n                                $parameters[] = '        - { name: \"filter[name]\", in: query, schema: { type: string }, description: \"Filtra por coincidencia parcial del nombre.\" }';\n                                $parameters[] = '        - { name: \"filter[email]\", in: query, schema: { type: string }, description: \"Filtra por coincidencia parcial del correo electrónico.\" }';\n                                $parameters[] = '        - { name: \"filter[role]\", in: query, schema: { type: string }, description: \"Filtra por rol exacto.\" }';\n                            } else {",
    'generated openapi user filters',
)

replace_once(
    "                            $description = $endpoint['id'] === 'products.list'\n                                ? 'Campos permitidos: id y name; separados por coma; prefijo - para descendente.'\n                                : 'Campos de orden separados por coma; prefijo - para descendente.';",
    "                            $description = match ($endpoint['id']) {\n                                'products.list' => 'Campos permitidos: id y name; separados por coma; prefijo - para descendente.',\n                                'users.list' => 'Campos permitidos: id, name, email y role; separados por coma; prefijo - para descendente.',\n                                default => 'Campos de orden separados por coma; prefijo - para descendente.',\n                            };",
    'generated openapi user sorting',
)

replace_once(
    "                    } elseif ($endpoint['id'] === 'products.list') {\n                        $lines[] = \"        '200':\";\n                        $lines[] = '          description: \"Listado paginado de productos.\"';\n                        $lines[] = '          content: { application/json: { schema: { type: object, required: [data, meta], properties: { data: { type: array, items: { $ref: \"#/components/schemas/Product\" } }, meta: { $ref: \"#/components/schemas/ProductListMeta\" } } } } }';",
    "                    } elseif ($endpoint['id'] === 'users.list') {\n                        $lines[] = \"        '200':\";\n                        $lines[] = '          description: \"Listado paginado de usuarios.\"';\n                        $lines[] = '          content: { application/json: { schema: { type: object, required: [data, meta], properties: { data: { type: array, items: { $ref: \"#/components/schemas/UserListItem\" } }, meta: { $ref: \"#/components/schemas/ListMeta\" } } } } }';\n                        $lines[] = \"        '401':\";\n                        $lines[] = '          description: \"Autenticación requerida.\"';\n                        $lines[] = '          content: { application/problem+json: { schema: { $ref: \"#/components/schemas/ProblemDetails\" } } }';\n                        if ($governance['rbac']) {\n                            $lines[] = \"        '403':\";\n                            $lines[] = '          description: \"Se requieren privilegios de administrador.\"';\n                            $lines[] = '          content: { application/problem+json: { schema: { $ref: \"#/components/schemas/ProblemDetails\" } } }';\n                        }\n                        $lines[] = \"        '422':\";\n                        $lines[] = '          description: \"Parámetros de listado inválidos.\"';\n                        $lines[] = '          content: { application/problem+json: { schema: { $ref: \"#/components/schemas/ProblemDetails\" } } }';\n                    } elseif ($endpoint['id'] === 'products.list') {\n                        $lines[] = \"        '200':\";\n                        $lines[] = '          description: \"Listado paginado de productos.\"';\n                        $lines[] = '          content: { application/json: { schema: { type: object, required: [data, meta], properties: { data: { type: array, items: { $ref: \"#/components/schemas/Product\" } }, meta: { $ref: \"#/components/schemas/ListMeta\" } } } } }';",
    'generated openapi user responses',
)

replace_once(
    "        if ($this->hasEndpoint($manifest, 'products.list')) {\n            $lines[] = '    ProductListMeta:';",
    "        if ($this->hasEndpoint($manifest, 'users.list')) {\n            $lines[] = '    UserListItem:';\n            $lines[] = '      type: object';\n            $lines[] = '      required: [id, name, email, role]';\n            $lines[] = '      properties:';\n            $lines[] = '        id: { type: string }';\n            $lines[] = '        name: { type: string }';\n            $lines[] = '        email: { type: string, format: email }';\n            $lines[] = '        role: { type: string }';\n        }\n        if ($this->hasEndpoint($manifest, 'products.list') || $this->hasEndpoint($manifest, 'users.list')) {\n            $lines[] = '    ListMeta:';",
    'generated openapi shared list meta',
)

replace_once(
    "            $status = in_array($endpoint['id'], ['auth.login', 'auth.logout', 'products.list', 'products.show'], true) ? 'Ejecutable' : 'Stub 501';",
    "            $status = in_array($endpoint['id'], ['auth.login', 'auth.logout', 'users.list', 'products.list', 'products.show'], true) ? 'Ejecutable' : 'Stub 501';",
    'readme executable statuses',
)

replace_once(
    "`auth.login`, `auth.logout`, `products.list` y `products.show` se exportan como vertical slices ejecutables cuando están seleccionados.",
    "`auth.login`, `auth.logout`, `users.list`, `products.list` y `products.show` se exportan como vertical slices ejecutables cuando están seleccionados.",
    'readme executable text',
)

products_controller = r'''    private function productsListControllerFile(string $className): string
    {
        return <<<PHP
<?php

namespace App\Presentation\Http\Controllers\Generated;

use App\Application\Products\UseCases\ListProducts;
use App\Presentation\Http\Support\ListQueryValidator;
use App\Presentation\Http\Support\ProblemDetails;
use App\Presentation\Http\Support\QueryOptionsParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

final readonly class $className
{
    public function __construct(
        private ListProducts \$listProducts,
        private QueryOptionsParser \$queryOptionsParser,
        private ListQueryValidator \$queryValidator,
    ) {
        //
    }

    public function __invoke(Request \$request): JsonResponse
    {
        \$this->queryValidator->validate(\$request, ['id', 'name'], ['id', 'name']);

        try {
            \$page = \$this->listProducts->handle(\$this->queryOptionsParser->parse(\$request));
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

'''
replace_block(
    "    private function productsListControllerFile(string $className): string\n    {",
    "    private function productListRepositoryContractFile(): string\n    {",
    products_controller,
    'products controller shared validator',
)

product_repo = r'''    private function databaseProductListRepositoryFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Infrastructure\Products;

use App\Application\Products\Contracts\ProductListRepository;
use App\Application\Products\Data\ProductPage;
use App\Application\Shared\Query\QueryOptions;
use App\Domain\Products\Product;
use App\Infrastructure\Database\DatabaseQueryPaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final readonly class DatabaseProductListRepository implements ProductListRepository
{
    public function __construct(private DatabaseQueryPaginator $paginator)
    {
        //
    }

    public function paginate(QueryOptions $options): ProductPage
    {
        $query = DB::table('products')->select(['id', 'name']);
        $this->applyFilters($query, $options->filters);
        $sorts = $this->paginator->sorts($options->sort, ['id', 'name']);
        $page = $this->paginator->paginate($query, $sorts, $options);

        return new ProductPage(
            items: $this->products($page['items']),
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
            }
        }
    }

    private function products(array $rows): array
    {
        return array_map(
            static fn (object $row): Product => new Product(id: (string) $row->id, name: (string) $row->name),
            $rows,
        );
    }
}
PHP;
    }

'''
replace_block(
    "    private function databaseProductListRepositoryFile(): string\n    {",
    "    private function productListQueryValidatorFile(array $manifest): string\n    {",
    product_repo,
    'product repository shared paginator',
)

list_validator = r'''    private function listQueryValidatorFile(array $manifest): string
    {
        $pagination = $manifest['governance']['pagination'];
        $maxSize = (int) $pagination['max_size'];
        $strategy = $pagination['strategy'];
        $pageRule = $strategy === 'cursor' ? 'array:size,cursor' : 'array:size,number';
        $strategyRule = $strategy === 'cursor'
            ? "            'page.cursor' => ['sometimes', 'string', 'max:2048'],"
            : "            'page.number' => ['sometimes', 'integer', 'min:1'],";
        $filtering = $manifest['governance']['filtering'] ? 'true' : 'false';
        $sorting = $manifest['governance']['sorting'] ? 'true' : 'false';

        return <<<PHP
<?php

namespace App\Presentation\Http\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

final class ListQueryValidator
{
    public function validate(Request \$request, array \$filterFields, array \$sortFields): void
    {
        \$rules = [
            'page' => ['sometimes', '$pageRule'],
            'page.size' => ['sometimes', 'integer', 'min:1', 'max:$maxSize'],
$strategyRule
        ];

        if ($filtering) {
            \$rules['filter'] = ['sometimes', 'array:'.implode(',', \$filterFields)];
            foreach (\$filterFields as \$field) {
                \$rules['filter.'.\$field] = ['sometimes', 'string', 'max:255'];
            }
        } else {
            \$rules['filter'] = ['prohibited'];
        }

        \$rules['sort'] = $sorting ? ['sometimes', 'string', 'max:255'] : ['prohibited'];

        \$validator = Validator::make(\$request->query(), \$rules, [
            'page.array' => 'Los parámetros de paginación no son válidos para la estrategia configurada.',
            'page.size.integer' => 'page[size] debe ser un entero.',
            'page.size.min' => 'page[size] debe ser mayor que cero.',
            'page.size.max' => 'page[size] supera el máximo permitido.',
            'page.number.integer' => 'page[number] debe ser un entero.',
            'page.number.min' => 'page[number] debe ser mayor que cero.',
            'filter.array' => 'filter contiene campos no permitidos para este listado.',
            'filter.prohibited' => 'Los filtros están deshabilitados para este blueprint.',
            'sort.prohibited' => 'El ordenamiento está deshabilitado para este blueprint.',
        ]);

        if ($sorting) {
            \$validator->after(function (\$validator) use (\$request, \$sortFields): void {
                \$sort = \$request->query('sort');
                if (\$sort === null || \$sort === '') {
                    return;
                }
                if (! is_string(\$sort)) {
                    \$validator->errors()->add('sort', 'El parámetro sort debe ser una cadena.');

                    return;
                }

                \$seen = [];
                foreach (explode(',', \$sort) as \$token) {
                    \$token = trim(\$token);
                    \$field = ltrim(\$token, '-');
                    if (\$token === '' || ! in_array(\$field, \$sortFields, true) || isset(\$seen[\$field])) {
                        \$validator->errors()->add('sort', 'sort contiene campos no permitidos o repetidos.');

                        return;
                    }
                    \$seen[\$field] = true;
                }
            });
        }

        \$validator->validate();
    }
}
PHP;
    }

'''
replace_block(
    "    private function productListQueryValidatorFile(array $manifest): string\n    {",
    "    private function productsListVerticalSliceTestFile(array $manifest): string\n    {",
    list_validator,
    'shared list validator',
)

paginator_method = r'''    private function databaseQueryPaginatorFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Infrastructure\Database;

use App\Application\Shared\Query\QueryOptions;
use Illuminate\Database\Query\Builder;
use InvalidArgumentException;
use JsonException;

final class DatabaseQueryPaginator
{
    public function sorts(?string $sort, array $allowedFields): array
    {
        if (! in_array('id', $allowedFields, true)) {
            throw new InvalidArgumentException('Stable lists require id as a tie breaker.');
        }

        $tokens = $sort === null || trim($sort) === '' ? ['id'] : explode(',', $sort);
        $sorts = [];
        $seen = [];

        foreach ($tokens as $token) {
            $token = trim($token);
            $direction = str_starts_with($token, '-') ? 'desc' : 'asc';
            $field = ltrim($token, '-');
            if (! in_array($field, $allowedFields, true) || isset($seen[$field])) {
                throw new InvalidArgumentException('Unsupported sort definition.');
            }
            $seen[$field] = true;
            $sorts[] = [$field, $direction];
        }

        if (! isset($seen['id'])) {
            $sorts[] = ['id', 'asc'];
        }

        return $sorts;
    }

    public function paginate(Builder $query, array $sorts, QueryOptions $options): array
    {
        return match ($options->paginationStrategy) {
            'cursor' => $this->cursorPage($query, $sorts, $options),
            'offset' => $this->offsetPage($query, $sorts, $options),
            default => throw new InvalidArgumentException('Unsupported pagination strategy.'),
        };
    }

    private function offsetPage(Builder $query, array $sorts, QueryOptions $options): array
    {
        $total = (clone $query)->count();
        $this->applySorts($query, $sorts);
        $rows = $query
            ->offset(($options->pageNumber - 1) * $options->pageSize)
            ->limit($options->pageSize)
            ->get();
        $totalPages = $total === 0 ? 0 : (int) ceil($total / $options->pageSize);

        return [
            'items' => $rows->all(),
            'meta' => [
                'strategy' => 'offset',
                'page_size' => $options->pageSize,
                'page_number' => $options->pageNumber,
                'total' => $total,
                'total_pages' => $totalPages,
                'has_more' => $options->pageNumber < $totalPages,
            ],
        ];
    }

    private function cursorPage(Builder $query, array $sorts, QueryOptions $options): array
    {
        if ($options->cursor !== null && $options->cursor !== '') {
            $values = $this->decodeCursor($options->cursor, $sorts);
            $this->applyCursor($query, $sorts, $values);
        }

        $this->applySorts($query, $sorts);
        $rows = $query->limit($options->pageSize + 1)->get();
        $hasMore = $rows->count() > $options->pageSize;
        $visibleRows = $rows->take($options->pageSize)->values();
        $lastRow = $visibleRows->last();
        $nextCursor = $hasMore && is_object($lastRow) ? $this->encodeCursor($sorts, $lastRow) : null;

        return [
            'items' => $visibleRows->all(),
            'meta' => [
                'strategy' => 'cursor',
                'page_size' => $options->pageSize,
                'has_more' => $hasMore,
                'next_cursor' => $nextCursor,
            ],
        ];
    }

    private function applySorts(Builder $query, array $sorts): void
    {
        foreach ($sorts as [$field, $direction]) {
            $query->orderBy($field, $direction);
        }
    }

    private function applyCursor(Builder $query, array $sorts, array $values): void
    {
        $query->where(function (Builder $outer) use ($sorts, $values): void {
            foreach ($sorts as $index => [$field, $direction]) {
                $outer->orWhere(function (Builder $branch) use ($sorts, $values, $index, $field, $direction): void {
                    for ($previous = 0; $previous < $index; $previous++) {
                        [$previousField] = $sorts[$previous];
                        $branch->where($previousField, '=', $values[$previousField]);
                    }
                    $branch->where($field, $direction === 'asc' ? '>' : '<', $values[$field]);
                });
            }
        });
    }

    private function encodeCursor(array $sorts, object $row): string
    {
        $values = [];
        foreach ($sorts as [$field]) {
            $values[$field] = (string) $row->{$field};
        }
        $payload = json_encode([
            'sort' => $this->serializedSorts($sorts),
            'values' => $values,
        ], JSON_THROW_ON_ERROR);

        return rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
    }

    private function decodeCursor(string $cursor, array $sorts): array
    {
        try {
            $base64 = strtr($cursor, '-_', '+/');
            $base64 .= str_repeat('=', (4 - strlen($base64) % 4) % 4);
            $decoded = base64_decode($base64, true);
            if ($decoded === false) {
                throw new InvalidArgumentException('Invalid cursor encoding.');
            }
            $payload = json_decode($decoded, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidArgumentException('Invalid cursor payload.');
        }

        if (! is_array($payload)
            || ($payload['sort'] ?? null) !== $this->serializedSorts($sorts)
            || ! is_array($payload['values'] ?? null)) {
            throw new InvalidArgumentException('Cursor does not match sorting.');
        }

        $values = [];
        foreach ($sorts as [$field]) {
            $value = $payload['values'][$field] ?? null;
            if (! is_string($value)) {
                throw new InvalidArgumentException('Cursor is missing sort values.');
            }
            $values[$field] = $value;
        }

        return $values;
    }

    private function serializedSorts(array $sorts): array
    {
        return array_map(static fn (array $sort): string => ($sort[1] === 'desc' ? '-' : '').$sort[0], $sorts);
    }
}
PHP;
    }

'''
replace_once(
    "    private function serviceProviderFile(array $manifest): string\n    {",
    paginator_method + "    private function serviceProviderFile(array $manifest): string\n    {",
    'shared paginator method',
)

users_methods = r'''    private function usersListControllerFile(string $className): string
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

    private function userListItemFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Application\Users\Data;

final readonly class UserListItem
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string $role,
    ) {
        //
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
        ];
    }
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
    /** @param list<UserListItem> $items */
    public function __construct(
        public array $items,
        public array $meta,
    ) {
        //
    }

    public function toArray(): array
    {
        return [
            'data' => array_map(static fn (UserListItem $user): array => $user->toArray(), $this->items),
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
use App\Application\Users\Data\UserListItem;
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
                static fn (object $row): UserListItem => new UserListItem(
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

'''
replace_once(
    "    private function productsListVerticalSliceTestFile(array $manifest): string\n    {",
    users_methods + "    private function productsListVerticalSliceTestFile(array $manifest): string\n    {",
    'users vertical slice methods',
)

path.write_text(text)
print('U0.9 exporter patch applied')
