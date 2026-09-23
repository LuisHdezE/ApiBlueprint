<?php

namespace App\Infrastructure\Blueprint;

use App\Application\Blueprint\Contracts\BlueprintCatalog;
use App\Application\Blueprint\Contracts\BlueprintExporter;
use App\Application\Blueprint\Data\ExportedBlueprint;
use RuntimeException;
use ZipArchive;

final readonly class OperationalContractBlueprintExporter implements BlueprintExporter
{
    public function __construct(
        private BlueprintExporter $inner,
        private BlueprintCatalog $catalog,
    ) {
        //
    }

    public function export(array $manifest): ExportedBlueprint
    {
        $exported = $this->inner->export($manifest);

        if (($manifest['governance']['audit'] ?? false) !== true) {
            return $exported;
        }

        $temporaryFile = tempnam(sys_get_temp_dir(), 'apiblueprint-operational-');
        if ($temporaryFile === false) {
            throw new RuntimeException('Unable to create temporary operational-contract archive.');
        }

        try {
            if (file_put_contents($temporaryFile, $exported->content) === false) {
                throw new RuntimeException('Unable to stage generated archive for operational contract.');
            }

            $zip = new ZipArchive;
            if ($zip->open($temporaryFile) !== true) {
                throw new RuntimeException('Unable to open generated archive for operational contract.');
            }

            $projectRoot = pathinfo($exported->filename, PATHINFO_FILENAME);
            $routeEventCodes = $this->routeEventCodes($manifest);
            $selectedEndpoint = $this->selectedEndpoint($manifest, $routeEventCodes);

            $files = [
                'config/apiblueprint_audit.php' => $this->auditConfigFile($routeEventCodes),
                'app/Infrastructure/Audit/LogAuditTrail.php' => $this->auditTrailFile(),
                'app/Presentation/Http/Middleware/AuditRequestMiddleware.php' => $this->auditMiddlewareFile(),
                'tests/Feature/AuditSemanticContractTest.php' => $this->auditSemanticContractTestFile($selectedEndpoint),
            ];

            foreach ($files as $path => $content) {
                if (! $zip->addFromString($projectRoot.'/'.$path, $content)) {
                    $zip->close();
                    throw new RuntimeException("Unable to add generated operational-contract file: $path");
                }
            }

            if (! $zip->close()) {
                throw new RuntimeException('Unable to finalize generated operational-contract archive.');
            }

            $content = file_get_contents($temporaryFile);
            if ($content === false) {
                throw new RuntimeException('Unable to read generated operational-contract archive.');
            }

            return new ExportedBlueprint(
                $exported->filename,
                $content,
                $exported->mimeType,
            );
        } finally {
            @unlink($temporaryFile);
        }
    }

    /** @return array<string, string> */
    private function routeEventCodes(array $manifest): array
    {
        $features = [];
        foreach ($this->catalog->get()['features'] as $feature) {
            $features[$feature['id']] = $feature;
        }

        $routeEventCodes = [];
        foreach ($manifest['endpoints'] ?? [] as $endpoint) {
            $feature = $features[$endpoint['id']] ?? [];
            $auditEvents = is_array($feature['audit_events'] ?? null) ? $feature['audit_events'] : [];
            $eventCode = is_string($auditEvents[0] ?? null) && $auditEvents[0] !== ''
                ? $auditEvents[0]
                : 'audit.request';

            $routeEventCodes['api.v1.'.$endpoint['id']] = $eventCode;
        }

        ksort($routeEventCodes);

        return $routeEventCodes;
    }

    /** @param array<string, string> $routeEventCodes */
    private function selectedEndpoint(array $manifest, array $routeEventCodes): array
    {
        $endpoint = $manifest['endpoints'][0] ?? null;
        if (! is_array($endpoint)) {
            return [
                'route_name' => 'api.v1.__audit_probe__',
                'event_code' => 'audit.request',
                'method' => 'GET',
                'path' => '/api/v1/__audit_probe__',
            ];
        }

        $routeName = 'api.v1.'.$endpoint['id'];

        return [
            'route_name' => $routeName,
            'event_code' => $routeEventCodes[$routeName] ?? 'audit.request',
            'method' => $endpoint['method'],
            'path' => preg_replace('/\{[^}]+\}/', 'audit-target-123', $endpoint['path']) ?? $endpoint['path'],
        ];
    }

    /** @param array<string, string> $routeEventCodes */
    private function auditConfigFile(array $routeEventCodes): string
    {
        $rows = [];
        foreach ($routeEventCodes as $routeName => $eventCode) {
            $rows[] = "        '$routeName' => '$eventCode',";
        }
        $mapping = $rows === [] ? '' : "\n".implode("\n", $rows)."\n    ";

        return <<<PHP
<?php

return [
    'route_event_codes' => [$mapping],
];
PHP.PHP_EOL;
    }

    private function auditTrailFile(): string
    {
        return <<<'PHP'
<?php

namespace App\Infrastructure\Audit;

use App\Application\Shared\Contracts\AuditTrail;
use RuntimeException;

final class LogAuditTrail implements AuditTrail
{
    private const SENSITIVE_KEYS = [
        'access_token',
        'api_key',
        'authorization',
        'hash',
        'password',
        'password_confirmation',
        'refresh_token',
        'secret',
        'token',
    ];

    public function record(array $event): void
    {
        $payload = json_encode(
            $this->sanitize($event),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        );
        $path = storage_path('logs/audit.jsonl');

        if (file_put_contents($path, $payload.PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
            throw new RuntimeException('Unable to append durable audit record.');
        }
    }

    private function sanitize(mixed $value, ?string $key = null): mixed
    {
        if ($key !== null && in_array(strtolower($key), self::SENSITIVE_KEYS, true)) {
            return '[REDACTED]';
        }

        if (is_array($value)) {
            $sanitized = [];
            foreach ($value as $childKey => $childValue) {
                $sanitized[$childKey] = $this->sanitize($childValue, is_string($childKey) ? $childKey : null);
            }

            return $sanitized;
        }

        if (is_string($value) && preg_match('/^Bearer\s+/i', $value) === 1) {
            return '[REDACTED]';
        }

        return $value;
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
        $routeName = $request->route()?->getName();
        $eventCode = is_string($routeName)
            ? config('apiblueprint_audit.route_event_codes.'.$routeName, 'audit.request')
            : 'audit.request';
        $targetId = $request->route('id');

        $this->auditTrail->record([
            'event_code' => is_string($eventCode) ? $eventCode : 'audit.request',
            'occurred_at' => now()->toIso8601String(),
            'route' => $routeName,
            'method' => $request->method(),
            'path' => '/'.$request->path(),
            'status' => $response->getStatusCode(),
            'outcome' => $response->isSuccessful() || $response->isRedirection() ? 'success' : 'failure',
            'correlation_id' => $request->attributes->get('correlation_id'),
            'actor_id' => $request->user()?->getAuthIdentifier(),
            'target_id' => is_scalar($targetId) ? (string) $targetId : null,
        ]);

        return $response;
    }
}
PHP;
    }

    private function auditSemanticContractTestFile(array $endpoint): string
    {
        $routeName = var_export($endpoint['route_name'], true);
        $eventCode = var_export($endpoint['event_code'], true);
        $method = var_export($endpoint['method'], true);
        $path = var_export($endpoint['path'], true);

        return <<<PHP
<?php

namespace Tests\Feature;

use App\Application\Shared\Contracts\AuditTrail;
use App\Infrastructure\Audit\LogAuditTrail;
use App\Presentation\Http\Middleware\AuditRequestMiddleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Tests\TestCase;

final class AuditSemanticContractTest extends TestCase
{
    public function test_middleware_emits_canonical_semantic_event_without_copying_request_secrets(): void
    {
        \$recordingAuditTrail = new class implements AuditTrail
        {
            public array \$events = [];

            public function record(array \$event): void
            {
                \$this->events[] = \$event;
            }
        };
        \$request = Request::create($path, $method, [
            'password' => 'semantic-audit-password-should-not-leak',
            'token' => 'semantic-audit-token-should-not-leak',
        ]);
        \$request->headers->set('Authorization', 'Bearer semantic-audit-authorization-should-not-leak');
        \$request->attributes->set('correlation_id', 'audit-correlation-123');
        \$route = new Route([$method], ltrim($path, '/'), static fn () => null);
        \$route->name($routeName);
        \$request->setRouteResolver(static fn () => \$route);

        \$response = (new AuditRequestMiddleware(\$recordingAuditTrail))->handle(
            \$request,
            static fn () => response()->json(['ok' => true]),
        );

        \$this->assertSame(200, \$response->getStatusCode());
        \$this->assertCount(1, \$recordingAuditTrail->events);
        \$event = \$recordingAuditTrail->events[0];
        \$this->assertSame($eventCode, \$event['event_code']);
        \$this->assertSame('success', \$event['outcome']);
        \$this->assertSame('audit-correlation-123', \$event['correlation_id']);
        \$serialized = json_encode(\$event, JSON_THROW_ON_ERROR);
        \$this->assertStringNotContainsString('semantic-audit-password-should-not-leak', \$serialized);
        \$this->assertStringNotContainsString('semantic-audit-token-should-not-leak', \$serialized);
        \$this->assertStringNotContainsString('semantic-audit-authorization-should-not-leak', \$serialized);
    }

    public function test_durable_sink_recursively_redacts_sensitive_values(): void
    {
        \$path = storage_path('logs/audit.jsonl');
        @unlink(\$path);

        try {
            (new LogAuditTrail)->record([
                'event_code' => 'audit.test',
                'password' => 'sink-password-should-not-leak',
                'nested' => [
                    'access_token' => 'sink-token-should-not-leak',
                    'authorization' => 'Bearer sink-authorization-should-not-leak',
                ],
            ]);

            \$contents = (string) file_get_contents(\$path);
            \$this->assertStringContainsString('audit.test', \$contents);
            \$this->assertStringContainsString('[REDACTED]', \$contents);
            \$this->assertStringNotContainsString('sink-password-should-not-leak', \$contents);
            \$this->assertStringNotContainsString('sink-token-should-not-leak', \$contents);
            \$this->assertStringNotContainsString('sink-authorization-should-not-leak', \$contents);
        } finally {
            @unlink(\$path);
        }
    }
}
PHP;
    }
}
