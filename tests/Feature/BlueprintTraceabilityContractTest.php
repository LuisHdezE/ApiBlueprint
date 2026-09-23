<?php

namespace Tests\Feature;

use Tests\TestCase;

final class BlueprintTraceabilityContractTest extends TestCase
{
    public function test_requirements_catalog_has_valid_internal_references(): void
    {
        $catalog = $this->getJson('/api/v1/blueprint/catalog')->assertOk()->json();

        $actorIds = array_column($catalog['actors'], 'id');
        $requirementIds = array_column($catalog['requirements'], 'id');
        $businessRuleIds = array_column($catalog['business_rules'], 'id');
        $acceptanceCriteriaIds = array_column($catalog['acceptance_criteria'], 'id');

        $this->assertSameSize(array_unique($actorIds), $actorIds);
        $this->assertSameSize(array_unique($requirementIds), $requirementIds);
        $this->assertSameSize(array_unique($businessRuleIds), $businessRuleIds);
        $this->assertSameSize(array_unique($acceptanceCriteriaIds), $acceptanceCriteriaIds);
        $this->assertContains('functional', array_column($catalog['requirements'], 'type'));
        $this->assertContains('non_functional', array_column($catalog['requirements'], 'type'));

        foreach ($catalog['requirements'] as $requirement) {
            foreach ($requirement['actor_ids'] as $actorId) {
                $this->assertContains($actorId, $actorIds, "Requirement {$requirement['id']} references unknown actor {$actorId}.");
            }
        }

        foreach ($catalog['acceptance_criteria'] as $criterion) {
            $this->assertNotEmpty($criterion['requirement_ids'], "Acceptance criterion {$criterion['id']} must reference at least one requirement.");
            foreach ($criterion['requirement_ids'] as $requirementId) {
                $this->assertContains($requirementId, $requirementIds, "Acceptance criterion {$criterion['id']} references unknown requirement {$requirementId}.");
            }
        }

        foreach ($catalog['use_cases'] as $useCase) {
            foreach ($useCase['actor_ids'] as $actorId) {
                $this->assertContains($actorId, $actorIds, "Use case {$useCase['id']} references unknown actor {$actorId}.");
            }
            foreach ($useCase['requirement_ids'] as $requirementId) {
                $this->assertContains($requirementId, $requirementIds, "Use case {$useCase['id']} references unknown requirement {$requirementId}.");
            }
            foreach ($useCase['business_rule_ids'] as $businessRuleId) {
                $this->assertContains($businessRuleId, $businessRuleIds, "Use case {$useCase['id']} references unknown business rule {$businessRuleId}.");
            }
            foreach ($useCase['acceptance_criteria_ids'] as $criterionId) {
                $this->assertContains($criterionId, $acceptanceCriteriaIds, "Use case {$useCase['id']} references unknown acceptance criterion {$criterionId}.");
            }
        }
    }

    public function test_every_implemented_feature_has_complete_traceability(): void
    {
        $catalog = $this->getJson('/api/v1/blueprint/catalog')->assertOk()->json();

        $requirementIds = array_column($catalog['requirements'], 'id');
        $useCaseIds = array_column($catalog['use_cases'], 'id');
        $acceptanceCriteriaIds = array_column($catalog['acceptance_criteria'], 'id');
        $businessRuleIds = array_column($catalog['business_rules'], 'id');
        $permissionPolicyIds = array_column($catalog['permission_policies'], 'id');
        $idempotencyPolicyIds = array_column($catalog['idempotency_policies'], 'id');
        $auditEventIds = array_column($catalog['audit_events'], 'id');
        $traceability = collect($catalog['traceability'])->keyBy('feature_id');

        $implemented = collect($catalog['features'])
            ->where('implementation_status', 'implemented')
            ->values();

        $this->assertCount($implemented->count(), $catalog['traceability']);

        foreach ($implemented as $feature) {
            $this->assertSame('complete', $feature['traceability_status'], "Feature {$feature['id']} is implemented but traceability is incomplete.");
            $this->assertNotEmpty($feature['requirement_ids']);
            $this->assertNotEmpty($feature['use_case_ids']);
            $this->assertNotEmpty($feature['acceptance_criteria_ids']);
            $this->assertNotEmpty($feature['audit_events']);
            $this->assertNotEmpty($feature['test_evidence']);
            $this->assertContains($feature['permission_policy'], $permissionPolicyIds);
            $this->assertContains($feature['idempotency_policy'], $idempotencyPolicyIds);

            foreach ($feature['requirement_ids'] as $requirementId) {
                $this->assertContains($requirementId, $requirementIds, "Feature {$feature['id']} references unknown requirement {$requirementId}.");
            }
            foreach ($feature['use_case_ids'] as $useCaseId) {
                $this->assertContains($useCaseId, $useCaseIds, "Feature {$feature['id']} references unknown use case {$useCaseId}.");
            }
            foreach ($feature['acceptance_criteria_ids'] as $criterionId) {
                $this->assertContains($criterionId, $acceptanceCriteriaIds, "Feature {$feature['id']} references unknown acceptance criterion {$criterionId}.");
            }
            foreach ($feature['business_rule_ids'] as $businessRuleId) {
                $this->assertContains($businessRuleId, $businessRuleIds, "Feature {$feature['id']} references unknown business rule {$businessRuleId}.");
            }
            foreach ($feature['audit_events'] as $eventId) {
                $this->assertContains($eventId, $auditEventIds, "Feature {$feature['id']} references unknown audit event {$eventId}.");
            }
            foreach ($feature['test_evidence'] as $testPath) {
                $this->assertFileExists(base_path($testPath), "Feature {$feature['id']} references missing test evidence {$testPath}.");
            }

            $row = $traceability->get($feature['id']);
            $this->assertIsArray($row, "Feature {$feature['id']} is missing from the traceability projection.");
            $this->assertSame(str_replace('.', '_', $feature['id']), $row['operation_id']);
            $this->assertSame($feature['requirement_ids'], $row['requirement_ids']);
            $this->assertSame($feature['permission_policy'], $row['permission_policy']);
            $this->assertSame($feature['idempotency_policy'], $row['idempotency_policy']);
        }
    }

    public function test_permission_and_idempotency_policies_match_runtime_contract(): void
    {
        $catalog = $this->getJson('/api/v1/blueprint/catalog')->assertOk()->json();
        $expectedPermissionByExposure = [
            'public' => 'public',
            'authenticated' => 'authenticated',
            'admin' => 'admin-api',
            'internal' => 'internal-api',
        ];

        foreach ($catalog['features'] as $feature) {
            if ($feature['implementation_status'] !== 'implemented') {
                continue;
            }

            $this->assertSame(
                $expectedPermissionByExposure[$feature['default_exposure']],
                $feature['permission_policy'],
                "Feature {$feature['id']} permission policy drifted from its canonical exposure.",
            );

            $expectedIdempotency = match (true) {
                in_array($feature['id'], ['auth.login', 'auth.logout'], true) => 'excluded',
                in_array($feature['method'], ['POST', 'PUT', 'PATCH'], true) => 'required_when_enabled',
                default => 'not_applicable',
            };

            $this->assertSame(
                $expectedIdempotency,
                $feature['idempotency_policy'],
                "Feature {$feature['id']} idempotency policy drifted from generated runtime behavior.",
            );
        }
    }

    public function test_master_openapi_projects_feature_traceability(): void
    {
        $document = $this->getJson('/api/v1/blueprint/openapi')
            ->assertOk()
            ->assertJsonPath('x-apiblueprint-catalog-version', '0.2')
            ->json();

        $operation = $document['paths']['/api/v1/users']['post'];

        $this->assertSame('users.create', $operation['x-apiblueprint-feature-id']);
        $this->assertContains('REQ-USERS-003', $operation['x-apiblueprint-requirements']);
        $this->assertSame(['UC-USERS-CREATE'], $operation['x-apiblueprint-use-cases']);
        $this->assertSame('admin-api', $operation['x-apiblueprint-permission-policy']);
        $this->assertSame('required_when_enabled', $operation['x-apiblueprint-idempotency-policy']);
        $this->assertSame(['audit.users.create'], $operation['x-apiblueprint-audit-events']);
        $this->assertContains('tests/Feature/GeneratedUsersCreateVerticalSliceExportTest.php', $operation['x-apiblueprint-test-evidence']);
    }
}
