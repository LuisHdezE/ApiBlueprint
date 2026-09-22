#!/usr/bin/env python3

from pathlib import Path

path = Path("app/Infrastructure/Blueprint/LaravelZipBlueprintExporter.php")
text = path.read_text(encoding="utf-8")

artisan_line = "            'artisan' => $this->artisanFile(),\n"
runtime_directories = artisan_line + """            'bootstrap/cache/.gitignore' => "*\\n!.gitignore\\n",
            'storage/app/.gitignore' => "*\\n!private/\\n!public/\\n!.gitignore\\n",
            'storage/app/private/.gitignore' => "*\\n!.gitignore\\n",
            'storage/app/public/.gitignore' => "*\\n!.gitignore\\n",
            'storage/framework/cache/.gitignore' => "*\\n!data/\\n!.gitignore\\n",
            'storage/framework/cache/data/.gitignore' => "*\\n!.gitignore\\n",
            'storage/framework/sessions/.gitignore' => "*\\n!.gitignore\\n",
            'storage/framework/testing/.gitignore' => "*\\n!.gitignore\\n",
            'storage/framework/views/.gitignore' => "*\\n!.gitignore\\n",
            'storage/logs/.gitignore' => "*\\n!.gitignore\\n",
"""

if text.count(artisan_line) != 1:
    raise SystemExit("Expected exactly one artisan file entry.")
text = text.replace(artisan_line, runtime_directories, 1)

contract_start = text.index("    private function contractTestFile(array $manifest): string\n")
contract_end = text.index("    private function governanceContractTestFile(array $manifest): string\n", contract_start)
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

'''
text = text[:contract_start] + contract_method + text[contract_end:]

openapi_start = text.index("    private function openApiFile(array $manifest): string\n")
openapi_end = text.index("    private function readme(array $manifest): string\n", openapi_start)
openapi_method = r'''    private function openApiFile(array $manifest): string
    {
        $governance = $manifest['governance'];
        $lines = [
            'openapi: 3.1.0',
            'info:',
            '  title: '.$this->yamlString($manifest['project']['name'].' API'),
            '  version: "1.0.0"',
            '  description: "Contrato OpenAPI generado por ApiBlueprint. Los textos visibles se presentan en español."',
        ];

        if ($manifest['endpoints'] === []) {
            $lines[] = 'paths: {}';
        } else {
            $lines[] = 'paths:';
            $endpointsByPath = [];
            foreach ($manifest['endpoints'] as $endpoint) {
                $endpointsByPath[$endpoint['path']][] = $endpoint;
            }

            foreach ($endpointsByPath as $path => $endpoints) {
                $lines[] = '  '.$this->yamlString($path).':';

                foreach ($endpoints as $endpoint) {
                    $method = strtolower($endpoint['method']);
                    $lines[] = "    $method:";
                    $lines[] = '      operationId: '.str_replace('.', '_', $endpoint['id']);
                    $lines[] = '      summary: '.$this->yamlString($endpoint['summary']);
                    $lines[] = '      tags: ['.$this->yamlString($endpoint['capability_label']).']';
                    $lines[] = '      x-exposure: '.$endpoint['exposure'];

                    if ($endpoint['exposure'] !== 'public' && $governance['authentication'] === 'sanctum') {
                        $lines[] = '      security:';
                        $lines[] = '        - bearerAuth: []';
                    }

                    $parameters = [];
                    if (preg_match_all('/\{([^}]+)\}/', $endpoint['path'], $matches) > 0) {
                        foreach ($matches[1] as $parameterName) {
                            $parameters[] = '        - { name: '.$this->yamlString($parameterName).', in: path, required: true, schema: { type: string } }';
                        }
                    }

                    if (str_ends_with($endpoint['id'], '.list')) {
                        $parameters[] = '        - { name: "page[size]", in: query, schema: { type: integer, default: '.$governance['pagination']['default_size'].', maximum: '.$governance['pagination']['max_size'].' } }';
                        $pageKey = $governance['pagination']['strategy'] === 'cursor' ? 'page[cursor]' : 'page[number]';
                        $pageType = $governance['pagination']['strategy'] === 'cursor' ? 'string' : 'integer';
                        $parameters[] = '        - { name: '.$this->yamlString($pageKey).', in: query, schema: { type: '.$pageType.' } }';
                        if ($governance['filtering']) {
                            $parameters[] = '        - { name: "filter[field]", in: query, schema: { type: string }, description: "Filtro por campo permitido." }';
                        }
                        if ($governance['sorting']) {
                            $parameters[] = '        - { name: sort, in: query, schema: { type: string }, description: "Campos de orden separados por coma; prefijo - para descendente." }';
                        }
                    }

                    if ($parameters !== []) {
                        $lines[] = '      parameters:';
                        array_push($lines, ...$parameters);
                    }

                    $lines[] = '      responses:';
                    $lines[] = "        '501':";
                    $lines[] = '          description: "Endpoint generado pendiente de implementación."';
                    if ($governance['rate_limiting']['enabled']) {
                        $lines[] = "        '429':";
                        $lines[] = '          description: "Se superó el límite de solicitudes permitido."';
                        $lines[] = '          content: { application/problem+json: { schema: { $ref: "#/components/schemas/ProblemDetails" } } }';
                    }
                }
            }
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

'''
text = text[:openapi_start] + openapi_method + text[openapi_end:]

path.write_text(text, encoding="utf-8")
print("U0.5 exporter fixes applied.")
