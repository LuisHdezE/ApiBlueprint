<?php

declare(strict_types=1);

use App\Application\Blueprint\UseCases\ExportBlueprintSolution;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$templateId = $argv[1] ?? null;
$outputPath = $argv[2] ?? null;
$profile = $argv[3] ?? 'default';

if (! is_string($templateId) || $templateId === '' || ! is_string($outputPath) || $outputPath === '') {
    fwrite(STDERR, "Usage: php scripts/export-template.php <template> <output.zip> [profile]\n");
    exit(2);
}

$catalog = config('blueprint');
if (! is_array($catalog)) {
    fwrite(STDERR, "Blueprint catalog is unavailable.\n");
    exit(2);
}

$template = null;
foreach ($catalog['templates'] as $candidate) {
    if (($candidate['id'] ?? null) === $templateId) {
        $template = $candidate;
        break;
    }
}

if (! is_array($template)) {
    fwrite(STDERR, "Unknown template: {$templateId}\n");
    exit(2);
}

$definitions = [];
foreach ($catalog['endpoints'] as $endpoint) {
    $definitions[$endpoint['id']] = $endpoint;
}

$endpoints = [];
foreach ($template['endpoints'] as $endpointId) {
    if (! isset($definitions[$endpointId])) {
        fwrite(STDERR, "Template references unknown endpoint: {$endpointId}\n");
        exit(2);
    }

    $endpoints[] = [
        'id' => $endpointId,
        'exposure' => $definitions[$endpointId]['default_exposure'],
    ];
}

$governance = $catalog['governance']['defaults'];
if ($profile === 'offset-no-rate') {
    $governance['pagination']['strategy'] = 'offset';
    $governance['rate_limiting']['enabled'] = false;
} elseif ($profile !== 'default') {
    fwrite(STDERR, "Unknown acceptance profile: {$profile}\n");
    exit(2);
}

$manifest = [
    'schema_version' => $catalog['schema_version'],
    'generator' => 'ApiBlueprint',
    'project' => [
        'name' => 'Acceptance '.ucfirst($templateId),
        'api_version' => $catalog['api_version'],
    ],
    'template' => $templateId,
    'governance' => $governance,
    'endpoints' => $endpoints,
];

$exported = $app->make(ExportBlueprintSolution::class)->handle($manifest);
$directory = dirname($outputPath);
if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
    fwrite(STDERR, "Unable to create output directory: {$directory}\n");
    exit(2);
}

if (file_put_contents($outputPath, $exported->content) === false) {
    fwrite(STDERR, "Unable to write generated archive: {$outputPath}\n");
    exit(2);
}

fwrite(STDOUT, $outputPath."\n");
