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
        $slug = $this->slug((string) $manifest['project']['name']);
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
            'bootstrap/app.php' => $this->bootstrapFile($manifest),
            'bootstrap/providers.php' => "<?php\n\nreturn [\n    App\\Providers\\AppServiceProvider::class,\n];\n",
            'public/index.php' => $this->publicIndexFile(),
            'routes/api.php' => $this->routesFile($manifest),
            'routes/console.php' => "<?php\n",
            'app/Presentation/Http/Support/ProblemDetails.php' => $this->problemDetailsFile(),
            'app/Providers/AppServiceProvider.php' => $this->serviceProviderFile($manifest),
            'tests/TestCase.php' => $this->testCaseFile(),
            'tests/Feature/GeneratedEndpointContractTest.php' => $this->contractTestFile($manifest),
            'tests/Feature/GeneratedGovernanceContractTest.php' => $this->governanceContractTestFile($manifest),
            'phpunit.xml' => $this->phpUnitFile(),
            'openapi/openapi.yaml' => $this->openApiFile($manifest),
            'app/Domain/README.md' => "# Domain\n\nModelo de dominio puro, sin dependencias de Laravel.\n",
            'app/Application/README.md' => "# Application\n\nCasos de uso, puertos y contratos independientes del framework.\n",
        ];

        if ($manifest['governance']['correlation_id']) {
            $files['app/Presentation/Http/Middleware/CorrelationIdMiddleware.php'] = $this->correlationMiddlewareFile();
        }

        if ($manifest['governance']['idempotency']) {
            $files['app/Application/Shared/Contracts/IdempotencyStore.php'] = $this->idempotencyStoreContractFile();
            $files['app/Infrastructure/Idempotency/CacheIdempotencyStore.php'] = $this->cacheIdempotencyStoreFile();
            $files['app/Presentation/Http/Middleware/IdempotencyMiddleware.php'] = $this->idempotencyMiddlewareFile();
        }

        if ($manifest['governance']['audit']) {
            $files['app/Application/Shared/Contracts/AuditTrail.php'] = $this->auditTrailContractFile();
            $files['app/Infrastructure/Audit/LogAuditTrail.php'] = $this->logAuditTrailFile();
            $files['app/Presentation/Http/Middleware/AuditRequestMiddleware.php'] = $this->auditMiddlewareFile();
        }

        if ($this->hasListEndpoint($manifest)) {
            $files['app/Application/Shared/Query/QueryOptions.php'] = $this->queryOptionsFile();
            $files['app/Presentation/Http/Support/QueryOptionsParser.php'] = $this->queryOptionsParserFile($manifest);
        }

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
            'CACHE_STORE=file',
            'SESSION_DRIVER=file',
            'QUEUE_CONNECTION=sync',
            '',
        ]);
    }

    private function composerFile(array $manifest): string
    {
        $require = [
            'php' => '^8.3',
            'laravel/framework' => '^13.17',
        ];

        if ($manifest['governance']['authentication'] === 'sanctum') {
            $require['laravel/sanctum'] = '^4.3';
        }

        return json_encode([
            '$schema' => 'https://getcomposer.org/schema.json',
            'name' => 'generated/'.$this->slug((string) $manifest['project']['name']),
            'type' => 'project',
            'description' => 'API Laravel generada por ApiBlueprint.',
            'require' => $require,
            'require-dev' => [
                'laravel/pint' => '^1.27',
                'phpunit/phpunit' => '^12.5',
            ],
            'autoload' => ['psr-4' => ['App\\' => 'app/']],
            'autoload-dev' => ['psr-4' => ['Tests\\' => 'tests/']],
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

    private function bootstrapFile(array $manifest): string
    {
        $imports = [
            'use App\\Presentation\\Http\\Support\\ProblemDetails;',
            'use Illuminate\\Auth\\Access\\AuthorizationException;',
            'use Illuminate\\Auth\\AuthenticationException;',
            'use Illuminate\\Foundation\\Application;',
            'use Illuminate\\Foundation\\Configuration\\Exceptions;',
            'use Illuminate\\Foundation\\Configuration\\Middleware;',
            'use Illuminate\\Http\\Request;',
            'use Illuminate\\Validation\\ValidationException;',
            'use Symfony\\Component\\HttpKernel\\Exception\\HttpExceptionInterface;',
        ];
        $middlewareLines = [];

        if ($manifest['governance']['correlation_id']) {
            $imports[] = 'use App\\Presentation\\Http\\Middleware\\CorrelationIdMiddleware;';
            $middlewareLines[] = '        $middleware->append(CorrelationIdMiddleware::class);';
        }
        if ($manifest['governance']['idempotency']) {
            $imports[] = 'use App\\Presentation\\Http\\Middleware\\IdempotencyMiddleware;';
            $middlewareLines[] = "        \$middleware->alias(['idempotency' => IdempotencyMiddleware::class]);";
        }
        if ($manifest['governance']['audit']) {
            $imports[] = 'use App\\Presentation\\Http\\Middleware\\AuditRequestMiddleware;';
            $middlewareLines[] = "        \$middleware->alias(['audit.request' => AuditRequestMiddleware::class]);";
        }

        sort($imports);
        $importsText = implode("\n", $imports);
        $middlewareText = $middlewareLines === [] ? '        //' : implode("\n", $middlewareLines);

        return <<<PHP
<?php

$importsText

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware \$middleware): void {
$middlewareText
    })
    ->withExceptions(function (Exceptions \$exceptions): void {
        \$exceptions->shouldRenderJsonWhen(
            static fn (Request \$request, \\Throwable \$exception): bool => \$request->is('api/*') || \$request->expectsJson(),
        );
        \$exceptions->render(function (\\Throwable \$exception, Request \$request) {
            if (\$request->is('api/*') === false) {
                return null;
            }

            \$status = match (true) {
                \$exception instanceof AuthenticationException => 401,
                \$exception instanceof AuthorizationException => 403,
                \$exception instanceof ValidationException => 422,
                \$exception instanceof HttpExceptionInterface => \$exception->getStatusCode(),
                default => 500,
            };
            \$title = match (\$status) {
                401 => 'Autenticación requerida',
                403 => 'Acceso denegado',
                404 => 'Recurso no encontrado',
                405 => 'Método no permitido',
                422 => 'Error de validación',
                429 => 'Demasiadas solicitudes',
                default => \$status >= 500 ? 'Error interno del servidor' : 'Solicitud no válida',
            };
            \$extensions = \$exception instanceof ValidationException ? ['errors' => \$exception->errors()] : [];

            return ProblemDetails::response(
                request: \$request,
                status: \$status,
                title: \$title,
                detail: \$status >= 500 ? 'Ocurrió un error inesperado al procesar la solicitud.' : 'La solicitud no pudo procesarse.',
                type: "https://eliasworks.uy/problems/http-\$status",
                extensions: \$extensions,
            );
        });
    })->create();
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
            $middleware = $this->routeMiddleware($endpoint, $manifest['governance']);
            $route = "Route::$method('$path', $className::class)->name('api.v1.{$endpoint['id']}')";

            if ($middleware !== []) {
                $serialized = implode(', ', array_map(static fn (string $value): string => "'$value'", $middleware));
                $route .= "->middleware([$serialized])";
            }

            $routes[] = $route.';';
        }

        sort($imports);

        return "<?php\n\n".implode("\n", $imports)."\nuse Illuminate\\Support\\Facades\\Route;\n\n".implode("\n", $routes)."\n";
    }

    private function routeMiddleware(array $endpoint, array $governance): array
    {
        $middleware = [];

        if ($governance['rate_limiting']['enabled']) {
            $middleware[] = 'throttle:api';
        }
        if ($endpoint['exposure'] !== 'public' && $governance['authentication'] === 'sanctum') {
            $middleware[] = 'auth:sanctum';
        }
        if ($governance['rbac'] && $endpoint['exposure'] === 'admin') {
            $middleware[] = 'can:admin-api';
        }
        if ($governance['rbac'] && $endpoint['exposure'] === 'internal') {
            $middleware[] = 'can:internal-api';
        }
        if ($governance['idempotency'] && in_array($endpoint['method'], ['POST', 'PUT', 'PATCH'], true) && $endpoint['id'] !== 'auth.login') {
            $middleware[] = 'idempotency';
        }
        if ($governance['audit']) {
            $middleware[] = 'audit.request';
        }

        return $middleware;
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

    private function problemDetailsFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Presentation\Http\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProblemDetails
{
    public static function response(Request $request, int $status, string $title, string $detail, string $type = 'about:blank', array $extensions = []): JsonResponse
    {
        $payload = [
            'type' => $type,
            'title' => $title,
            'status' => $status,
            'detail' => $detail,
            'instance' => '/'.$request->path(),
        ];
        $correlationId = $request->attributes->get('correlation_id');
        if (is_string($correlationId) && $correlationId !== '') {
            $payload['correlation_id'] = $correlationId;
        }

        return response()->json(array_merge($payload, $extensions), $status, ['Content-Type' => 'application/problem+json']);
    }
}
PHP;
    }

    private function correlationMiddlewareFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class CorrelationIdMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $incoming = trim((string) $request->header('X-Correlation-ID', ''));
        $correlationId = preg_match('/^[A-Za-z0-9._:-]{1,100}$/', $incoming) === 1 ? $incoming : (string) Str::uuid();
        $request->attributes->set('correlation_id', $correlationId);
        $response = $next($request);
        $response->headers->set('X-Correlation-ID', $correlationId);

        return $response;
    }
}
PHP;
    }

    private function idempotencyStoreContractFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Application\Shared\Contracts;

interface IdempotencyStore
{
    public function get(string $key): ?array;

    public function put(string $key, array $response, int $ttlSeconds): void;
}
PHP;
    }

    private function cacheIdempotencyStoreFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Infrastructure\Idempotency;

use App\Application\Shared\Contracts\IdempotencyStore;
use Illuminate\Support\Facades\Cache;

final class CacheIdempotencyStore implements IdempotencyStore
{
    public function get(string $key): ?array
    {
        $value = Cache::get($key);

        return is_array($value) ? $value : null;
    }

    public function put(string $key, array $response, int $ttlSeconds): void
    {
        Cache::put($key, $response, $ttlSeconds);
    }
}
PHP;
    }

    private function idempotencyMiddlewareFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Presentation\Http\Middleware;

use App\Application\Shared\Contracts\IdempotencyStore;
use App\Presentation\Http\Support\ProblemDetails;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class IdempotencyMiddleware
{
    public function __construct(private IdempotencyStore $store)
    {
        //
    }

    public function handle(Request $request, Closure $next): Response
    {
        $idempotencyKey = trim((string) $request->header('Idempotency-Key', ''));
        if ($idempotencyKey === '') {
            return ProblemDetails::response($request, 400, 'Clave de idempotencia requerida', 'La cabecera Idempotency-Key es obligatoria para esta operación.');
        }

        $key = 'idempotency:'.hash('sha256', $request->method().'|'.$request->path().'|'.$idempotencyKey);
        $cached = $this->store->get($key);
        if ($cached !== null) {
            $response = response($cached['body'], $cached['status'], $cached['headers']);
            $response->headers->set('X-Idempotent-Replay', 'true');

            return $response;
        }

        $response = $next($request);
        $this->store->put($key, [
            'status' => $response->getStatusCode(),
            'body' => $response->getContent(),
            'headers' => ['Content-Type' => (string) $response->headers->get('Content-Type', 'application/json')],
        ], 86400);

        return $response;
    }
}
PHP;
    }

    private function auditTrailContractFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Application\Shared\Contracts;

interface AuditTrail
{
    public function record(array $event): void;
}
PHP;
    }

    private function logAuditTrailFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Infrastructure\Audit;

use App\Application\Shared\Contracts\AuditTrail;
use Psr\Log\LoggerInterface;

final readonly class LogAuditTrail implements AuditTrail
{
    public function __construct(private LoggerInterface $logger)
    {
        //
    }

    public function record(array $event): void
    {
        $this->logger->info('api_audit', $event);
    }
}
PHP;
    }

    private function auditMiddlewareFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Presentation\Http\Middleware;

use App\Application\Shared\Contracts\AuditTrail;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class AuditRequestMiddleware
{
    public function __construct(private AuditTrail $auditTrail)
    {
        //
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $this->auditTrail->record([
            'route' => $request->route()?->getName(),
            'method' => $request->method(),
            'path' => '/'.$request->path(),
            'status' => $response->getStatusCode(),
            'correlation_id' => $request->attributes->get('correlation_id'),
            'user_id' => $request->user()?->getAuthIdentifier(),
        ]);

        return $response;
    }
}
PHP;
    }

    private function queryOptionsFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Application\Shared\Query;

final readonly class QueryOptions
{
    public function __construct(
        public string $paginationStrategy,
        public int $pageSize,
        public ?string $cursor,
        public int $pageNumber,
        public array $filters,
        public ?string $sort,
    ) {
        //
    }
}
PHP;
    }

    private function queryOptionsParserFile(array $manifest): string
    {
        $pagination = $manifest['governance']['pagination'];
        $strategy = var_export($pagination['strategy'], true);
        $defaultSize = $pagination['default_size'];
        $maxSize = $pagination['max_size'];
        $filtering = $manifest['governance']['filtering'] ? 'true' : 'false';
        $sorting = $manifest['governance']['sorting'] ? 'true' : 'false';

        return <<<PHP
<?php

namespace App\Presentation\Http\Support;

use App\Application\Shared\Query\QueryOptions;
use Illuminate\Http\Request;

final class QueryOptionsParser
{
    public function parse(Request \$request): QueryOptions
    {
        \$page = is_array(\$request->query('page')) ? \$request->query('page') : [];
        \$pageSize = min(max((int) (\$page['size'] ?? $defaultSize), 1), $maxSize);
        \$pageNumber = max((int) (\$page['number'] ?? 1), 1);
        \$cursor = isset(\$page['cursor']) ? (string) \$page['cursor'] : null;
        \$filters = $filtering && is_array(\$request->query('filter')) ? \$request->query('filter') : [];
        \$sort = $sorting && is_string(\$request->query('sort')) ? \$request->query('sort') : null;

        return new QueryOptions($strategy, \$pageSize, \$cursor, \$pageNumber, \$filters, \$sort);
    }
}
PHP;
    }

    private function serviceProviderFile(array $manifest): string
    {
        $imports = ['use Illuminate\\Support\\ServiceProvider;'];
        $registerLines = [];
        $bootLines = [];
        $governance = $manifest['governance'];

        if ($governance['idempotency']) {
            $imports[] = 'use App\\Application\\Shared\\Contracts\\IdempotencyStore;';
            $imports[] = 'use App\\Infrastructure\\Idempotency\\CacheIdempotencyStore;';
            $registerLines[] = '        $this->app->bind(IdempotencyStore::class, CacheIdempotencyStore::class);';
        }
        if ($governance['audit']) {
            $imports[] = 'use App\\Application\\Shared\\Contracts\\AuditTrail;';
            $imports[] = 'use App\\Infrastructure\\Audit\\LogAuditTrail;';
            $registerLines[] = '        $this->app->bind(AuditTrail::class, LogAuditTrail::class);';
        }
        if ($governance['rate_limiting']['enabled']) {
            $imports[] = 'use Illuminate\\Cache\\RateLimiting\\Limit;';
            $imports[] = 'use Illuminate\\Http\\Request;';
            $imports[] = 'use Illuminate\\Support\\Facades\\RateLimiter;';
            $requestsPerMinute = $governance['rate_limiting']['requests_per_minute'];
            $bootLines[] = "        RateLimiter::for('api', static fn (Request \$request): Limit => Limit::perMinute($requestsPerMinute)->by((string) (\$request->user()?->getAuthIdentifier() ?? \$request->ip())));";
        }
        if ($governance['rbac']) {
            $imports[] = 'use Illuminate\\Support\\Facades\\Gate;';
            $bootLines[] = "        Gate::define('admin-api', static fn (object \$user): bool => method_exists(\$user, 'hasRole') ? (bool) \$user->hasRole('admin') : ((\$user->role ?? null) === 'admin'));";
            $bootLines[] = "        Gate::define('internal-api', static fn (object \$user): bool => method_exists(\$user, 'hasRole') ? (bool) (\$user->hasRole('internal') || \$user->hasRole('admin')) : in_array(\$user->role ?? null, ['internal', 'admin'], true));";
        }

        sort($imports);
        $importsText = implode("\n", $imports);
        $registerText = $registerLines === [] ? '        //' : implode("\n", $registerLines);
        $bootText = $bootLines === [] ? '        //' : implode("\n", $bootLines);

        return <<<PHP
<?php

namespace App\Providers;

$importsText

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
$registerText
    }

    public function boot(): void
    {
$bootText
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

abstract class TestCase extends BaseTestCase
{
    //
}
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
        \$this->withoutMiddleware();
        \$this->call(\$method, \$path)
            ->assertStatus(501)
            ->assertJsonPath('title', 'Endpoint generado pendiente de implementación');
    }
}
PHP;
    }

    private function governanceContractTestFile(array $manifest): string
    {
        $correlationAssertion = $manifest['governance']['correlation_id']
            ? "            ->assertHeader('X-Correlation-ID')"
            : '';

        return <<<PHP
<?php

namespace Tests\Feature;

use Tests\TestCase;

final class GeneratedGovernanceContractTest extends TestCase
{
    public function test_unknown_route_uses_problem_details(): void
    {
        \$this->getJson('/api/v1/__missing__')
            ->assertStatus(404)
            ->assertHeader('content-type', 'application/problem+json')
$correlationAssertion
            ->assertJsonPath('title', 'Recurso no encontrado');
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
        <testsuite name="Feature"><directory>tests/Feature</directory></testsuite>
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
        $governance = $manifest['governance'];
        $lines = [
            'openapi: 3.1.0',
            'info:',
            '  title: '.$this->yamlString($manifest['project']['name'].' API'),
            '  version: "1.0.0"',
            '  description: "Contrato OpenAPI generado por ApiBlueprint. Los textos visibles se presentan en español."',
            'paths:',
        ];

        foreach ($manifest['endpoints'] as $endpoint) {
            $method = strtolower($endpoint['method']);
            $lines[] = '  '.$endpoint['path'].':';
            $lines[] = "    $method:";
            $lines[] = '      operationId: '.str_replace('.', '_', $endpoint['id']);
            $lines[] = '      summary: '.$this->yamlString($endpoint['summary']);
            $lines[] = '      tags: ['.$this->yamlString($endpoint['capability_label']).']';
            $lines[] = '      x-exposure: '.$endpoint['exposure'];

            if ($endpoint['exposure'] !== 'public' && $governance['authentication'] === 'sanctum') {
                $lines[] = '      security:';
                $lines[] = '        - bearerAuth: []';
            }

            if (str_ends_with($endpoint['id'], '.list')) {
                $lines[] = '      parameters:';
                $lines[] = '        - { name: "page[size]", in: query, schema: { type: integer, default: '.$governance['pagination']['default_size'].', maximum: '.$governance['pagination']['max_size'].' } }';
                $pageKey = $governance['pagination']['strategy'] === 'cursor' ? 'page[cursor]' : 'page[number]';
                $lines[] = '        - { name: '.$this->yamlString($pageKey).', in: query, schema: { type: string } }';
                if ($governance['filtering']) {
                    $lines[] = '        - { name: "filter[field]", in: query, schema: { type: string }, description: "Filtro por campo permitido." }';
                }
                if ($governance['sorting']) {
                    $lines[] = '        - { name: sort, in: query, schema: { type: string }, description: "Campos de orden separados por coma; prefijo - para descendente." }';
                }
            }

            $lines[] = '      responses:';
            $lines[] = "        '501':";
            $lines[] = '          description: "Endpoint generado pendiente de implementación."';
            $lines[] = "        '429':";
            $lines[] = '          description: "Se superó el límite de solicitudes permitido."';
            $lines[] = '          content: { application/problem+json: { schema: { $ref: "#/components/schemas/ProblemDetails" } } }';
        }

        $lines[] = 'components:';
        $lines[] = '  schemas:';
        $lines[] = '    ProblemDetails:';
        $lines[] = '      type: object';
        $lines[] = '      required: [type, title, status, detail]';
        $lines[] = '      properties:';
        $lines[] = '        type: { type: string }';
        $lines[] = '        title: { type: string }';
        $lines[] = '        status: { type: integer }';
        $lines[] = '        detail: { type: string }';
        $lines[] = '        correlation_id: { type: string }';

        if ($governance['authentication'] === 'sanctum') {
            $lines[] = '  securitySchemes:';
            $lines[] = '    bearerAuth:';
            $lines[] = '      type: http';
            $lines[] = '      scheme: bearer';
            $lines[] = '      bearerFormat: token';
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
        $governance = $manifest['governance'];

        return "# {$manifest['project']['name']}\n\nSolución Laravel generada por **ApiBlueprint**. El código se mantiene en inglés; mensajes, errores y OpenAPI se presentan en español.\n\n## Gobierno exportado\n\n- Autenticación: `{$governance['authentication']}`\n- RBAC: ".($governance['rbac'] ? 'sí' : 'no')."\n- Correlation ID: ".($governance['correlation_id'] ? 'sí' : 'no')."\n- Rate limit: ".($governance['rate_limiting']['enabled'] ? $governance['rate_limiting']['requests_per_minute'].' solicitudes/minuto' : 'deshabilitado')."\n- Paginación: `{$governance['pagination']['strategy']}`\n- Idempotencia: ".($governance['idempotency'] ? 'sí' : 'no')."\n- Auditoría: ".($governance['audit'] ? 'sí' : 'no')."\n\n## Endpoints exportados\n\n| Método | Ruta | Descripción | Exposición |\n| --- | --- | --- | --- |\n$table\n\n## Inicio rápido\n\n```bash\ncomposer install\ncp .env.example .env\nphp artisan key:generate\nphp artisan test\nphp artisan serve\n```\n\nLos casos de uso generados responden inicialmente con HTTP 501. Los endpoints y capacidades no seleccionados no se incluyen como infraestructura dormida.\n";
    }

    private function controllerClassName(string $endpointId): string
    {
        $parts = preg_split('/[^A-Za-z0-9]+/', $endpointId) ?: [];

        return implode('', array_map(static fn (string $part): string => ucfirst($part), $parts)).'Controller';
    }

    private function hasListEndpoint(array $manifest): bool
    {
        foreach ($manifest['endpoints'] as $endpoint) {
            if (str_ends_with($endpoint['id'], '.list')) {
                return true;
            }
        }

        return false;
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
