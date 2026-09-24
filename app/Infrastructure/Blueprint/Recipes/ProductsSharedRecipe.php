<?php

namespace App\Infrastructure\Blueprint\Recipes;

final class ProductsSharedRecipe extends AbstractLaravelFeatureRecipe
{
    public function id(): string
    {
        return 'support.products';
    }

    public function endpointIds(): array
    {
        return [
            'products.show',
            'products.list',
        ];
    }

    public function files(array $manifest): array
    {
        return [
            'database/database.sqlite' => '',
            'database/migrations/2026_01_01_000000_create_products_table.php' => $this->productsMigrationFile(),
            'app/Domain/Products/Product.php' => $this->productEntityFile(),
        ];
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
}
