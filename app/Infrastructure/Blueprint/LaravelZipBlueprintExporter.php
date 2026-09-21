<?php

namespace App\Infrastructure\Blueprint;

use App\Application\Blueprint\Contracts\BlueprintExporter;
use App\Application\Blueprint\Data\ExportedBlueprint;
use RuntimeException;
use ZipArchive;

final class LaravelZipBlueprintExporter implements BlueprintExporter
{
    public function export(array $manifest): ExportedBlueprint
    {
        $projectName = (string) $manifest['project']['name'];
        $slug = $this->slug($projectName);
        $temporaryFile = tempnam(sys_get_temp_dir(), 'apiblueprint-');

        if ($temporaryFile === false) {
            throw new RuntimeException('Unable to create temporary export file.');
        }

        $zip = new ZipArchive;
        if ($zip->open($temporaryFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($temporaryFile);
            throw new RuntimeException('Unable to open export archive.');
        }

        foreach ($this->buildFiles($manifest) as $path => $content) {
            $zip->addFromString("$slug/$path", $content);
        }

        $zip->close();
        $content = file_get_contents($temporaryFile);
        @unlink($temporaryFile);

        if ($content === false) {
            throw new RuntimeException('Unable to read generated export archive.');
        }

        return new ExportedBlueprint("$slug.zip", $content);
    }

    private function buildFiles(array $manifest): array
    {
        $files = [
            '.apiblueprint.json' => json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL,
            '.env.example' => $this->environmentFile($manifest),
            'README.md' => $this->readme($manifest),
            'composer.json' => $this->composerFile($manifest),
            'artisan' => $this->artisanFile(),
            'bootstrap/app.php' => $this->bootstrapFile(),
            'bootstrap/providers.php' => "<?php\n\nreturn [];\n",
            'public/index.php' => $this->publicIndexFile(),
            'routes/api.php' => $this->routesFile($manifest),
            'routes/console.php' => "<?php\n",
            'tests/TestCase.php' => $this->testCaseFile(),
            'tests/Feature/GeneratedEndpointContractTest.php' => $this->contractTestFile($manifest),
            'phpunit.xml' => $this->phpUnitFile(),
            'openapi/openapi.yaml' => $this->openApiFile($manifest),
            'app/Domain/README.md' => "# Domain\n\nEste directorio debe contener el modelo de dominio puro, sin dependencias de Laravel.\n",
            'app/Application/README.md' => "# Application\n\nEste directorio debe contener casos de uso, puertos y contratos independientes del framework.\n",
        ];

        foreach ($manifest['endpoints'] as $endpoint) {
            $className = $this->controllerClassName($endpoint['id']);
            $files["app/Presentation/Http/Controllers/Generated/$className.php"] = $this->controllerFile($endpoint, $className);
        }

        return $files;
    }

    private function environmentFile(array $manifest): string
    {
        return implode("\n", [
            'APP_NAME="'.$manifest['project']['name'].'"',
            'APP_ENV=local',
            'APP_KEY=',
            'APP_DEBUG=true',
            'APP_URL=http://localhost',
            'APP_LOCALE=es',
            'APP_FALLBACK_LOCALE=es',
            '',
            'LOG_CHANNEL=stack',
            'CACHE_STORE=array',
            'SESSION_DRIVER=array',
            'QUEUE_CONNECTION=sync',
            '',
        ]);
    }

    private function composerFile(array $manifest): string
    {
        $name = 'generated/'.$this->slug((string) $manifest['project']['name']);

        return json_encode([
            '$schema' => 'https://getcomposer.org/schema.json',
            'name' => $name,
            'type' => 'project',
            'description' => 'API Laravel generada por ApiBlueprint.',
            'require' => [
                'php' => '^8.3',
                'laravel/framework' => '^13.17',
            ],
            'require-dev' => [
                'laravel/pint' => '^1.27',
                'phpunit/phpunit' => '^12.5',
            ],
            'autoload' => [
                'psr-4' => ['App\\' => 'app/'],
            ],
            'autoload-dev' => [
                'psr-4' => ['Tests\\' => 'tests/'],
            ],
            'scripts' => [
                'post-autoload-dump' => [
                    'Illuminate\\Foundation\\ComposerScripts::postAutoloadDump',
                    '@php artisan package:discover --ansi',
                ],
            ],
            'minimum-stability' => 'stable',
            'prefer-stable' => true,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;
    }

    private function artisanFile(): string
    {
        return <<<'PHP'
#!/usr/bin/env php
<?php

define('LARAVEL_START', microtime(true));

require __DIR__.'/vendor/autoload.php';

$status = (require_once __DIR__.'/bootstrap/app.php')
    ->handleCommand(new Symfony\Component\Console\Input\ArgvInput);

exit($status);
PHP;
    }

    private function bootstrapFile(): string
    {
        return <<<'PHP'
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {})
    ->withExceptions(function (Exceptions $exceptions): void {})
    ->create();
PHP;
    }

    private function publicIndexFile(): string
    {
        return <<<'PHP'
<?php

define('LARAVEL_START', microtime(true));

require __DIR__.'/../vendor/autoload.php';

(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Illuminate\Http\Request::capture());
PHP;
    }

    private function routesFile(array $manifest): string
    {
        $imports = [];
        $routes = [];

        foreach ($manifest['endpoints'] as $endpoint) {
            $className = $this->controllerClassName($endpoint['id']);
            $imports[] = "use App\\Presentation\\Http\\Controllers\\Generated\\$className;";
            $method = strtolower($endpoint['method']);
            $path = preg_replace('#^/api#', '', $endpoint['path']) ?? $endpoint['path'];
            $routes[] = "Route::$method('$path', $className::class)->name('api.v1.{$endpoint['id']}');";
        }

        sort($imports);

        return "<?php\n\n".implode("\n", $imports)."\nuse Illuminate\\Support\\Facades\\Route;\n\n".implode("\n", $routes)."\n";
    }

    private function controllerFile(array $endpoint, string $className): string
    {
        $endpointId = var_export($endpoint['id'], true);
        $summary = var_export($endpoint['summary'], true);

        return <<<PHP
<?php

namespace App\Presentation\Http\Controllers\Generated;

use Illuminate\Http\JsonResponse;

final class $className
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'type' => 'about:blank',
            'title' => 'Endpoint generado pendiente de implementación',
            'status' => 501,
            'detail' => $summary.' forma parte del contrato exportado y conserva su stub hasta implementar el caso de uso.',
            'endpoint' => $endpointId,
        ], 501, ['Content-Type' => 'application/problem+json']);
    }
}
PHP;
    }

    private function testCaseFile(): string
    {
        return <<<'PHP'
<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase {}
PHP;
    }

    private function contractTestFile(array $manifest): string
    {
        $rows = [];
        foreach ($manifest['endpoints'] as $endpoint) {
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
    public function test_exported_endpoint_is_registered(string \$method, string \$path): void
    {
        \$this->call(\$method, \$path)
            ->assertStatus(501)
            ->assertJsonPath('title', 'Endpoint generado pendiente de implementación');
    }
}
PHP;
    }

    private function phpUnitFile(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="vendor/autoload.php" colors="true">
    <testsuites>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="CACHE_STORE" value="array"/>
        <env name="SESSION_DRIVER" value="array"/>
    </php>
</phpunit>
XML;
    }

    private function openApiFile(array $manifest): string
    {
        $paths = [];
        foreach ($manifest['endpoints'] as $endpoint) {
            $paths[$endpoint['path']][strtolower($endpoint['method'])] = $endpoint;
        }

        $lines = [
            'openapi: 3.1.0',
            'info:',
            '  title: '.$this->yamlString($manifest['project']['name'].' API'),
            '  version: "1.0.0"',
            '  description: "Contrato OpenAPI generado por ApiBlueprint. Los textos visibles se presentan en español."',
            'paths:',
        ];

        foreach ($paths as $path => $methods) {
            $lines[] = '  '.$path.':';
            foreach ($methods as $method => $endpoint) {
                $lines[] = "    $method:";
                $lines[] = '      operationId: '.str_replace('.', '_', $endpoint['id']);
                $lines[] = '      summary: '.$this->yamlString($endpoint['summary']);
                $lines[] = '      tags: ['.$this->yamlString($endpoint['capability_label']).']';
                $lines[] = '      x-exposure: '.$endpoint['exposure'];
                $lines[] = '      responses:';
                $lines[] = "        '501':";
                $lines[] = '          description: "Endpoint generado pendiente de implementación."';
            }
        }

        return implode("\n", $lines)."\n";
    }

    private function readme(array $manifest): string
    {
        $rows = [];
        foreach ($manifest['endpoints'] as $endpoint) {
            $rows[] = "| {$endpoint['method']} | `{$endpoint['path']}` | {$endpoint['summary']} | {$endpoint['exposure']} |";
        }

        $table = $rows === [] ? '_No se seleccionaron endpoints._' : implode("\n", $rows);

        return "# {$manifest['project']['name']}\n\nSolución Laravel generada por **ApiBlueprint**. El código y los identificadores internos se mantienen en inglés; los mensajes, respuestas y OpenAPI se presentan en español.\n\n## Endpoints exportados\n\n| Método | Ruta | Descripción | Exposición |\n| --- | --- | --- | --- |\n$table\n\n## Inicio rápido\n\n```bash\ncomposer install\ncp .env.example .env\nphp artisan key:generate\nphp artisan test\nphp artisan serve\n```\n\nLos controladores exportados responden inicialmente con HTTP 501 hasta que cada caso de uso sea implementado. Los endpoints no seleccionados no existen en este paquete.\n";
    }

    private function controllerClassName(string $endpointId): string
    {
        $parts = preg_split('/[^A-Za-z0-9]+/', $endpointId) ?: [];

        return implode('', array_map(static fn (string $part): string => ucfirst($part), $parts)).'Controller';
    }

    private function slug(string $value): string
    {
        $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($value)) ?? 'api';
        $slug = trim($slug, '-');

        return $slug === '' ? 'api' : $slug;
    }

    private function yamlString(string $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
