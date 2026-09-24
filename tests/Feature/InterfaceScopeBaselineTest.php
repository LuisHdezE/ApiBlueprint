<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\View;
use Tests\TestCase;

final class InterfaceScopeBaselineTest extends TestCase
{
    public function test_brownfield_scope_baseline_matches_the_existing_web_surfaces(): void
    {
        $baseline = $this->baseline();

        $this->assertSame('0.5.4', $baseline['schema_version']);
        $this->assertSame('LuisHdezE/ApiBlueprint', $baseline['project']);
        $this->assertSame('brownfield', $baseline['mode']);
        $this->assertSame('SCOPE_BASELINE', $baseline['maturity']);

        $items = collect($baseline['items'])->keyBy('id');
        $this->assertSame(['WEB-001', 'WEB-002', 'WEB-003'], $items->keys()->sort()->values()->all());

        $expected = [
            'WEB-001' => ['route_name' => 'landing', 'path' => '/', 'view' => 'welcome'],
            'WEB-002' => ['route_name' => 'catalog.admin', 'path' => '/catalogo', 'view' => 'catalog'],
            'WEB-003' => ['route_name' => 'swagger', 'path' => '/swagger', 'view' => 'swagger'],
        ];

        foreach ($expected as $id => $surface) {
            $item = $items->get($id);
            $this->assertIsArray($item);
            $this->assertSame('web', $item['platform']);
            $this->assertSame('OBSERVED', $item['source_classification']);
            $this->assertSame('EXISTING', $item['implementation_status']);
            $this->assertSame([], $item['requirements']);
            $this->assertSame([], $item['permissions']);
            $this->assertSame($surface['path'], $item['navigation']['route']);
            $this->assertNotEmpty($item['unresolved_api_needs']);
            $this->assertNotNull(app('router')->getRoutes()->getByName($surface['route_name']));
            $this->assertTrue(View::exists($surface['view']));

            $this->get($surface['path'])->assertOk();
        }
    }

    public function test_scope_baseline_does_not_pretend_to_be_executable_inventory(): void
    {
        $baseline = $this->baseline();
        $serialized = json_encode($baseline, JSON_THROW_ON_ERROR);

        $this->assertStringNotContainsString('operation_ids', $serialized);
        $this->assertArrayNotHasKey('reconciled_from', $baseline);
        $this->assertArrayNotHasKey('baseline_reconciliation', $baseline);

        foreach ($baseline['items'] as $item) {
            $this->assertArrayNotHasKey('slice_id', $item);
        }
    }

    private function baseline(): array
    {
        $content = file_get_contents(base_path('.blueprint/ui/interface-scope-baseline.json'));
        $this->assertIsString($content);

        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }
}
