from pathlib import Path

path = Path('app/Infrastructure/Blueprint/LaravelZipBlueprintExporter.php')
text = path.read_text()

old = """        $middlewareLines = [];

        if ($manifest['governance']['correlation_id']) {
            $imports[] = 'use App\\\\Presentation\\\\Http\\\\Middleware\\\\CorrelationIdMiddleware;';
            $middlewareLines[] = '        $middleware->append(CorrelationIdMiddleware::class);';
        }
        if ($manifest['governance']['idempotency']) {
            $imports[] = 'use App\\\\Presentation\\\\Http\\\\Middleware\\\\IdempotencyMiddleware;';
            $middlewareLines[] = \"        \\$middleware->alias(['idempotency' => IdempotencyMiddleware::class]);\";
        }
        if ($manifest['governance']['audit']) {
            $imports[] = 'use App\\\\Presentation\\\\Http\\\\Middleware\\\\AuditRequestMiddleware;';
            $middlewareLines[] = \"        \\$middleware->alias(['audit.request' => AuditRequestMiddleware::class]);\";
        }

        sort($imports);
"""

new = """        $middlewareLines = [];
        $middlewareAliases = [];

        if ($manifest['governance']['correlation_id']) {
            $imports[] = 'use App\\\\Presentation\\\\Http\\\\Middleware\\\\CorrelationIdMiddleware;';
            $middlewareLines[] = '        $middleware->append(CorrelationIdMiddleware::class);';
        }
        if ($manifest['governance']['idempotency']) {
            $imports[] = 'use App\\\\Presentation\\\\Http\\\\Middleware\\\\IdempotencyMiddleware;';
            $middlewareAliases['idempotency'] = 'IdempotencyMiddleware::class';
        }
        if ($manifest['governance']['audit']) {
            $imports[] = 'use App\\\\Presentation\\\\Http\\\\Middleware\\\\AuditRequestMiddleware;';
            $middlewareAliases['audit.request'] = 'AuditRequestMiddleware::class';
        }
        if ($middlewareAliases !== []) {
            $aliases = [];
            foreach ($middlewareAliases as $alias => $class) {
                $aliases[] = \"'{$alias}' => {$class}\";
            }
            $middlewareLines[] = '        $middleware->alias(['.implode(', ', $aliases).']);';
        }

        sort($imports);
"""

count = text.count(old)
if count != 1:
    raise SystemExit(f'expected bootstrap middleware block once, got {count}')

path.write_text(text.replace(old, new, 1))
print('middleware alias composition fixed')
