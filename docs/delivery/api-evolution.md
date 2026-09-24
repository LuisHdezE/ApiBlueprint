# BP-ADOPT-005 · API evolution and compatibility

## Purpose

This checkpoint freezes the current consumer-facing API contract and makes future contract drift explicit, reviewable and proportional to its actual impact. It implements the API-evolution evidence expected by SoftwareDevelopmentBlueprint 0.5.4 without inventing a change that has not happened.

## Current contract baseline

The authoritative baseline is `.blueprint/api-contract-baseline.json`.

- API path version: `v1`.
- Baseline source revision: `07297c9438d0efd3421e197fd8e78f4302c12c42`.
- Baseline contract revision: `b75a0d933a66497ff378f02e74500443ec52ddcfe4fea6039b9defe0b9e7dc87`.
- Implemented baseline operations: 7.
- Each operation is identified by its canonical OpenAPI `operationId`.

The contract revision is deterministic. It hashes the API version plus the sorted per-operation fingerprints, rather than a Git commit SHA. Repository-only changes therefore do not manufacture API revisions.

## What is fingerprinted

`scripts/api-evolution.php` derives the snapshot from `GetTraceableMasterOpenApi` and fingerprints consumer-visible contract only:

- HTTP method;
- path;
- parameters;
- request body;
- responses;
- operation security;
- schemas transitively referenced by that operation;
- security schemes when the operation is protected.

Descriptions, tags and `x-apiblueprint-*` traceability metadata are deliberately excluded from the fingerprint. A documentation or evidence-only edit must not look like consumer contract drift.

A shared component change affects only operations whose consumer contract actually references that component. Renaming an `operationId` appears as one removed operation plus one added operation and therefore cannot drift silently.

## `/api/v1` compatibility policy

`/api/v1` is the current public major contract line. `operationId` is a stable downstream linkage across OpenAPI, Postman, tests, evidence and future client slices.

A change may remain within `v1` only when existing consumers can continue using the previously accepted contract. Additive behavior can be compatible when it does not invalidate existing requests, responses, permissions or error handling.

The following are treated as potentially breaking and require explicit contract review. When compatibility cannot be preserved through an approved migration/deprecation window, they require a new major path such as `/api/v2`:

- removing or renaming an existing `operationId`;
- changing an existing operation method or path incompatibly;
- adding a new required request field or tightening an accepted request incompatibly;
- removing or incompatibly changing response fields/schemas relied upon by consumers;
- strengthening authentication/authorization in a way that invalidates an accepted consumer flow;
- changing error/status semantics incompatibly;
- introducing cross-cutting security or versioning semantics that invalidate existing clients.

Deprecation is a governed transition, not silent removal. The old contract remains testable during the approved migration window until consumers are revalidated or explicitly retired.

## CI guard

CI runs:

```text
php scripts/api-evolution.php validate .blueprint/api-contract-baseline.json
bash scripts/test-api-evolution-guard.sh
```

If the current contract equals the frozen baseline, validation passes without an impact report.

If drift is detected, CI fails unless exactly one matching impact report exists. The guard automatically searches `.blueprint/api-impacts/API-IMPACT-*.json`; `API_IMPACT_FILE` is only an explicit override for controlled QA scenarios.

The impact report must conform to Blueprint 0.5.4 semantics:

- `previous_revision` equals the frozen baseline contract revision;
- `new_revision` equals the currently generated contract revision;
- `changed_operation_ids` exactly matches detected drift;
- `classification` is `operation_local`, `platform_cross_cutting` or `project_cross_cutting`;
- revalidation policy matches that classification;
- changed contract areas use the canonical Blueprint vocabulary;
- affected slices identify valid platform scopes;
- `preserve_unrelated_evidence` is always `true`;
- evidence IDs are explicit and valid.

The self-test proves three paths on every CI run: unchanged baseline passes; ungoverned drift fails; governed drift with a valid impact report passes. An invalid revalidation policy is rejected.

## Impact and revalidation lifecycle

After the initial API Gate has consumers, a consumer-affecting contract change follows this sequence:

1. Change the authoritative API contract/backend first. Do not invent substitute semantics in a client.
2. Let the evolution guard identify the exact changed `operationId` set.
3. Create one `.blueprint/api-impacts/API-IMPACT-NNN.json` for the detected revision transition.
4. Classify the impact:
   - operation-local changes use `affected_only`;
   - platform-cross-cutting changes use `platform`;
   - project-cross-cutting changes use `project`.
5. Revalidate only the affected consumers/scopes unless the contract change is genuinely cross-cutting.
6. Preserve unrelated previously accepted evidence.
7. Record the impact in `.blueprint/status.yaml` while it is OPEN/REVALIDATING.
8. Resolve the impact only after the required evidence is green.
9. Advance the frozen baseline only after the impact and required revalidation are accepted. The baseline must never be updated merely to silence drift.

## Current applicability

No `API-IMPACT-*` artifact is created by BP-ADOPT-005 because there is no post-initial-gate consumer-affecting contract change to describe. In the current Brownfield alignment state:

- `api.versioning_policy` can be evidenced by this policy and the executable guard;
- `api.change_impact_analysis` remains `N/A` until its Blueprint conditional rule becomes applicable;
- `api.affected_consumer_revalidation` remains `N/A` until an actual impact exists;
- `api_impacts` remains an empty array.

This checkpoint does not change endpoint behavior, OpenAPI semantics, authentication, authorization, generated routes or production runtime.
