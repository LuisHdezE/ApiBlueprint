from pathlib import Path

path = Path('app/Infrastructure/Blueprint/LaravelZipBlueprintExporter.php')
source = path.read_text()


def replace_between(start: str, end: str, replacement: str) -> None:
    global source
    start_index = source.find(start)
    end_index = source.find(end, start_index)
    if start_index < 0 or end_index < 0:
        raise SystemExit(f'Unable to locate boundaries: {start!r} -> {end!r}')
    source = source[:start_index] + replacement + source[end_index:]


service_block = r'''        if ($this->hasEndpoint($manifest, 'products.show')) {
            $imports[] = 'use App\\Application\\Products\\Contracts\\ProductReadRepository;';
            $imports[] = 'use App\\Infrastructure\\Products\\DatabaseProductReadRepository;';
            $registerLines[] = '        $this->app->bind(ProductReadRepository::class, DatabaseProductReadRepository::class);';
        }

'''
replace_between(
    "        if ($this->hasEndpoint($manifest, 'products.show')) {\n            $imports[] = 'use App",
    "        if ($governance['idempotency']) {\n",
    service_block,
)

contract_method = r'''    private function contractTestFile(array $manifest): string
    {
        if ($manifest['endpoints'] === []) {
            return <<<'PHP'
<?php

namespace Tests\Feature;

use Tests\TestCase;

final class GeneratedEndpointContractTest extends TestCase
{
    public function test_blank_blueprint_contains_no_endpoint_contracts(): void
    {
        $manifest = json_decode(
            (string) file_get_contents(base_path('.apiblueprint.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->assertSame([], $manifest['endpoints'] ?? null);
    }
}
PHP;
        }

        $stubEndpoints = array_values(array_filter(
            $manifest['endpoints'],
            static fn (array $endpoint): bool => $endpoint['id'] !== 'products.show',
        ));

        if ($stubEndpoints === []) {
            $assertions = [];
            foreach ($manifest['endpoints'] as $endpoint) {
                $routeName = 'api.v1.'.$endpoint['id'];
                $assertions[] = "        \$this->assertNotNull(app('router')->getRoutes()->getByName('$routeName'));";
            }
            $assertionText = implode("\n", $assertions);

            return <<<PHP
<?php

namespace Tests\Feature;

use Tests\TestCase;

final class GeneratedEndpointContractTest extends TestCase
{
    public function test_executable_endpoint_routes_are_registered(): void
    {
$assertionText
    }
}
PHP;
        }

        $rows = [];
        foreach ($stubEndpoints as $endpoint) {
            $path = preg_replace('/\{[^}]+\}/', 'test-value', $endpoint['path']) ?? $endpoint['path'];
            $rows[] = "            '{$endpoint['id']}' => ['{$endpoint['method']}', '$path'],";
        }
        $dataset = implode("\n", $rows);

        return <<<PHP
<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class GeneratedEndpointContractTest extends TestCase
{
    public static function endpoints(): array
    {
        return [
$dataset
        ];
    }

    #[DataProvider('endpoints')]
    public function test_stub_endpoint_is_registered(string \$method, string \$path): void
    {
        \$this->withoutMiddleware();
        \$this->call(\$method, \$path)
            ->assertStatus(501)
            ->assertJsonPath('title', 'Endpoint generado pendiente de implementación');
    }
}
PHP;
    }

'''
replace_between(
    '    private function contractTestFile(array $manifest): string\n',
    '    private function governanceContractTestFile(array $manifest): string\n',
    contract_method,
)

readme_method = r'''    private function readme(array $manifest): string
    {
        $rows = [];
        foreach ($manifest['endpoints'] as $endpoint) {
            $status = $endpoint['id'] === 'products.show' ? 'Ejecutable' : 'Stub 501';
            $rows[] = "| {$endpoint['method']} | `{$endpoint['path']}` | {$endpoint['summary']} | {$endpoint['exposure']} | $status |";
        }
        $table = $rows === [] ? '_No se seleccionaron endpoints._' : implode("\n", $rows);
        $governance = $manifest['governance'];
        $migrationStep = $this->hasEndpoint($manifest, 'products.show') ? "php artisan migrate\n" : '';

        return "# {$manifest['project']['name']}\n\nSolución Laravel generada por **ApiBlueprint**. El código se mantiene en inglés; mensajes, errores y OpenAPI se presentan en español.\n\n## Gobierno exportado\n\n- Autenticación: `{$governance['authentication']}`\n- RBAC: ".($governance['rbac'] ? 'sí' : 'no')."\n- Correlation ID: ".($governance['correlation_id'] ? 'sí' : 'no')."\n- Rate limit: ".($governance['rate_limiting']['enabled'] ? $governance['rate_limiting']['requests_per_minute'].' solicitudes/minuto' : 'deshabilitado')."\n- Paginación: `{$governance['pagination']['strategy']}`\n- Idempotencia: ".($governance['idempotency'] ? 'sí' : 'no')."\n- Auditoría: ".($governance['audit'] ? 'sí' : 'no')."\n\n## Endpoints exportados\n\n| Método | Ruta | Descripción | Exposición | Implementación |\n| --- | --- | --- | --- | --- |\n$table\n\n## Inicio rápido\n\n```bash\ncomposer install\ncp .env.example .env\nphp artisan key:generate\n{$migrationStep}php artisan test\nphp artisan serve\n```\n\n`products.show` se exporta como vertical slice ejecutable cuando está seleccionado. Los demás endpoints conservan HTTP 501 hasta que su receta ejecutable sea incorporada. Los endpoints y capacidades no seleccionados no se incluyen como infraestructura dormida.\n";
    }

'''
replace_between(
    '    private function readme(array $manifest): string\n',
    '    private function controllerClassName(string $endpointId): string\n',
    readme_method,
)

product_methods = r'''    private function productsShowControllerFile(string $className): string
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

    private function productEntityFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Domain\Products;

final readonly class Product
{
    public function __construct(
        public string $id,
        public string $name,
    ) {
        //
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
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

    private function productsMigrationFile(): string
    {
        return <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
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

    private function hasEndpoint(array $manifest, string $endpointId): bool
    {
        foreach ($manifest['endpoints'] as $endpoint) {
            if ($endpoint['id'] === $endpointId) {
                return true;
            }
        }

        return false;
    }

'''
replace_between(
    '    private function productsShowControllerFile(string $className): string\n',
    '    private function normalizeFile(string $path, string $content): string\n',
    product_methods,
)

path.write_text(source)
