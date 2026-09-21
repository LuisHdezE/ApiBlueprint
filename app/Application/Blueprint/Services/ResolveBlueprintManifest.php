<?php

namespace App\Application\Blueprint\Services;

use App\Application\Blueprint\Contracts\BlueprintCatalog;
use App\Application\Blueprint\Exceptions\InvalidBlueprintManifest;
use RuntimeException;

final readonly class ResolveBlueprintManifest
{
    public function __construct(private BlueprintCatalog $catalog)
    {
        //
    }

    public function handle(array $manifest): array
    {
        $catalog = $this->catalog->get();
        $errors = [];

        if (($manifest['schema_version'] ?? null) !== $catalog['schema_version']) {
            $errors['schema_version'][] = 'La versión del manifest no coincide con la versión soportada por ApiBlueprint.';
        }

        $project = is_array($manifest['project'] ?? null) ? $manifest['project'] : [];
        $projectName = trim((string) ($project['name'] ?? ''));

        if ($projectName === '') {
            $errors['project.name'][] = 'El nombre del proyecto es obligatorio.';
        } elseif (mb_strlen($projectName) > 80) {
            $errors['project.name'][] = 'El nombre del proyecto no puede superar los 80 caracteres.';
        }

        if (($project['api_version'] ?? null) !== $catalog['api_version']) {
            $errors['project.api_version'][] = 'La versión de API solicitada no está soportada.';
        }

        $template = $manifest['template'] ?? 'custom';
        $templateIds = array_column($catalog['templates'], 'id');
        if (is_string($template) === false || ($template !== 'custom' && in_array($template, $templateIds, true) === false)) {
            $errors['template'][] = 'La plantilla indicada no existe.';
        }

        $endpointDefinitions = [];
        foreach ($catalog['endpoints'] as $endpoint) {
            $endpointDefinitions[$endpoint['id']] = $endpoint;
        }

        $allowedExposures = array_column($catalog['exposures'], 'id');
        $submittedEndpoints = $manifest['endpoints'] ?? null;
        $selected = [];

        if (is_array($submittedEndpoints) === false) {
            $errors['endpoints'][] = 'La colección de endpoints es obligatoria.';
        } else {
            foreach ($submittedEndpoints as $index => $submittedEndpoint) {
                if (is_array($submittedEndpoint) === false) {
                    $errors["endpoints.$index"][] = 'El endpoint debe ser un objeto válido.';

                    continue;
                }

                $id = (string) ($submittedEndpoint['id'] ?? '');
                $exposure = (string) ($submittedEndpoint['exposure'] ?? '');

                if (isset($endpointDefinitions[$id]) === false) {
                    $errors["endpoints.$index.id"][] = "El endpoint '$id' no pertenece al catálogo de ApiBlueprint.";

                    continue;
                }

                if (in_array($exposure, $allowedExposures, true) === false) {
                    $errors["endpoints.$index.exposure"][] = 'El perfil de exposición indicado no es válido.';

                    continue;
                }

                $selected[$id] = ['exposure' => $exposure];
            }
        }

        $governance = $this->resolveGovernance($manifest['governance'] ?? [], $catalog, $errors);

        if ($errors !== []) {
            throw new InvalidBlueprintManifest($errors);
        }

        $autoAdded = [];
        $changed = true;

        while ($changed) {
            $changed = false;

            foreach (array_keys($selected) as $endpointId) {
                $selection = $selected[$endpointId];
                $requiredEndpoints = array_unique(array_merge(
                    $catalog['exposure_dependencies'][$selection['exposure']] ?? [],
                    $catalog['endpoint_dependencies'][$endpointId] ?? [],
                ));

                foreach ($requiredEndpoints as $requiredEndpointId) {
                    if (isset($endpointDefinitions[$requiredEndpointId]) === false) {
                        throw new RuntimeException("Unknown configured dependency: $requiredEndpointId");
                    }

                    if (isset($selected[$requiredEndpointId]) === false) {
                        $selected[$requiredEndpointId] = [
                            'exposure' => $endpointDefinitions[$requiredEndpointId]['default_exposure'],
                        ];
                        $autoAdded[$requiredEndpointId] = [
                            'id' => $requiredEndpointId,
                            'required_by' => [$endpointId],
                        ];
                        $changed = true;

                        continue;
                    }

                    if (isset($autoAdded[$requiredEndpointId]) && in_array($endpointId, $autoAdded[$requiredEndpointId]['required_by'], true) === false) {
                        $autoAdded[$requiredEndpointId]['required_by'][] = $endpointId;
                    }
                }
            }
        }

        $this->validateGovernanceAgainstSurface($governance, $selected, $errors);
        $governanceAdjustments = [];

        if (isset($selected['audit.list']) && $governance['audit'] === false) {
            $governance['audit'] = true;
            $governanceAdjustments[] = [
                'capability' => 'audit',
                'reason' => 'El endpoint de auditoría requiere habilitar la capacidad de auditoría.',
            ];
        }

        if ($errors !== []) {
            throw new InvalidBlueprintManifest($errors);
        }

        $resolvedEndpoints = [];
        foreach ($catalog['endpoints'] as $endpoint) {
            if (isset($selected[$endpoint['id']]) === false) {

                continue;
            }

            $resolvedEndpoints[] = [
                'id' => $endpoint['id'],
                'capability' => $endpoint['capability'],
                'capability_label' => $endpoint['capability_label'],
                'summary' => $endpoint['summary'],
                'method' => $endpoint['method'],
                'path' => $endpoint['path'],
                'exposure' => $selected[$endpoint['id']]['exposure'],
                'auto_added' => isset($autoAdded[$endpoint['id']]),
            ];
        }

        $messages = ['La configuración es válida.'];
        if ($autoAdded !== []) {
            $messages[] = 'ApiBlueprint añadió las dependencias de endpoints obligatorias antes de exportar.';
        }
        if ($governanceAdjustments !== []) {
            $messages[] = 'ApiBlueprint ajustó capacidades transversales requeridas por la superficie seleccionada.';
        }

        return [
            'schema_version' => $catalog['schema_version'],
            'generator' => 'ApiBlueprint',
            'project' => [
                'name' => $projectName,
                'api_version' => $catalog['api_version'],
            ],
            'template' => $template,
            'governance' => $governance,
            'endpoints' => $resolvedEndpoints,
            'resolution' => [
                'auto_added' => array_values($autoAdded),
                'governance_adjustments' => $governanceAdjustments,
                'messages' => $messages,
            ],
        ];
    }

    private function resolveGovernance(mixed $submitted, array $catalog, array &$errors): array
    {
        $defaults = $catalog['governance']['defaults'];
        $submitted = is_array($submitted) ? $submitted : [];

        $authentication = (string) ($submitted['authentication'] ?? $defaults['authentication']);
        $authenticationStrategies = array_column($catalog['governance']['authentication_strategies'], 'id');
        if (in_array($authentication, $authenticationStrategies, true) === false) {
            $errors['governance.authentication'][] = 'La estrategia de autenticación indicada no está soportada.';
        }

        $rbac = $this->booleanValue($submitted, 'rbac', $defaults['rbac'], $errors);
        $correlationId = $this->booleanValue($submitted, 'correlation_id', $defaults['correlation_id'], $errors);
        $filtering = $this->booleanValue($submitted, 'filtering', $defaults['filtering'], $errors);
        $sorting = $this->booleanValue($submitted, 'sorting', $defaults['sorting'], $errors);
        $idempotency = $this->booleanValue($submitted, 'idempotency', $defaults['idempotency'], $errors);
        $audit = $this->booleanValue($submitted, 'audit', $defaults['audit'], $errors);

        $rateLimiting = is_array($submitted['rate_limiting'] ?? null) ? $submitted['rate_limiting'] : [];
        $rateLimitingEnabled = $this->booleanValue($rateLimiting, 'enabled', $defaults['rate_limiting']['enabled'], $errors, 'governance.rate_limiting.enabled');
        $requestsPerMinute = $this->integerValue(
            $rateLimiting,
            'requests_per_minute',
            $defaults['rate_limiting']['requests_per_minute'],
            1,
            1000,
            $errors,
            'governance.rate_limiting.requests_per_minute',
        );

        $pagination = is_array($submitted['pagination'] ?? null) ? $submitted['pagination'] : [];
        $paginationStrategy = (string) ($pagination['strategy'] ?? $defaults['pagination']['strategy']);
        $paginationStrategies = array_column($catalog['governance']['pagination_strategies'], 'id');
        if (in_array($paginationStrategy, $paginationStrategies, true) === false) {
            $errors['governance.pagination.strategy'][] = 'La estrategia de paginación indicada no está soportada.';
        }

        $defaultSize = $this->integerValue(
            $pagination,
            'default_size',
            $defaults['pagination']['default_size'],
            1,
            500,
            $errors,
            'governance.pagination.default_size',
        );
        $maxSize = $this->integerValue(
            $pagination,
            'max_size',
            $defaults['pagination']['max_size'],
            1,
            500,
            $errors,
            'governance.pagination.max_size',
        );

        if ($defaultSize > $maxSize) {
            $errors['governance.pagination.default_size'][] = 'El tamaño de página predeterminado no puede superar el máximo.';
        }

        return [
            'authentication' => $authentication,
            'rbac' => $rbac,
            'correlation_id' => $correlationId,
            'rate_limiting' => [
                'enabled' => $rateLimitingEnabled,
                'requests_per_minute' => $requestsPerMinute,
            ],
            'pagination' => [
                'strategy' => $paginationStrategy,
                'default_size' => $defaultSize,
                'max_size' => $maxSize,
            ],
            'filtering' => $filtering,
            'sorting' => $sorting,
            'idempotency' => $idempotency,
            'audit' => $audit,
        ];
    }

    private function validateGovernanceAgainstSurface(array $governance, array $selected, array &$errors): void
    {
        $protectedEndpointExists = false;
        $privilegedEndpointExists = false;

        foreach ($selected as $selection) {
            if ($selection['exposure'] !== 'public') {
                $protectedEndpointExists = true;
            }

            if (in_array($selection['exposure'], ['admin', 'internal'], true)) {
                $privilegedEndpointExists = true;
            }
        }

        if ($protectedEndpointExists && $governance['authentication'] === 'none') {
            $errors['governance.authentication'][] = 'La superficie seleccionada contiene endpoints protegidos y requiere una estrategia de autenticación.';
        }

        if ($privilegedEndpointExists && $governance['rbac'] === false) {
            $errors['governance.rbac'][] = 'Los endpoints Administrador o Interno requieren RBAC habilitado.';
        }
    }

    private function booleanValue(
        array $source,
        string $key,
        bool $default,
        array &$errors,
        ?string $errorKey = null,
    ): bool {
        if (array_key_exists($key, $source) === false) {
            return $default;
        }

        if (is_bool($source[$key]) === false) {
            $errors[$errorKey ?? "governance.$key"][] = 'El valor debe ser booleano.';

            return $default;
        }

        return $source[$key];
    }

    private function integerValue(
        array $source,
        string $key,
        int $default,
        int $minimum,
        int $maximum,
        array &$errors,
        string $errorKey,
    ): int {
        if (array_key_exists($key, $source) === false) {
            return $default;
        }

        if (is_int($source[$key]) === false || $source[$key] < $minimum || $source[$key] > $maximum) {
            $errors[$errorKey][] = "El valor debe ser un entero entre $minimum y $maximum.";

            return $default;
        }

        return $source[$key];
    }
}
