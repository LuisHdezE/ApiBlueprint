<?php

namespace App\Application\Blueprint\Queries;

use App\Application\Blueprint\Contracts\BlueprintCatalog;

final readonly class GetMasterOpenApi
{
    public function __construct(private BlueprintCatalog $catalog)
    {
        //
    }

    public function handle(): array
    {
        $catalog = $this->catalog->get();
        $implemented = array_values(array_filter(
            $catalog['features'],
            static fn (array $feature): bool => ($feature['implementation_status'] ?? null) === 'implemented'
                && ($feature['openapi_ready'] ?? false) === true,
        ));

        $paths = [];
        $tags = [];

        foreach ($implemented as $feature) {
            $method = strtolower($feature['method']);
            $path = $feature['path'];
            $tags[$feature['capability']] = [
                'name' => $feature['capability_label'],
                'description' => sprintf('Features implementadas del módulo %s.', $feature['capability_label']),
            ];

            $operation = [
                'tags' => [$feature['capability_label']],
                'summary' => $feature['summary'],
                'operationId' => str_replace('.', '_', $feature['id']),
                'x-apiblueprint-feature-id' => $feature['id'],
                'x-apiblueprint-status' => $feature['implementation_status'],
                'x-apiblueprint-applications' => $feature['applications'],
                'parameters' => $this->parametersFor($feature, $catalog),
                'responses' => $this->responsesFor($feature),
            ];

            if ($feature['id'] === 'auth.login') {
                $operation['requestBody'] = [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => ['$ref' => '#/components/schemas/AuthLoginRequest'],
                        ],
                    ],
                ];
            }

            if ($feature['default_exposure'] !== 'public') {
                $operation['security'] = [['bearerAuth' => []]];
            }

            $paths[$path][$method] = $operation;
        }

        ksort($paths);

        return [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'ApiBlueprint · Master Feature Library',
                'version' => (string) ($catalog['catalog_version'] ?? '0.1'),
                'description' => 'Contrato vivo de las features implementadas y disponibles en la biblioteca maestra de ApiBlueprint.',
            ],
            'servers' => [[
                'url' => '{generatedApiBaseUrl}',
                'description' => 'URL de una solución generada por ApiBlueprint.',
                'variables' => [
                    'generatedApiBaseUrl' => [
                        'default' => 'http://localhost:8000',
                        'description' => 'Sustituye este valor por la URL de la API generada que quieras probar.',
                    ],
                ],
            ]],
            'tags' => array_values($tags),
            'paths' => $paths,
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'Sanctum personal access token',
                    ],
                ],
                'schemas' => [
                    'AuthLoginRequest' => [
                        'type' => 'object',
                        'required' => ['email', 'password'],
                        'properties' => [
                            'email' => ['type' => 'string', 'format' => 'email'],
                            'password' => ['type' => 'string', 'format' => 'password'],
                            'device_name' => ['type' => 'string', 'maxLength' => 100],
                        ],
                    ],
                    'AuthLoginResponse' => [
                        'type' => 'object',
                        'required' => ['data'],
                        'properties' => [
                            'data' => [
                                'type' => 'object',
                                'required' => ['user', 'access_token', 'token_type'],
                                'properties' => [
                                    'user' => [
                                        'type' => 'object',
                                        'required' => ['id', 'name', 'email'],
                                        'properties' => [
                                            'id' => ['type' => 'string'],
                                            'name' => ['type' => 'string'],
                                            'email' => ['type' => 'string', 'format' => 'email'],
                                        ],
                                    ],
                                    'access_token' => ['type' => 'string'],
                                    'token_type' => ['type' => 'string', 'enum' => ['Bearer']],
                                ],
                            ],
                        ],
                    ],
                    'ProblemDetails' => [
                        'type' => 'object',
                        'required' => ['type', 'title', 'status'],
                        'properties' => [
                            'type' => ['type' => 'string'],
                            'title' => ['type' => 'string'],
                            'status' => ['type' => 'integer'],
                            'detail' => ['type' => 'string'],
                            'instance' => ['type' => 'string'],
                            'correlation_id' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function parametersFor(array $feature, array $catalog): array
    {
        $parameters = [];

        if (preg_match_all('/\{([^}]+)\}/', $feature['path'], $matches)) {
            foreach ($matches[1] as $name) {
                $parameters[] = [
                    'name' => $name,
                    'in' => 'path',
                    'required' => true,
                    'schema' => ['type' => 'string'],
                ];
            }
        }

        if ($feature['id'] === 'auth.logout') {
            return [
                '204' => ['description' => 'Sesión cerrada correctamente.'],
                '401' => [
                    'description' => 'Token de acceso ausente o inválido.',
                    'content' => ['application/problem+json' => ['schema' => ['$ref' => '#/components/schemas/ProblemDetails']]],
                ],
            ];
        }

        if (str_ends_with($feature['id'], '.list')) {
            $parameters[] = [
                'name' => 'page[size]',
                'in' => 'query',
                'required' => false,
                'schema' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => $catalog['governance']['defaults']['pagination']['max_size'],
                    'default' => $catalog['governance']['defaults']['pagination']['default_size'],
                ],
            ];
            $parameters[] = ['name' => 'page[cursor]', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string']];
            $parameters[] = ['name' => 'page[number]', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'integer', 'minimum' => 1]];

            if ($catalog['governance']['defaults']['filtering']) {
                $parameters[] = ['name' => 'filter[id]', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string']];
                $parameters[] = ['name' => 'filter[name]', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string']];
            }

            if ($catalog['governance']['defaults']['sorting']) {
                $parameters[] = ['name' => 'sort', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string'], 'example' => 'name,-id'];
            }
        }

        return $parameters;
    }

    private function responsesFor(array $feature): array
    {
        if ($feature['id'] === 'auth.login') {
            return [
                '200' => [
                    'description' => 'Sesión iniciada correctamente.',
                    'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/AuthLoginResponse']]],
                ],
                '401' => [
                    'description' => 'Credenciales inválidas.',
                    'content' => ['application/problem+json' => ['schema' => ['$ref' => '#/components/schemas/ProblemDetails']]],
                ],
                '422' => [
                    'description' => 'Datos de inicio de sesión inválidos.',
                    'content' => ['application/problem+json' => ['schema' => ['$ref' => '#/components/schemas/ProblemDetails']]],
                ],
            ];
        }

        if (str_ends_with($feature['id'], '.list')) {
            return [
                '200' => ['description' => 'Listado obtenido correctamente.'],
                '422' => [
                    'description' => 'Parámetros de consulta inválidos.',
                    'content' => ['application/problem+json' => ['schema' => ['$ref' => '#/components/schemas/ProblemDetails']]],
                ],
            ];
        }

        if (str_ends_with($feature['id'], '.show')) {
            return [
                '200' => ['description' => 'Recurso obtenido correctamente.'],
                '404' => [
                    'description' => 'Recurso no encontrado.',
                    'content' => ['application/problem+json' => ['schema' => ['$ref' => '#/components/schemas/ProblemDetails']]],
                ],
            ];
        }

        return ['200' => ['description' => 'Operación completada correctamente.']];
    }
}
