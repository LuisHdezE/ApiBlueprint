from pathlib import Path

path = Path('app/Infrastructure/Blueprint/LaravelZipBlueprintExporter.php')
source = path.read_text()

old = """        $sortingTest = $manifest['governance']['sorting']
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
"""
new = """        $sortingTest = $manifest['governance']['sorting']
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

        $testMethods = implode("\\n\\n", array_values(array_filter([
            rtrim($paginationTest),
            rtrim($filteringTest),
            rtrim($sortingTest),
        ], static fn (string $test): bool => $test !== '')));

        return <<<PHP
"""
if source.count(old) != 1:
    raise SystemExit(f'Expected one generated-test assembly anchor, got {source.count(old)}')
source = source.replace(old, new, 1)

old = """    use RefreshDatabase;

$paginationTest
$filteringTest
$sortingTest
    private function seedProducts(): void
"""
new = """    use RefreshDatabase;

$testMethods

    private function seedProducts(): void
"""
if source.count(old) != 1:
    raise SystemExit(f'Expected one generated-test interpolation block, got {source.count(old)}')
source = source.replace(old, new, 1)

path.write_text(source)
