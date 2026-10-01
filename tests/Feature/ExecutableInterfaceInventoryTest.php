<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

final class ExecutableInterfaceInventoryTest extends TestCase
{
    public function test_root_openapi_operations_bind_to_real_routes_and_inventory_dependencies(): void
    {
        $contract = $this->jsonFile('.blueprint/root-api-openapi.json');
        $inventory = $this->jsonFile('.blueprint/ui/interface-inventory.json');
        $requirements = $this->jsonFile('.blueprint/ui/interface-requirements.json');
        $baseline = $this->jsonFile('.blueprint/ui/interface-scope-baseline.json');

        $this->assertSame('3.1.0', $contract['openapi']);
        $this->assertSame('EXECUTABLE_INVENTORY', $inventory['maturity']);
        $this->assertSame($baseline['baseline_revision'], $inventory['reconciled_from']);
        $this->assertSame('PASS', data_get($this->readStatus(), 'gates.api_gate.status'));

        $operations = [];
        foreach ($contract['paths'] as $path => $methods) {
            foreach ($methods as $method => $definition) {
                $operationId = $definition['operationId'];
                $this->assertArrayNotHasKey($operationId, $operations);
                $operations[$operationId] = strtoupper($method).' '.$path;
                $route = app('router')->getRoutes()->match(
                    Request::create($path, strtoupper($method))
                );
                $this->assertContains(strtoupper($method), $route->methods());
                $this->assertSame(ltrim($path, '/'), $route->uri());
            }
        }

        $this->assertSame([
            'root_status_show' => 'GET /api/v1/meta/status',
            'root_blueprint_catalog_show' => 'GET /api/v1/blueprint/catalog',
            'root_blueprint_openapi_show' => 'GET /api/v1/blueprint/openapi',
            'root_blueprint_manifest_resolve' => 'POST /api/v1/blueprint/resolve',
            'root_blueprint_solution_export' => 'POST /api/v1/blueprint/export',
        ], $operations);

        $requirementById = collect($requirements['requirements'])->keyBy('id');
        $baselineById = collect($baseline['items'])->keyBy('id');
        $inventoryById = collect($inventory['items'])->keyBy('id');
        $this->assertSame($baselineById->keys()->sort()->values()->all(), $inventoryById->keys()->sort()->values()->all());
        $this->assertSame(['WEB-001', 'WEB-002', 'WEB-003'], array_column($inventory['baseline_reconciliation'], 'baseline_id'));

        $viewById = ['WEB-001' => 'welcome', 'WEB-002' => 'catalog', 'WEB-003' => 'swagger'];
        foreach ($inventory['items'] as $item) {
            $original = $baselineById->get($item['id']);
            $this->assertSame($original['navigation']['route'], $item['navigation']['route']);
            $this->assertSame('OBSERVED', $item['source_classification']);
            $this->assertSame('EXISTING', $item['implementation_status']);
            $this->assertSame(['anonymous'], $item['roles']);
            $this->assertSame([], $item['permissions']);
            $this->assertSame([], $item['unresolved_api_needs']);
            $this->assertNotEmpty($item['slice_id']);
            $this->assertGreaterThan(0, $item['priority']);
            $this->assertTrue(View::exists($viewById[$item['id']]));

            foreach ($item['requirements'] as $id) {
                $this->assertSame($item['id'], $requirementById->get($id)['interface_id'] ?? null);
            }

            $bound = [];
            foreach (array_merge($item['data'], $item['actions']) as $edge) {
                if (($edge['source'] ?? null) === 'api') {
                    $this->assertNotEmpty($edge['operation_ids'] ?? []);
                }
                foreach ($edge['operation_ids'] ?? [] as $operationId) {
                    $this->assertArrayHasKey($operationId, $operations);
                    $bound[] = $operations[$operationId];
                }
            }

            $view = file_get_contents(resource_path('views/'.$viewById[$item['id']].'.blade.php'));
            $this->assertIsString($view);
            preg_match_all('~(?:fetch\(|url:\s*)[\x27\x22](/api/v1/[^\x27\x22]+)[\x27\x22]~', $view, $matches);
            foreach ($matches[1] as $path) {
                $this->assertContains($path, array_map(
                    static fn (string $binding): string => explode(' ', $binding, 2)[1],
                    $bound
                ), $item['id'].' has an unbound API call '.$path);
            }
        }

        $this->assertSame(['COMMITTED', 'COMMITTED', 'COMMITTED'], array_column($inventory['baseline_reconciliation'], 'disposition'));
    }

    private function jsonFile(string $path): array
    {
        $contents = file_get_contents(base_path($path));
        $this->assertIsString($contents);

        return json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    }

    private function readStatus(): array
    {
        return Yaml::parseFile(base_path('.blueprint/status.yaml'));
    }
}
