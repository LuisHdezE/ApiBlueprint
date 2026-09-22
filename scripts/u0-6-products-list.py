from pathlib import Path

path = Path('app/Infrastructure/Blueprint/LaravelZipBlueprintExporter.php')
source = path.read_text()


def replace_once(old: str, new: str, label: str) -> None:
    global source
    count = source.count(old)
    if count != 1:
        raise SystemExit(f'{label}: expected exactly one match, got {count}')
    source = source.replace(old, new, 1)


replace_once(
"""        if ($this->hasEndpoint($manifest, 'products.show')) {
            $files['database/database.sqlite'] = '';
            $files['database/migrations/2026_01_01_000000_create_products_table.php'] = $this->productsMigrationFile();
            $files['app/Domain/Products/Product.php'] = $this->productEntityFile();
            $files['app/Application/Products/Contracts/ProductReadRepository.php'] = $this->productReadRepositoryContractFile();
            $files['app/Application/Products/UseCases/GetProduct.php'] = $this->getProductUseCaseFile();
            $files['app/Infrastructure/Products/DatabaseProductReadRepository.php'] = $this->databaseProductReadRepositoryFile();
            $files['tests/Feature/ProductsShowVerticalSliceTest.php'] = $this->productsShowVerticalSliceTestFile();
        }
""",
"""        $hasProductsShow = $this->hasEndpoint($manifest, 'products.show');
        $hasProductsList = $this->hasEndpoint($manifest, 'products.list');

        if ($hasProductsShow || $hasProductsList) {
            $files['database/database.sqlite'] = '';
            $files['database/migrations/2026_01_01_000000_create_products_table.php'] = $this->productsMigrationFile();
            $files['app/Domain/Products/Product.php'] = $this->productEntityFile();
        }

        if ($hasProductsShow) {
            $files['app/Application/Products/Contracts/ProductReadRepository.php'] = $this->productReadRepositoryContractFile();
            $files['app/Application/Products/UseCases/GetProduct.php'] = $this->getProductUseCaseFile();
            $files['app/Infrastructure/Products/DatabaseProductReadRepository.php'] = $this->databaseProductReadRepositoryFile();
            $files['tests/Feature/ProductsShowVerticalSliceTest.php'] = $this->productsShowVerticalSliceTestFile();
        }

        if ($hasProductsList) {
            $files['app/Application/Products/Contracts/ProductListRepository.php'] = $this->productListRepositoryContractFile();
            $files['app/Application/Products/Data/ProductPage.php'] = $this->productPageFile();
            $files['app/Application/Products/UseCases/ListProducts.php'] = $this->listProductsUseCaseFile();
            $files['app/Infrastructure/Products/DatabaseProductListRepository.php'] = $this->databaseProductListRepositoryFile();
            $files['app/Presentation/Http/Support/ProductListQueryValidator.php'] = $this->productListQueryValidatorFile($manifest);
            $files['tests/Feature/ProductsListVerticalSliceTest.php'] = $this->productsListVerticalSliceTestFile($manifest);
        }
""",
'buildFiles product slices',
)

replace_once(
"""        if ($this->hasEndpoint($manifest, 'products.show')) {
            $requireDev['mockery/mockery'] = '^1.6';
        }
""",
"""        if ($this->hasEndpoint($manifest, 'products.show') || $this->hasEndpoint($manifest, 'products.list')) {
            $requireDev['mockery/mockery'] = '^1.6';
        }
""",
'composer mockery condition',
)

replace_once(
"""        if ($endpoint['id'] === 'products.show') {
            return $this->productsShowControllerFile($className);
        }
""",
"""        if ($endpoint['id'] === 'products.list') {
            return $this->productsListControllerFile($className);
        }
        if ($endpoint['id'] === 'products.show') {
            return $this->productsShowControllerFile($className);
        }
""",
'controller dispatch',
)

replace_once(
"""        if ($this->hasEndpoint($manifest, 'products.show')) {
            $imports[] = 'use App\\\\Application\\\\Products\\\\Contracts\\\\ProductReadRepository;';
            $imports[] = 'use App\\\\Infrastructure\\\\Products\\\\DatabaseProductReadRepository;';
            $registerLines[] = '        $this->app->bind(ProductReadRepository::class, DatabaseProductReadRepository::class);';
        }
""",
"""        if ($this->hasEndpoint($manifest, 'products.show')) {
            $imports[] = 'use App\\\\Application\\\\Products\\\\Contracts\\\\ProductReadRepository;';
            $imports[] = 'use App\\\\Infrastructure\\\\Products\\\\DatabaseProductReadRepository;';
            $registerLines[] = '        $this->app->bind(ProductReadRepository::class, DatabaseProductReadRepository::class);';
        }
        if ($this->hasEndpoint($manifest, 'products.list')) {
            $imports[] = 'use App\\\\Application\\\\Products\\\\Contracts\\\\ProductListRepository;';
            $imports[] = 'use App\\\\Infrastructure\\\\Products\\\\DatabaseProductListRepository;';
            $registerLines[] = '        $this->app->bind(ProductListRepository::class, DatabaseProductListRepository::class);';
        }
""",
'service provider products bindings',
)

replace_once(
"""        $stubEndpoints = array_values(array_filter(
            $manifest['endpoints'],
            static fn (array $endpoint): bool => $endpoint['id'] !== 'products.show',
        ));
""",
"""        $stubEndpoints = array_values(array_filter(
            $manifest['endpoints'],
            static fn (array $endpoint): bool => ! in_array($endpoint['id'], ['products.list', 'products.show'], true),
        ));
""",
'contract executable endpoints',
)

replace_once(
"""        $databaseEnvironment = $this->hasEndpoint($manifest, 'products.show')
""",
"""        $databaseEnvironment = ($this->hasEndpoint($manifest, 'products.show') || $this->hasEndpoint($manifest, 'products.list'))
""",
'phpunit database condition',
)

replace_once(
"""                        if ($governance['filtering']) {
                            $parameters[] = '        - { name: \"filter[field]\", in: query, schema: { type: string }, description: \"Filtro por campo permitido.\" }';
                        }
                        if ($governance['sorting']) {
                            $parameters[] = '        - { name: sort, in: query, schema: { type: string }, description: \"Campos de orden separados por coma; prefijo - para descendente.\" }';
                        }
""",
"""                        if ($governance['filtering']) {
                            if ($endpoint['id'] === 'products.list') {
                                $parameters[] = '        - { name: \"filter[id]\", in: query, schema: { type: string }, description: \"Filtra por identificador exacto.\" }';
                                $parameters[] = '        - { name: \"filter[name]\", in: query, schema: { type: string }, description: \"Filtra por coincidencia parcial del nombre.\" }';
                            } else {
                                $parameters[] = '        - { name: \"filter[field]\", in: query, schema: { type: string }, description: \"Filtro por campo permitido.\" }';
                            }
                        }
                        if ($governance['sorting']) {
                            $description = $endpoint['id'] === 'products.list'
                                ? 'Campos permitidos: id y name; separados por coma; prefijo - para descendente.'
                                : 'Campos de orden separados por coma; prefijo - para descendente.';
                            $parameters[] = '        - { name: sort, in: query, schema: { type: string }, description: '.$this->yamlString($description).' }';
                        }
""",
'openapi product list parameters',
)

replace_once(
"""                    if ($endpoint['id'] === 'products.show') {
                        $lines[] = \"        '200':\";
                        $lines[] = '          description: \"Producto encontrado.\"';
                        $lines[] = '          content: { application/json: { schema: { type: object, required: [data], properties: { data: { $ref: \"#/components/schemas/Product\" } } } } }';
                        $lines[] = \"        '404':\";
                        $lines[] = '          description: \"Producto no encontrado.\"';
                        $lines[] = '          content: { application/problem+json: { schema: { $ref: \"#/components/schemas/ProblemDetails\" } } }';
                    } else {
                        $lines[] = \"        '501':\";
                        $lines[] = '          description: \"Endpoint generado pendiente de implementación.\"';
                    }
""",
"""                    if ($endpoint['id'] === 'products.list') {
                        $lines[] = \"        '200':\";
                        $lines[] = '          description: \"Listado paginado de productos.\"';
                        $lines[] = '          content: { application/json: { schema: { type: object, required: [data, meta], properties: { data: { type: array, items: { $ref: \"#/components/schemas/Product\" } }, meta: { $ref: \"#/components/schemas/ProductListMeta\" } } } } }';
                        $lines[] = \"        '422':\";
                        $lines[] = '          description: \"Parámetros de listado inválidos.\"';
                        $lines[] = '          content: { application/problem+json: { schema: { $ref: \"#/components/schemas/ProblemDetails\" } } }';
                    } elseif ($endpoint['id'] === 'products.show') {
                        $lines[] = \"        '200':\";
                        $lines[] = '          description: \"Producto encontrado.\"';
                        $lines[] = '          content: { application/json: { schema: { type: object, required: [data], properties: { data: { $ref: \"#/components/schemas/Product\" } } } } }';
                        $lines[] = \"        '404':\";
                        $lines[] = '          description: \"Producto no encontrado.\"';
                        $lines[] = '          content: { application/problem+json: { schema: { $ref: \"#/components/schemas/ProblemDetails\" } } }';
                    } else {
                        $lines[] = \"        '501':\";
                        $lines[] = '          description: \"Endpoint generado pendiente de implementación.\"';
                    }
""",
'openapi product list response',
)

replace_once(
"""        if ($this->hasEndpoint($manifest, 'products.show')) {
            $lines[] = '    Product:';
            $lines[] = '      type: object';
            $lines[] = '      required: [id, name]';
            $lines[] = '      properties:';
            $lines[] = '        id: { type: string }';
            $lines[] = '        name: { type: string }';
        }
""",
"""        if ($this->hasEndpoint($manifest, 'products.show') || $this->hasEndpoint($manifest, 'products.list')) {
            $lines[] = '    Product:';
            $lines[] = '      type: object';
            $lines[] = '      required: [id, name]';
            $lines[] = '      properties:';
            $lines[] = '        id: { type: string }';
            $lines[] = '        name: { type: string }';
        }
        if ($this->hasEndpoint($manifest, 'products.list')) {
            $lines[] = '    ProductListMeta:';
            $lines[] = '      type: object';
            $lines[] = '      required: [strategy, page_size, has_more]';
            $lines[] = '      properties:';
            $lines[] = '        strategy: { type: string, enum: [cursor, offset] }';
            $lines[] = '        page_size: { type: integer }';
            $lines[] = '        has_more: { type: boolean }';
            $lines[] = '        next_cursor: { type: [string, \"null\"] }';
            $lines[] = '        page_number: { type: integer }';
            $lines[] = '        total: { type: integer }';
            $lines[] = '        total_pages: { type: integer }';
        }
""",
'openapi product schemas',
)

replace_once(
"""            $status = $endpoint['id'] === 'products.show' ? 'Ejecutable' : 'Stub 501';
""",
"""            $status = in_array($endpoint['id'], ['products.list', 'products.show'], true) ? 'Ejecutable' : 'Stub 501';
""",
'readme endpoint status',
)
replace_once(
"""        $migrationStep = $this->hasEndpoint($manifest, 'products.show') ? \"php artisan migrate\\n\" : '';
""",
"""        $migrationStep = ($this->hasEndpoint($manifest, 'products.show') || $this->hasEndpoint($manifest, 'products.list')) ? \"php artisan migrate\\n\" : '';
""",
'readme migration condition',
)
replace_once(
"""`products.show` se exporta como vertical slice ejecutable cuando está seleccionado. Los demás endpoints conservan HTTP 501 hasta que su receta ejecutable sea incorporada. Los endpoints y capacidades no seleccionados no se incluyen como infraestructura dormida.
""",
"""`products.list` y `products.show` se exportan como vertical slices ejecutables cuando están seleccionados. Los demás endpoints conservan HTTP 501 hasta que su receta ejecutable sea incorporada. Los endpoints y capacidades no seleccionados no se incluyen como infraestructura dormida.
""",
'readme slice note',
)

anchor = """    private function productsShowControllerFile(string $className): string
    {
"""
if source.count(anchor) != 1:
    raise SystemExit(f'products show method anchor: expected one match, got {source.count(anchor)}')

new_methods = r'''    private function productsListControllerFile(string $className): string
    {
        return <<<PHP
<?php

namespace App\Presentation\Http\Controllers\Generated;

use App\Application\Products\UseCases\ListProducts;
use App\Presentation\Http\Support\ProblemDetails;
use App\Presentation\Http\Support\ProductListQueryValidator;
use App\Presentation\Http\Support\QueryOptionsParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

final readonly class $className
{
    public function __construct(
        private ListProducts \$listProducts,
        private QueryOptionsParser \$queryOptionsParser,
        private ProductListQueryValidator \$queryValidator,
    ) {
        //
    }

    public function __invoke(Request \$request): JsonResponse
    {
        \$this->queryValidator->validate(\$request);

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

    private function productListRepositoryContractFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Application\Products\Contracts;

use App\Application\Products\Data\ProductPage;
use App\Application\Shared\Query\QueryOptions;

interface ProductListRepository
{
    public function paginate(QueryOptions $options): ProductPage;
}
PHP;
    }

    private function productPageFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Application\Products\Data;

use App\Domain\Products\Product;

final readonly class ProductPage
{
    /** @param list<Product> $items */
    public function __construct(
        public array $items,
        public array $meta,
    ) {
        //
    }

    public function toArray(): array
    {
        return [
            'data' => array_map(static fn (Product $product): array => $product->toArray(), $this->items),
            'meta' => $this->meta,
        ];
    }
}
PHP;
    }

    private function listProductsUseCaseFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Application\Products\UseCases;

use App\Application\Products\Contracts\ProductListRepository;
use App\Application\Products\Data\ProductPage;
use App\Application\Shared\Query\QueryOptions;

final readonly class ListProducts
{
    public function __construct(private ProductListRepository $products)
    {
        //
    }

    public function handle(QueryOptions $options): ProductPage
    {
        return $this->products->paginate($options);
    }
}
PHP;
    }

    private function databaseProductListRepositoryFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Infrastructure\Products;

use App\Application\Products\Contracts\ProductListRepository;
use App\Application\Products\Data\ProductPage;
use App\Application\Shared\Query\QueryOptions;
use App\Domain\Products\Product;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use JsonException;

final class DatabaseProductListRepository implements ProductListRepository
{
    public function paginate(QueryOptions $options): ProductPage
    {
        $query = DB::table('products')->select(['id', 'name']);
        $this->applyFilters($query, $options->filters);
        $sorts = $this->sorts($options->sort);

        return match ($options->paginationStrategy) {
            'cursor' => $this->cursorPage($query, $sorts, $options),
            'offset' => $this->offsetPage($query, $sorts, $options),
            default => throw new InvalidArgumentException('Unsupported pagination strategy.'),
        };
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

    private function sorts(?string $sort): array
    {
        $tokens = $sort === null || trim($sort) === '' ? ['id'] : explode(',', $sort);
        $sorts = [];
        $seen = [];

        foreach ($tokens as $token) {
            $token = trim($token);
            $direction = str_starts_with($token, '-') ? 'desc' : 'asc';
            $field = ltrim($token, '-');
            if (! in_array($field, ['id', 'name'], true) || isset($seen[$field])) {
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

    private function offsetPage(Builder $query, array $sorts, QueryOptions $options): ProductPage
    {
        $total = (clone $query)->count();
        $this->applySorts($query, $sorts);
        $rows = $query
            ->offset(($options->pageNumber - 1) * $options->pageSize)
            ->limit($options->pageSize)
            ->get();
        $totalPages = $total === 0 ? 0 : (int) ceil($total / $options->pageSize);

        return new ProductPage(
            items: $this->products($rows->all()),
            meta: [
                'strategy' => 'offset',
                'page_size' => $options->pageSize,
                'page_number' => $options->pageNumber,
                'total' => $total,
                'total_pages' => $totalPages,
                'has_more' => $options->pageNumber < $totalPages,
            ],
        );
    }

    private function cursorPage(Builder $query, array $sorts, QueryOptions $options): ProductPage
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

        return new ProductPage(
            items: $this->products($visibleRows->all()),
            meta: [
                'strategy' => 'cursor',
                'page_size' => $options->pageSize,
                'has_more' => $hasMore,
                'next_cursor' => $nextCursor,
            ],
        );
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

    private function productListQueryValidatorFile(array $manifest): string
    {
        $pagination = $manifest['governance']['pagination'];
        $maxSize = (int) $pagination['max_size'];
        $strategy = $pagination['strategy'];
        $pageRule = $strategy === 'cursor' ? 'array:size,cursor' : 'array:size,number';
        $strategyRule = $strategy === 'cursor'
            ? "            'page.cursor' => ['sometimes', 'string', 'max:2048'],"
            : "            'page.number' => ['sometimes', 'integer', 'min:1'],";
        $filterRules = $manifest['governance']['filtering']
            ? "            'filter' => ['sometimes', 'array:id,name'],\n            'filter.id' => ['sometimes', 'string', 'max:255'],\n            'filter.name' => ['sometimes', 'string', 'max:255'],"
            : "            'filter' => ['prohibited'],";
        $sortRule = $manifest['governance']['sorting']
            ? "            'sort' => ['sometimes', 'string', 'max:255'],"
            : "            'sort' => ['prohibited'],";
        $sortAfter = $manifest['governance']['sorting']
            ? <<<'PHP'
        $validator->after(function ($validator) use ($request): void {
            $sort = $request->query('sort');
            if ($sort === null || $sort === '') {
                return;
            }
            if (! is_string($sort)) {
                $validator->errors()->add('sort', 'El parámetro sort debe ser una cadena.');

                return;
            }

            $seen = [];
            foreach (explode(',', $sort) as $token) {
                $token = trim($token);
                $field = ltrim($token, '-');
                if ($token === '' || ! in_array($field, ['id', 'name'], true) || isset($seen[$field])) {
                    $validator->errors()->add('sort', 'sort solo admite id y name, sin campos repetidos.');

                    return;
                }
                $seen[$field] = true;
            }
        });
PHP
            : '';

        return <<<PHP
<?php

namespace App\Presentation\Http\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

final class ProductListQueryValidator
{
    public function validate(Request \$request): void
    {
        \$validator = Validator::make(\$request->query(), [
            'page' => ['sometimes', '$pageRule'],
            'page.size' => ['sometimes', 'integer', 'min:1', 'max:$maxSize'],
$strategyRule
$filterRules
$sortRule
        ], [
            'page.array' => 'Los parámetros de paginación no son válidos para la estrategia configurada.',
            'page.size.integer' => 'page[size] debe ser un entero.',
            'page.size.min' => 'page[size] debe ser mayor que cero.',
            'page.size.max' => 'page[size] supera el máximo permitido.',
            'page.number.integer' => 'page[number] debe ser un entero.',
            'page.number.min' => 'page[number] debe ser mayor que cero.',
            'filter.array' => 'filter solo admite los campos id y name.',
            'filter.prohibited' => 'Los filtros están deshabilitados para este blueprint.',
            'sort.prohibited' => 'El ordenamiento está deshabilitado para este blueprint.',
        ]);
$sortAfter
        \$validator->validate();
    }
}
PHP;
    }

    private function productsListVerticalSliceTestFile(array $manifest): string
    {
        $strategy = $manifest['governance']['pagination']['strategy'];
        $paginationTest = $strategy === 'cursor'
            ? <<<'PHP'
    public function test_products_use_keyset_cursor_pagination(): void
    {
        $this->seedProducts();

        $first = $this->getJson('/api/v1/products?page[size]=2&sort=name')
            ->assertOk()
            ->assertJsonPath('meta.strategy', 'cursor')
            ->assertJsonPath('meta.has_more', true)
            ->assertJsonPath('data.0.name', 'Alpha')
            ->assertJsonPath('data.1.name', 'Beta');

        $cursor = $first->json('meta.next_cursor');
        $this->assertIsString($cursor);
        $this->assertNotSame('', $cursor);

        $this->getJson('/api/v1/products?page[size]=2&sort=name&page[cursor]='.rawurlencode($cursor))
            ->assertOk()
            ->assertJsonPath('meta.strategy', 'cursor')
            ->assertJsonPath('meta.has_more', false)
            ->assertJsonPath('data.0.name', 'Delta')
            ->assertJsonPath('data.1.name', 'Gamma');
    }

    public function test_invalid_cursor_uses_problem_details(): void
    {
        $this->seedProducts();

        $this->getJson('/api/v1/products?page[cursor]=not-a-valid-cursor')
            ->assertStatus(422)
            ->assertHeader('content-type', 'application/problem+json')
            ->assertJsonPath('title', 'Cursor de paginación inválido');
    }
PHP
            : <<<'PHP'
    public function test_products_use_offset_pagination_with_totals(): void
    {
        $this->seedProducts();

        $this->getJson('/api/v1/products?page[size]=2&page[number]=2&sort=name')
            ->assertOk()
            ->assertJsonPath('meta.strategy', 'offset')
            ->assertJsonPath('meta.page_number', 2)
            ->assertJsonPath('meta.total', 4)
            ->assertJsonPath('meta.total_pages', 2)
            ->assertJsonPath('meta.has_more', false)
            ->assertJsonPath('data.0.name', 'Delta')
            ->assertJsonPath('data.1.name', 'Gamma');
    }
PHP;
        $filteringTest = $manifest['governance']['filtering']
            ? <<<'PHP'
    public function test_products_can_be_filtered_by_name(): void
    {
        $this->seedProducts();

        $this->getJson('/api/v1/products?filter[name]=ta&sort=name')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Beta')
            ->assertJsonPath('data.1.name', 'Delta');
    }
PHP
            : '';
        $sortingTest = $manifest['governance']['sorting']
            ? <<<'PHP'
    public function test_products_support_deterministic_descending_sort(): void
    {
        $this->seedProducts();

        $this->getJson('/api/v1/products?page[size]=2&sort=-name')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Gamma')
            ->assertJsonPath('data.1.name', 'Delta');
    }

    public function test_unknown_sort_field_is_rejected(): void
    {
        $this->getJson('/api/v1/products?sort=price')
            ->assertStatus(422)
            ->assertHeader('content-type', 'application/problem+json')
            ->assertJsonPath('title', 'Error de validación');
    }
PHP
            : '';

        return <<<PHP
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ProductsListVerticalSliceTest extends TestCase
{
    use RefreshDatabase;

$paginationTest
$filteringTest
$sortingTest
    private function seedProducts(): void
    {
        DB::table('products')->insert([
            ['id' => 'product-001', 'name' => 'Gamma'],
            ['id' => 'product-002', 'name' => 'Alpha'],
            ['id' => 'product-003', 'name' => 'Delta'],
            ['id' => 'product-004', 'name' => 'Beta'],
        ]);
    }
}
PHP;
    }

'''
source = source.replace(anchor, new_methods + anchor, 1)

path.write_text(source)
