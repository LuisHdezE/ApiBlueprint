<?php

namespace Tests\Feature;

use Tests\TestCase;
use ZipArchive;

final class GeneratedProductListVerticalSliceExportTest extends TestCase
{
    public function test_cursor_products_list_exports_an_executable_slice_without_show_infrastructure(): void
    {
        $zip = $this->export($this->manifest('cursor'));

        $controller = $this->entry($zip, 'product-list-api/app/Presentation/Http/Controllers/Generated/ProductsListController.php');
        $repository = $this->entry($zip, 'product-list-api/app/Infrastructure/Products/DatabaseProductListRepository.php');
        $validator = $this->entry($zip, 'product-list-api/app/Presentation/Http/Support/ProductListQueryValidator.php');
        $openApi = $this->entry($zip, 'product-list-api/openapi/openapi.yaml');
        $composer = $this->entry($zip, 'product-list-api/composer.json');
        $generatedTest = $this->entry($zip, 'product-list-api/tests/Feature/ProductsListVerticalSliceTest.php');

        $this->assertStringContainsString('ListProducts', $controller);
        $this->assertStringContainsString('QueryOptionsParser', $controller);
        $this->assertStringNotContainsString("'status' => 501", $controller);

        $this->assertStringContainsString('cursorPage', $repository);
        $this->assertStringContainsString('applyCursor', $repository);
        $this->assertStringContainsString('offsetPage', $repository);
        $this->assertStringContainsString("'array:size,cursor'", $validator);

        $this->assertStringContainsString("'200':", $openApi);
        $this->assertStringContainsString("'422':", $openApi);
        $this->assertStringContainsString('ProductListMeta:', $openApi);
        $this->assertStringContainsString('page[cursor]', $openApi);
        $this->assertStringContainsString('filter[id]', $openApi);
        $this->assertStringContainsString('filter[name]', $openApi);

        $this->assertStringContainsString('mockery/mockery', $composer);
        $this->assertStringContainsString('test_products_use_keyset_cursor_pagination', $generatedTest);
        $this->assertStringContainsString('test_invalid_cursor_uses_problem_details', $generatedTest);

        $this->assertNotFalse($zip->locateName('product-list-api/app/Domain/Products/Product.php'));
        $this->assertNotFalse($zip->locateName('product-list-api/app/Application/Products/Contracts/ProductListRepository.php'));
        $this->assertNotFalse($zip->locateName('product-list-api/app/Application/Products/Data/ProductPage.php'));
        $this->assertNotFalse($zip->locateName('product-list-api/app/Application/Products/UseCases/ListProducts.php'));
        $this->assertNotFalse($zip->locateName('product-list-api/database/migrations/2026_01_01_000000_create_products_table.php'));

        $this->assertFalse($zip->locateName('product-list-api/app/Application/Products/Contracts/ProductReadRepository.php'));
        $this->assertFalse($zip->locateName('product-list-api/app/Application/Products/UseCases/GetProduct.php'));
        $this->assertFalse($zip->locateName('product-list-api/app/Infrastructure/Products/DatabaseProductReadRepository.php'));
        $this->assertFalse($zip->locateName('product-list-api/app/Presentation/Http/Controllers/Generated/ProductsShowController.php'));
        $this->assertFalse($zip->locateName('product-list-api/tests/Feature/ProductsShowVerticalSliceTest.php'));

        $zip->close();
    }

    public function test_offset_products_list_exports_offset_contract_and_runtime_test(): void
    {
        $zip = $this->export($this->manifest('offset'));

        $validator = $this->entry($zip, 'product-list-api/app/Presentation/Http/Support/ProductListQueryValidator.php');
        $openApi = $this->entry($zip, 'product-list-api/openapi/openapi.yaml');
        $generatedTest = $this->entry($zip, 'product-list-api/tests/Feature/ProductsListVerticalSliceTest.php');

        $this->assertStringContainsString("'array:size,number'", $validator);
        $this->assertStringContainsString('page[number]', $openApi);
        $this->assertStringContainsString('type: integer', $openApi);
        $this->assertStringNotContainsString('page[cursor]', $openApi);
        $this->assertStringContainsString('test_products_use_offset_pagination_with_totals', $generatedTest);

        $zip->close();
    }

    private function export(array $manifest): ZipArchive
    {
        $response = $this->postJson('/api/v1/blueprint/export', $manifest)
            ->assertOk()
            ->assertHeader('content-type', 'application/zip');

        $temporaryFile = tempnam(sys_get_temp_dir(), 'apiblueprint-products-list-');
        $this->assertNotFalse($temporaryFile);
        file_put_contents($temporaryFile, $response->getContent());

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($temporaryFile) === true);
        @unlink($temporaryFile);

        return $zip;
    }

    private function entry(ZipArchive $zip, string $path): string
    {
        $content = $zip->getFromName($path);
        $this->assertIsString($content, "Missing generated entry: {$path}");

        return $content;
    }

    private function manifest(string $strategy): array
    {
        return [
            'schema_version' => '0.3',
            'generator' => 'ApiBlueprint',
            'project' => [
                'name' => 'Product List API',
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
                    'strategy' => $strategy,
                    'default_size' => 25,
                    'max_size' => 100,
                ],
                'filtering' => true,
                'sorting' => true,
                'idempotency' => true,
                'audit' => true,
            ],
            'endpoints' => [
                ['id' => 'products.list', 'exposure' => 'public'],
            ],
        ];
    }
}
