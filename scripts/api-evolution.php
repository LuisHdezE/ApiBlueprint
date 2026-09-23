<?php

declare(strict_types=1);

use App\Application\Blueprint\Queries\GetTraceableMasterOpenApi;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$command = $argv[1] ?? null;
$baselinePath = $argv[2] ?? __DIR__.'/../.blueprint/api-contract-baseline.json';

if (! is_string($command) || ! in_array($command, ['capture', 'validate'], true)) {
    fwrite(STDERR, "Usage: php scripts/api-evolution.php <capture|validate> [baseline-path] [source-revision]\n");
    exit(2);
}

$document = $app->make(GetTraceableMasterOpenApi::class)->handle();
$current = buildSnapshot($document, (string) config('blueprint.api_version', 'v1'));

if ($command === 'capture') {
    $sourceRevision = $argv[3] ?? null;
    if (! is_string($sourceRevision) || $sourceRevision === '') {
        fwrite(STDERR, "capture requires a non-empty source revision.\n");
        exit(2);
    }

    $current['source_revision'] = $sourceRevision;
    $json = json_encode($current, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL;

    if ($baselinePath === '-') {
        fwrite(STDOUT, $json);
        exit(0);
    }

    $directory = dirname($baselinePath);
    if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
        fwrite(STDERR, "Unable to create baseline directory: {$directory}\n");
        exit(2);
    }

    if (file_put_contents($baselinePath, $json) === false) {
        fwrite(STDERR, "Unable to write API contract baseline: {$baselinePath}\n");
        exit(2);
    }

    fwrite(STDOUT, "API contract baseline captured: {$baselinePath}\n");
    exit(0);
}

if (! is_file($baselinePath)) {
    fwrite(STDERR, "API contract baseline is missing: {$baselinePath}\n");
    exit(1);
}

$baseline = json_decode((string) file_get_contents($baselinePath), true, 512, JSON_THROW_ON_ERROR);
if (! is_array($baseline)) {
    fwrite(STDERR, "API contract baseline is invalid.\n");
    exit(1);
}

$errors = validateBaselineShape($baseline);
if ($errors !== []) {
    foreach ($errors as $error) {
        fwrite(STDERR, "Baseline error: {$error}\n");
    }
    exit(1);
}

$diff = compareSnapshots($baseline, $current);
if ($diff['changed_operation_ids'] === []) {
    fwrite(STDOUT, sprintf(
        "API evolution guard PASS: %d operationIds match baseline %s.\n",
        count($current['operations']),
        $baseline['contract_revision'],
    ));
    exit(0);
}

$impactPath = resolveImpactPath($baseline, $current);
if ($impactPath === null) {
    fwrite(STDERR, "API contract drift detected without one matching canonical API impact report.\n");
    fwrite(STDERR, json_encode($diff, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);
    exit(1);
}

$impact = json_decode((string) file_get_contents($impactPath), true, 512, JSON_THROW_ON_ERROR);
if (! is_array($impact)) {
    fwrite(STDERR, "API impact report is invalid JSON object: {$impactPath}\n");
    exit(1);
}

$impactErrors = validateImpactReport($impact, $baseline, $current, $diff);
if ($impactErrors !== []) {
    foreach ($impactErrors as $error) {
        fwrite(STDERR, "Impact error: {$error}\n");
    }
    exit(1);
}

fwrite(STDOUT, sprintf(
    "API evolution guard PASS with %s covering %d changed operationIds.\n",
    $impact['change_id'],
    count($diff['changed_operation_ids']),
));

/** @return array<string, mixed> */
function buildSnapshot(array $document, string $apiVersion): array
{
    $schemas = is_array($document['components']['schemas'] ?? null) ? $document['components']['schemas'] : [];
    $securitySchemes = is_array($document['components']['securitySchemes'] ?? null) ? $document['components']['securitySchemes'] : [];
    $operations = [];

    foreach ($document['paths'] ?? [] as $path => $pathItem) {
        if (! is_array($pathItem)) {
            continue;
        }

        foreach ($pathItem as $method => $operation) {
            if (! is_array($operation) || ! is_string($operation['operationId'] ?? null)) {
                continue;
            }

            $operationId = $operation['operationId'];
            $consumerContract = array_filter([
                'parameters' => $operation['parameters'] ?? null,
                'requestBody' => $operation['requestBody'] ?? null,
                'responses' => $operation['responses'] ?? null,
                'security' => $operation['security'] ?? null,
            ], static fn (mixed $value): bool => $value !== null);

            $referencedSchemas = collectReferencedSchemas($consumerContract, $schemas);
            $relevantSecurity = isset($consumerContract['security']) ? $securitySchemes : [];
            $fingerprintPayload = canonicalize([
                'method' => strtoupper((string) $method),
                'path' => (string) $path,
                'contract' => $consumerContract,
                'schemas' => $referencedSchemas,
                'security_schemes' => $relevantSecurity,
            ]);

            $operations[$operationId] = [
                'operation_id' => $operationId,
                'method' => strtoupper((string) $method),
                'path' => (string) $path,
                'contract_sha256' => hash('sha256', json_encode($fingerprintPayload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)),
            ];
        }
    }

    ksort($operations);
    $operations = array_values($operations);
    $revisionPayload = canonicalize([
        'api_version' => $apiVersion,
        'operations' => $operations,
    ]);

    return [
        'schema_version' => '1.0',
        'blueprint_version' => '0.5.4',
        'api_version' => $apiVersion,
        'contract_revision' => hash('sha256', json_encode($revisionPayload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)),
        'operations' => $operations,
    ];
}

/** @return array<string, mixed> */
function collectReferencedSchemas(mixed $value, array $schemas, array &$collected = []): array
{
    if (! is_array($value)) {
        return $collected;
    }

    foreach ($value as $child) {
        if (is_array($child)) {
            collectReferencedSchemas($child, $schemas, $collected);
            continue;
        }

        if (! is_string($child) || ! str_starts_with($child, '#/components/schemas/')) {
            continue;
        }

        $name = substr($child, strlen('#/components/schemas/'));
        if ($name === '' || isset($collected[$name]) || ! isset($schemas[$name]) || ! is_array($schemas[$name])) {
            continue;
        }

        $collected[$name] = $schemas[$name];
        collectReferencedSchemas($schemas[$name], $schemas, $collected);
    }

    ksort($collected);

    return $collected;
}

function canonicalize(mixed $value): mixed
{
    if (! is_array($value)) {
        return $value;
    }

    if (array_is_list($value)) {
        return array_map('canonicalize', $value);
    }

    ksort($value);
    foreach ($value as $key => $child) {
        $value[$key] = canonicalize($child);
    }

    return $value;
}

/** @return list<string> */
function validateBaselineShape(array $baseline): array
{
    $errors = [];
    foreach (['schema_version', 'blueprint_version', 'api_version', 'contract_revision', 'source_revision', 'operations'] as $required) {
        if (! array_key_exists($required, $baseline)) {
            $errors[] = "missing {$required}";
        }
    }

    if (($baseline['schema_version'] ?? null) !== '1.0') {
        $errors[] = 'schema_version must be 1.0';
    }
    if (($baseline['blueprint_version'] ?? null) !== '0.5.4') {
        $errors[] = 'blueprint_version must be 0.5.4';
    }
    if (! is_array($baseline['operations'] ?? null) || $baseline['operations'] === []) {
        $errors[] = 'operations must be a non-empty array';
    }

    return $errors;
}

/** @return array{changed_operation_ids:list<string>, added:list<string>, removed:list<string>, modified:list<string>} */
function compareSnapshots(array $baseline, array $current): array
{
    $baselineMap = [];
    foreach ($baseline['operations'] ?? [] as $operation) {
        if (is_array($operation) && is_string($operation['operation_id'] ?? null)) {
            $baselineMap[$operation['operation_id']] = $operation;
        }
    }

    $currentMap = [];
    foreach ($current['operations'] ?? [] as $operation) {
        if (is_array($operation) && is_string($operation['operation_id'] ?? null)) {
            $currentMap[$operation['operation_id']] = $operation;
        }
    }

    $added = array_values(array_diff(array_keys($currentMap), array_keys($baselineMap)));
    $removed = array_values(array_diff(array_keys($baselineMap), array_keys($currentMap)));
    $modified = [];

    foreach (array_intersect(array_keys($baselineMap), array_keys($currentMap)) as $operationId) {
        if (($baselineMap[$operationId]['contract_sha256'] ?? null) !== ($currentMap[$operationId]['contract_sha256'] ?? null)
            || ($baselineMap[$operationId]['method'] ?? null) !== ($currentMap[$operationId]['method'] ?? null)
            || ($baselineMap[$operationId]['path'] ?? null) !== ($currentMap[$operationId]['path'] ?? null)) {
            $modified[] = $operationId;
        }
    }

    sort($added);
    sort($removed);
    sort($modified);
    $changed = array_values(array_unique(array_merge($added, $removed, $modified)));
    sort($changed);

    return [
        'changed_operation_ids' => $changed,
        'added' => $added,
        'removed' => $removed,
        'modified' => $modified,
    ];
}

function resolveImpactPath(array $baseline, array $current): ?string
{
    $override = getenv('API_IMPACT_FILE');
    if (is_string($override) && $override !== '') {
        if (! is_file($override)) {
            fwrite(STDERR, "API impact report not found: {$override}\n");
            return null;
        }

        return $override;
    }

    $candidates = glob(__DIR__.'/../.blueprint/api-impacts/API-IMPACT-*.json') ?: [];
    $matches = [];

    foreach ($candidates as $candidate) {
        try {
            $impact = json_decode((string) file_get_contents($candidate), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            continue;
        }

        if (! is_array($impact)) {
            continue;
        }

        if (($impact['previous_revision'] ?? null) === ($baseline['contract_revision'] ?? null)
            && ($impact['new_revision'] ?? null) === ($current['contract_revision'] ?? null)) {
            $matches[] = $candidate;
        }
    }

    if (count($matches) !== 1) {
        if ($matches === []) {
            fwrite(STDERR, "No versioned API impact report matches the detected revision transition.\n");
        } else {
            fwrite(STDERR, "Multiple API impact reports match the detected revision transition; exactly one is required.\n");
        }

        return null;
    }

    return $matches[0];
}

/** @return list<string> */
function validateImpactReport(array $impact, array $baseline, array $current, array $diff): array
{
    $errors = [];
    $required = [
        'schema_version', 'change_id', 'previous_revision', 'new_revision', 'classification',
        'changed_operation_ids', 'changed_contract_areas', 'affected_slices', 'revalidation_policy',
        'preserve_unrelated_evidence', 'evidence_ids',
    ];
    foreach ($required as $field) {
        if (! array_key_exists($field, $impact)) {
            $errors[] = "missing {$field}";
        }
    }

    if (($impact['schema_version'] ?? null) !== '0.5.4') {
        $errors[] = 'schema_version must be 0.5.4';
    }
    if (! is_string($impact['change_id'] ?? null) || preg_match('/^API-IMPACT-[0-9]{3,}$/', $impact['change_id']) !== 1) {
        $errors[] = 'change_id must match API-IMPACT-[0-9]{3,}';
    }
    if (($impact['previous_revision'] ?? null) !== ($baseline['contract_revision'] ?? null)) {
        $errors[] = 'previous_revision must equal the frozen baseline contract revision';
    }
    if (($impact['new_revision'] ?? null) !== ($current['contract_revision'] ?? null)) {
        $errors[] = 'new_revision must equal the current contract revision';
    }

    $classPolicies = [
        'operation_local' => 'affected_only',
        'platform_cross_cutting' => 'platform',
        'project_cross_cutting' => 'project',
    ];
    $classification = $impact['classification'] ?? null;
    if (! is_string($classification) || ! isset($classPolicies[$classification])) {
        $errors[] = 'classification is invalid';
    } elseif (($impact['revalidation_policy'] ?? null) !== $classPolicies[$classification]) {
        $errors[] = 'revalidation_policy does not match classification';
    }

    $actualChanged = $diff['changed_operation_ids'];
    $reportedChanged = is_array($impact['changed_operation_ids'] ?? null) ? $impact['changed_operation_ids'] : [];
    sort($reportedChanged);
    if ($reportedChanged !== $actualChanged) {
        $errors[] = 'changed_operation_ids must exactly match detected contract drift';
    }

    $validAreas = ['auth', 'authorization', 'security', 'error_contract', 'versioning', 'data_schema', 'other'];
    $areas = $impact['changed_contract_areas'] ?? null;
    if (! is_array($areas) || $areas === [] || array_diff($areas, $validAreas) !== []) {
        $errors[] = 'changed_contract_areas must contain canonical Blueprint values';
    }

    $slices = $impact['affected_slices'] ?? null;
    if (! is_array($slices)) {
        $errors[] = 'affected_slices must be an array';
    } else {
        foreach ($slices as $slice) {
            if (! is_array($slice)
                || ! is_string($slice['id'] ?? null)
                || preg_match('/^[a-z0-9][a-z0-9._-]*$/', $slice['id']) !== 1
                || ! in_array($slice['platform'] ?? null, ['web', 'android', 'ios'], true)) {
                $errors[] = 'affected_slices contains an invalid entry';
                break;
            }
        }
    }

    if (($impact['preserve_unrelated_evidence'] ?? null) !== true) {
        $errors[] = 'preserve_unrelated_evidence must be true';
    }

    $evidenceIds = $impact['evidence_ids'] ?? null;
    if (! is_array($evidenceIds) || $evidenceIds === []) {
        $errors[] = 'evidence_ids must be non-empty';
    } else {
        foreach ($evidenceIds as $evidenceId) {
            if (! is_string($evidenceId) || preg_match('/^EVD-[A-Z0-9][A-Z0-9._-]*$/', $evidenceId) !== 1) {
                $errors[] = 'evidence_ids contains an invalid ID';
                break;
            }
        }
    }

    return $errors;
}
