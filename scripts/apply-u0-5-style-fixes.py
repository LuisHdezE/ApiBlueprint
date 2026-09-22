#!/usr/bin/env python3

from pathlib import Path

path = Path('app/Infrastructure/Blueprint/LaravelZipBlueprintExporter.php')
text = path.read_text(encoding='utf-8')

export_old = '''        foreach ($this->buildFiles($manifest) as $path => $content) {
            $zip->addFromString("$slug/$path", $content);
        }'''
export_new = '''        foreach ($this->buildFiles($manifest) as $path => $content) {
            $zip->addFromString("$slug/$path", $this->normalizeFile($path, $content));
        }'''
if export_old not in text:
    raise SystemExit('export loop not found')
text = text.replace(export_old, export_new, 1)

providers_old = '''            'bootstrap/providers.php' => "<?php\\n\\nreturn [\\n    App\\\\Providers\\\\AppServiceProvider::class,\\n];\\n",'''
providers_new = '''            'bootstrap/providers.php' => "<?php\\n\\nuse App\\\\Providers\\\\AppServiceProvider;\\n\\nreturn [\\n    AppServiceProvider::class,\\n];\\n",'''
if providers_old not in text:
    raise SystemExit('bootstrap/providers.php template not found')
text = text.replace(providers_old, providers_new, 1)

request_old = """<?php

define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';

(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Illuminate\\Http\\Request::capture());"""
request_new = """<?php

use Illuminate\\Http\\Request;

define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';

(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());"""
if request_old not in text:
    raise SystemExit('public index template not found')
text = text.replace(request_old, request_new, 1)

throwable_import = "            'use Symfony\\\\Component\\\\HttpKernel\\\\Exception\\\\HttpExceptionInterface;',\n"
if throwable_import not in text:
    raise SystemExit('bootstrap import anchor not found')
text = text.replace(throwable_import, throwable_import + "            'use Throwable;',\n", 1)

throwable_arrow_old = 'static fn (Request \\$request, \\\\Throwable \\$exception): bool'
throwable_arrow_new = 'static fn (Request \\$request, Throwable \\$exception): bool'
if throwable_arrow_old not in text:
    raise SystemExit('Throwable arrow type not found')
text = text.replace(throwable_arrow_old, throwable_arrow_new, 1)

throwable_render_old = 'render(function (\\\\Throwable \\$exception, Request \\$request)'
throwable_render_new = 'render(function (Throwable \\$exception, Request \\$request)'
if throwable_render_old not in text:
    raise SystemExit('Throwable render type not found')
text = text.replace(throwable_render_old, throwable_render_new, 1)

routes_anchor = '''    private function routesFile(array $manifest): string
    {
        $imports = [];
        $routes = [];
'''
routes_replacement = '''    private function routesFile(array $manifest): string
    {
        if ($manifest['endpoints'] === []) {
            return "<?php\\n";
        }

        $imports = [];
        $routes = [];
'''
if routes_anchor not in text:
    raise SystemExit('routesFile anchor not found')
text = text.replace(routes_anchor, routes_replacement, 1)

slug_anchor = '''    private function slug(string $value): string
    {'''
normalize_method = '''    private function normalizeFile(string $path, string $content): string
    {
        if (str_ends_with($path, '.php')) {
            return rtrim($content).PHP_EOL;
        }

        return $content;
    }

'''
if slug_anchor not in text:
    raise SystemExit('slug anchor not found')
text = text.replace(slug_anchor, normalize_method + slug_anchor, 1)

path.write_text(text, encoding='utf-8')
print('U0.5 generated PHP style fixes applied.')
