<?php

namespace App\Application\Blueprint\Queries;

use App\Application\Blueprint\Contracts\BlueprintCatalog;

final readonly class GetTraceableMasterOpenApi
{
    public function __construct(
        private GetMasterOpenApi $masterOpenApi,
        private BlueprintCatalog $catalog,
    ) {
        //
    }

    public function handle(): array
    {
        $document = $this->masterOpenApi->handle();
        $catalog = $this->catalog->get();
        $features = [];

        foreach ($catalog['features'] as $feature) {
            $features[$feature['id']] = $feature;
        }

        foreach ($document['paths'] as &$operations) {
            foreach ($operations as &$operation) {
                $featureId = $operation['x-apiblueprint-feature-id'] ?? null;
                if (is_string($featureId) === false || isset($features[$featureId]) === false) {
                    continue;
                }

                $feature = $features[$featureId];
                $operation['x-apiblueprint-requirements'] = $feature['requirement_ids'];
                $operation['x-apiblueprint-use-cases'] = $feature['use_case_ids'];
                $operation['x-apiblueprint-acceptance-criteria'] = $feature['acceptance_criteria_ids'];
                $operation['x-apiblueprint-business-rules'] = $feature['business_rule_ids'];
                $operation['x-apiblueprint-permission-policy'] = $feature['permission_policy'];
                $operation['x-apiblueprint-idempotency-policy'] = $feature['idempotency_policy'];
                $operation['x-apiblueprint-audit-events'] = $feature['audit_events'];
                $operation['x-apiblueprint-test-evidence'] = $feature['test_evidence'];
            }
            unset($operation);
        }
        unset($operations);

        $document['x-apiblueprint-catalog-version'] = $catalog['catalog_version'];

        return $document;
    }
}
