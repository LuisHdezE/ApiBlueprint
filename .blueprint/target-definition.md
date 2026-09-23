# BP-AUDIT-001 — Brownfield Target Definition

## Baselines

- Consumer: `LuisHdezE/ApiBlueprint` at `21e3a29ab8de6b15c50c9b0c6e20c0f506a00820`.
- Blueprint: `LuisHdezE/SoftwareDevelopmentBlueprint` at `8d29ba4c6caf0a382b80310dc0e88c8f1e7fb3c4` (`0.5.4`).
- Adoption mode: brownfield.
- Policy: ALIGN, DO NOT REWRITE.

## AS-IS

ApiBlueprint already has a strong implementation baseline: Clean Architecture dependency direction, ports/adapters, thin HTTP controllers, a canonical Master Feature Library, stable feature IDs, governed exposure profiles, OpenAPI 3.1, RFC 9457, Sanctum/RBAC, generated-solution acceptance, CI and explicit merge governance.

The primary gaps are not a failed runtime architecture. They are incomplete formal traceability and evidence, missing operational-contract artifacts required by Blueprint 0.5.4, missing semantic audit QA, missing API impact records, and limited architecture guardrails inside exported consumer APIs.

## TO-BE

ApiBlueprint adopts SoftwareDevelopmentBlueprint 0.5.4 incrementally while preserving current behavior. The repository becomes able to prove, from versioned artifacts, the chain from requirement to feature, operation, permission, idempotency, audit behavior, tests and evidence.

The generated APIs must preserve Clean Architecture after export, not only at generation time. Architecture tests therefore become part of generated solutions and their acceptance gate.

No runtime refactor is justified solely to satisfy artifact shape. Existing working implementation and evidence are grandfathered when they materially satisfy the intended control.

## Non-goals of the adoption baseline

- No backend/runtime behavior change.
- No new API endpoints.
- No feature-state changes.
- No production deployment.
- No exporter refactor in this checkpoint.
- No UI redesign.
- No automatic merge.

## Incremental alignment backlog

### BP-ADOPT-001 — Governance baseline

Materialize `.blueprint/project.yaml`, `.blueprint/status.yaml`, evidence registry, compliance review and this target definition.

### BP-ADOPT-002 — Requirements and contract traceability

Extend the canonical feature model so implemented features can reference requirement IDs, use-case IDs, permission policy, idempotency policy, audit events and test evidence without creating a duplicate source of truth.

### BP-ADOPT-003 — Generated architecture conformance

Generate executable architecture-boundary tests inside exported APIs and include them in generated-solution acceptance.

### BP-ADOPT-004 — Operational contract and audit QA

Close the current Postman gate according to Blueprint 0.5.4 unless the Blueprint policy is deliberately evolved to accept an equivalent executable operational contract. Add semantic audit-event mapping and QA.

### BP-ADOPT-005 — API evolution evidence

Materialize operationId-scoped API impact reports for post-baseline contract changes and preserve unrelated accepted evidence.

### BP-ADOPT-006 — Maintainability hardening

Decompose the monolithic ZIP exporter into registrable feature recipes only when this can be done without changing exported behavior.

### BP-ADOPT-007 — Client/UI governance

After the API gate is reconciled, inventory the existing web composer/catalog/swagger interfaces and apply scoped client architecture, design-system, functional, review and integration gates.

## Blueprint feedback captured by this audit

- `templates/status.example.yaml` still advertises Blueprint 0.5.3 while the stable root version and project template are 0.5.4.
- `pilot_id` in the generic compliance-review schema is legacy terminology and should be generalized in a future compatible revision.
- Universal Postman applicability should be reviewed against projects that already provide reproducible executable contract acceptance; until the canonical Blueprint changes, the current Postman requirement remains authoritative.

## Exit condition

This target-definition checkpoint is complete when the adoption artifacts are versioned, schema-compatible, reviewed in CI where applicable, and presented through a PR. Merge remains a separate explicit human decision.
