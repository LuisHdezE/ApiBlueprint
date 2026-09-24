<?php

namespace App\Infrastructure\Blueprint\Recipes;

final class ProductsListRecipe extends AbstractLaravelFeatureRecipe
{
    public function id(): string
    {
        return 'products.list';
    }

    public function endpointIds(): array
    {
        return [
            'products.list',
        ];
    }

    public function files(array $manifest): array
    {
        return [
            'app/Application/Products/Contracts/ProductListRepository.php' => $this->productListRepositoryContractFile(),
            'app/Application/Products/Data/ProductPage.php' => $this->productPageFile(),
            'app/Application/Products/UseCases/ListProducts.php' => $this->listProductsUseCaseFile(),
            'app/Infrastructure/Products/DatabaseProductListRepository.php' => $this->databaseProductListRepositoryFile(),
            'tests/Feature/ProductsListVerticalSliceTest.php' => $this->productsListVerticalSliceTestFile($manifest),
        ];
    }

    public function controller(array $endpoint, string $className): ?string
    {
        if (($endpoint['id'] ?? null) !== 'products.list') {
            return null;
        }

        return $this->productsListControllerFile($className);
    }

    public function serviceProviderImports(): array
    {
        return [
            'use App\\Application\\Products\\Contracts\\ProductListRepository;',
            'use App\\Infrastructure\\Products\\DatabaseProductListRepository;',
        ];
    }

    public function serviceProviderRegistrations(): array
    {
        return [
            '        $this->app->bind(ProductListRepository::class, DatabaseProductListRepository::class);',
        ];
    }

    public function composerRequireDev(): array
    {
        return [
            'mockery/mockery' => '^1.6',
        ];
    }

    private function productsListControllerFile(string $className): string
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

        $testMethods = implode("\n\n", array_values(array_filter([
            rtrim($paginationTest),
            rtrim($filteringTest),
            rtrim($sortingTest),
        ], static fn (string $test): bool => $test !== '')));

        return <<<PHP
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ProductsListVerticalSliceTest extends TestCase
{
    use RefreshDatabase;

$testMethods

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
    }}
