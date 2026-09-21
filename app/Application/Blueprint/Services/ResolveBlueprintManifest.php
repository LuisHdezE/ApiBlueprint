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

        $messages = $autoAdded === []
            ? ['La configuración es válida y no requiere dependencias adicionales.']
            : ['La configuración es válida. ApiBlueprint añadió las dependencias obligatorias antes de exportar.'];

        return [
            'schema_version' => $catalog['schema_version'],
            'generator' => 'ApiBlueprint',
            'project' => [
                'name' => $projectName,
                'api_version' => $catalog['api_version'],
            ],
            'template' => $template,
            'endpoints' => $resolvedEndpoints,
            'resolution' => [
                'auto_added' => array_values($autoAdded),
                'messages' => $messages,
            ],
        ];
    }
}
