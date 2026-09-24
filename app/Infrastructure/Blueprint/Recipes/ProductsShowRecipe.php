<?php

namespace App\Infrastructure\Blueprint\Recipes;

final class ProductsShowRecipe extends AbstractLaravelFeatureRecipe
{
    public function id(): string
    {
        return 'products.show';
    }

    public function endpointIds(): array
    {
        return [
            'products.show',
        ];
    }

    public function files(array $manifest): array
    {
        return [
            'app/Application/Products/Contracts/ProductReadRepository.php' => $this->productReadRepositoryContractFile(),
            'app/Application/Products/UseCases/GetProduct.php' => $this->getProductUseCaseFile(),
            'app/Infrastructure/Products/DatabaseProductReadRepository.php' => $this->databaseProductReadRepositoryFile(),
            'tests/Feature/ProductsShowVerticalSliceTest.php' => $this->productsShowVerticalSliceTestFile(),
        ];
    }

    public function controller(array $endpoint, string $className): ?string
    {
        if (($endpoint['id'] ?? null) !== 'products.show') {
            return null;
        }

        return $this->productsShowControllerFile($className);
    }

    public function serviceProviderImports(): array
    {
        return [
            'use App\\Application\\Products\\Contracts\\ProductReadRepository;',
            'use App\\Infrastructure\\Products\\DatabaseProductReadRepository;',
        ];
    }

    public function serviceProviderRegistrations(): array
    {
        return [
            '        $this->app->bind(ProductReadRepository::class, DatabaseProductReadRepository::class);',
        ];
    }

    public function composerRequireDev(): array
    {
        return [
            'mockery/mockery' => '^1.6',
        ];
    }

    private function productsShowControllerFile(string $className): string
    {
        return <<<PHP
<?php

namespace App\Presentation\Http\Controllers\Generated;

use App\Application\Products\UseCases\GetProduct;
use App\Presentation\Http\Support\ProblemDetails;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class $className
{
    public function __construct(private GetProduct \$getProduct)
    {
        //
    }

    public function __invoke(Request \$request, string \$id): JsonResponse
    {
        \$product = \$this->getProduct->handle(\$id);
        if (\$product === null) {
            return ProblemDetails::response(
                request: \$request,
                status: 404,
                title: 'Producto no encontrado',
                detail: 'No existe un producto con el identificador solicitado.',
                type: 'https://eliasworks.uy/problems/product-not-found',
            );
        }

        return response()->json(['data' => \$product->toArray()]);
    }
}
PHP;
    }

    private function productReadRepositoryContractFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Application\Products\Contracts;

use App\Domain\Products\Product;

interface ProductReadRepository
{
    public function find(string $id): ?Product;
}
PHP;
    }

    private function getProductUseCaseFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Application\Products\UseCases;

use App\Application\Products\Contracts\ProductReadRepository;
use App\Domain\Products\Product;

final readonly class GetProduct
{
    public function __construct(private ProductReadRepository $products)
    {
        //
    }

    public function handle(string $id): ?Product
    {
        return $this->products->find($id);
    }
}
PHP;
    }

    private function databaseProductReadRepositoryFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Infrastructure\Products;

use App\Application\Products\Contracts\ProductReadRepository;
use App\Domain\Products\Product;
use Illuminate\Support\Facades\DB;

final class DatabaseProductReadRepository implements ProductReadRepository
{
    public function find(string $id): ?Product
    {
        $row = DB::table('products')->where('id', $id)->first();
        if ($row === null) {
            return null;
        }

        return new Product(
            id: (string) $row->id,
            name: (string) $row->name,
        );
    }
}
PHP;
    }

    private function productsShowVerticalSliceTestFile(): string
    {
        return <<<'PHP'
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ProductsShowVerticalSliceTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_can_be_retrieved_through_the_generated_vertical_slice(): void
    {
        DB::table('products')->insert([
            'id' => 'product-001',
            'name' => 'Producto de prueba',
        ]);

        $this->getJson('/api/v1/products/product-001')
            ->assertOk()
            ->assertJsonPath('data.id', 'product-001')
            ->assertJsonPath('data.name', 'Producto de prueba');
    }

    public function test_missing_product_uses_problem_details_in_spanish(): void
    {
        $this->getJson('/api/v1/products/missing')
            ->assertStatus(404)
            ->assertHeader('content-type', 'application/problem+json')
            ->assertJsonPath('title', 'Producto no encontrado')
            ->assertJsonPath('status', 404);
    }
}
PHP;
    }
}
