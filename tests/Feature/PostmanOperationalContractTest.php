<?php

namespace Tests\Feature;

use App\Application\Blueprint\Queries\GetMasterOpenApi;
use Tests\TestCase;

final class PostmanOperationalContractTest extends TestCase
{
    public function test_collection_covers_each_master_operation_id_exactly_once(): void
    {
        $collection = $this->jsonFile(base_path('postman/ApiBlueprint.generated.postman_collection.json'));
        $coverage = $this->jsonFile(base_path('postman/operation-coverage.json'));
        $openApi = app(GetMasterOpenApi::class)->handle();

        $openApiOperations = [];
        foreach ($openApi['paths'] as $path => $methods) {
            foreach ($methods as $method => $operation) {
                $openApiOperations[$operation['operationId']] = [
                    'method' => strtoupper($method),
                    'path' => $path,
                ];
            }
        }

        $coverageOperations = [];
        foreach ($coverage['operations'] as $operation) {
            $this->assertArrayNotHasKey($operation['operation_id'], $coverageOperations, 'La cobertura no puede duplicar operationId.');
            $coverageOperations[$operation['operation_id']] = $operation;
        }

        $expectedOperationIds = array_keys($openApiOperations);
        $coveredOperationIds = array_keys($coverageOperations);
        sort($expectedOperationIds);
        sort($coveredOperationIds);
        $this->assertSame($expectedOperationIds, $coveredOperationIds);

        $requests = $this->requests($collection['item'] ?? []);
        foreach ($coverageOperations as $operationId => $mapping) {
            $matchingRequests = array_values(array_filter(
                $requests,
                static fn (array $request): bool => $request['name'] === $mapping['request_name'],
            ));

            $this->assertCount(1, $matchingRequests, "Cada operationId debe mapear a un único request Postman: $operationId");
            $request = $matchingRequests[0]['request'];
            $this->assertSame($openApiOperations[$operationId]['method'], strtoupper((string) $request['method']));
            $this->assertSame($openApiOperations[$operationId]['method'], $mapping['method']);
            $this->assertSame($openApiOperations[$operationId]['path'], $mapping['path']);
            $this->assertMatchesRegularExpression(
                $this->postmanUrlPattern($mapping['path']),
                (string) $request['url'],
                "El request Postman de $operationId debe conservar el path OpenAPI.",
            );
        }
    }

    public function test_environment_is_repository_owned_and_contains_no_runtime_credentials(): void
    {
        $environment = $this->jsonFile(base_path('postman/ApiBlueprint.local.postman_environment.json'));
        $values = [];
        foreach ($environment['values'] as $variable) {
            $values[$variable['key']] = (string) ($variable['value'] ?? '');
        }

        $this->assertSame('http://127.0.0.1:18081', $values['baseUrl'] ?? null);
        $this->assertSame('', $values['authToken'] ?? null);
        $this->assertSame('', $values['userToken'] ?? null);
        $this->assertStringStartsWith('PostmanSynthetic!', $values['adminPassword'] ?? '');
        $this->assertStringStartsWith('PostmanSynthetic!', $values['userPassword'] ?? '');
        $this->assertStringStartsWith('PostmanSynthetic!', $values['createdUserPassword'] ?? '');
        $this->assertStringEndsWith('@example.test', $values['adminEmail'] ?? '');
        $this->assertStringEndsWith('@example.test', $values['userEmail'] ?? '');
        $this->assertStringEndsWith('@example.test', $values['createdUserEmail'] ?? '');
    }

    public function test_operational_collection_includes_cross_cutting_negative_and_idempotency_scenarios(): void
    {
        $collection = $this->jsonFile(base_path('postman/ApiBlueprint.generated.postman_collection.json'));
        $requestNames = array_column($this->requests($collection['item'] ?? []), 'name');

        foreach ([
            'negative_auth_login_invalid',
            'negative_users_list_unauthenticated',
            'negative_users_list_forbidden',
            'negative_users_create_missing_idempotency',
            'negative_users_create_validation',
            'negative_products_show_missing',
            'idempotency_replay_users_create',
        ] as $requiredScenario) {
            $this->assertContains($requiredScenario, $requestNames);
        }
    }

    private function jsonFile(string $path): array
    {
        $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($decoded);

        return $decoded;
    }

    /** @return list<array{name: string, request: array}> */
    private function requests(array $items): array
    {
        $requests = [];
        foreach ($items as $item) {
            if (is_array($item['request'] ?? null)) {
                $requests[] = [
                    'name' => (string) ($item['name'] ?? ''),
                    'request' => $item['request'],
                ];
            }

            if (is_array($item['item'] ?? null)) {
                array_push($requests, ...$this->requests($item['item']));
            }
        }

        return $requests;
    }

    private function postmanUrlPattern(string $path): string
    {
        $segments = array_map(
            static function (string $segment): string {
                if (preg_match('/^\{[^}]+\}$/', $segment) === 1) {
                    return '\\{\\{[^}]+\\}\\}';
                }

                return preg_quote($segment, '#');
            },
            explode('/', trim($path, '/')),
        );

        return '#^\\{\\{baseUrl\\}\\}/'.implode('/', $segments).'(?:\\?.*)?$#';
    }
}
