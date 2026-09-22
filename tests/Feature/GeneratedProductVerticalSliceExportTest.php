<?php

namespace Tests\Feature;

use Tests\TestCase;
use ZipArchive;

final class GeneratedProductVerticalSliceExportTest extends TestCase
{
    public function test_products_show_exports_an_executable_clean_architecture_slice_without_dormant_list_infrastructure(): void
    {
        $response = $this->postJson('/api/v1/blueprint/export', $this->manifest())
            ->assertOk()
            ->assertHeader('content-type', 'application/zip');

        $temporaryFile = tempnam(sys_get_temp_dir(), 'apiblueprint-product-slice-');
        $this->assertNotFalse($temporaryFile);
        file_put_contents($temporaryFile, $response->getContent());

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($temporaryFile) === true);

        $controller = $zip->getFromName('product-api/app/Presentation/Http/Controllers/Generated/ProductsShowController.php');
        $domain = $zip->getFromName('product-api/app/Domain/Products/Product.php');
        $contract = $zip->getFromName('product-api/app/Application/Products/Contracts/ProductReadRepository.php');
        $useCase = $zip->getFromName('product-api/app/Application/Products/UseCases/GetProduct.php');
        $repository = $zip->getFromName('product-api/app/Infrastructure/Products/DatabaseProductReadRepository.php');
        $migration = $zip->getFromName('product-api/database/migrations/2026_01_01_000000_create_products_table.php');
        $provider = $zip->getFromName('product-api/app/Providers/AppServiceProvider.php');
        $verticalSliceTest = $zip->getFromName('product-api/tests/Feature/ProductsShowVerticalSliceTest.php');
        $composer = $zip->getFromName('product-api/composer.json');
        $openApi = $zip->getFromName('product-api/openapi/openapi.yaml');
        $phpunit = $zip->getFromName('product-api/phpunit.xml');
        $readme = $zip->getFromName('product-api/README.md');

        $this->assertIsString($controller);
        $this->assertStringContainsString('GetProduct', $controller);
        $this->assertStringContainsString('Producto no encontrado', $controller);
        $this->assertStringNotContainsString("'status' => 501", $controller);

        $this->assertIsString($domain);
        $this->assertStringContainsString('final readonly class Product', $domain);
        $this->assertIsString($contract);
        $this->assertStringContainsString('interface ProductReadRepository', $contract);
        $this->assertIsString($useCase);
        $this->assertStringContainsString('final readonly class GetProduct', $useCase);
        $this->assertIsString($repository);
        $this->assertStringContainsString("DB::table('products')", $repository);
        $this->assertIsString($migration);
        $this->assertStringContainsString("Schema::create('products'", $migration);

        $this->assertIsString($provider);
        $this->assertStringContainsString('ProductReadRepository::class, DatabaseProductReadRepository::class', $provider);
        $this->assertIsString($verticalSliceTest);
        $this->assertStringContainsString('RefreshDatabase', $verticalSliceTest);
        $this->assertIsString($composer);
        $composerData = json_decode($composer, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('^1.6', $composerData['require-dev']['mockery/mockery'] ?? null);

        $this->assertIsString($openApi);
        $this->assertStringContainsString("'200':", $openApi);
        $this->assertStringContainsString("'404':", $openApi);
        $this->assertStringContainsString('Product:', $openApi);
        $this->assertStringNotContainsString("'501':", $openApi);

        $this->assertIsString($phpunit);
        $this->assertStringContainsString('DB_CONNECTION', $phpunit);
        $this->assertStringContainsString(':memory:', $phpunit);
        $this->assertIsString($readme);
        $this->assertStringContainsString('Ejecutable', $readme);
        $this->assertStringContainsString('php artisan migrate', $readme);

        $this->assertFalse($zip->locateName('product-api/app/Presentation/Http/Controllers/Generated/ProductsListController.php'));
        $this->assertFalse($zip->locateName('product-api/app/Presentation/Http/Support/QueryOptionsParser.php'));

        $zip->close();
        @unlink($temporaryFile);
    }

    private function manifest(): array
    {
        return [
            'schema_version' => '0.3',
            'generator' => 'ApiBlueprint',
            'project' => [
                'name' => 'Product API',
                'api_version' => 'v1',
            ],
            'template' => 'custom',
            'governance' => [
                'authentication' => 'none',
                'rbac' => true,
                'correlation_id' => true,
                'rate_limiting' => [
                    'enabled' => true,
                    'requests_per_minute' => 60,
                ],
                'pagination' => [
                    'strategy' => 'cursor',
                    'default_size' => 25,
                    'max_size' => 100,
                ],
                'filtering' => true,
                'sorting' => true,
                'idempotency' => true,
                'audit' => true,
            ],
            'endpoints' => [
                ['id' => 'products.show', 'exposure' => 'public'],
            ],
        ];
    }
}
