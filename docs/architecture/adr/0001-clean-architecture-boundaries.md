# ADR-0001 — Preserve explicit Clean Architecture boundaries

- Status: Accepted
- Date: 2026-09-24
- Scope: ApiBlueprint root product and generated-solution architecture policy

## Context

ApiBlueprint already separates Presentation, Application, Domain and Infrastructure. Application owns use cases and ports; Infrastructure owns Laravel/export adapters; Presentation owns HTTP delivery; Domain is reserved for framework-independent business concepts.

The project also exports APIs that must preserve architectural dependency direction after generation.

## Decision

Keep the existing dependency direction:

`Presentation -> Application <- Infrastructure`

with `Domain` as the innermost framework-independent boundary.

Domain and Application must not depend on Laravel, HTTP or persistence details. Infrastructure may implement Application ports. Presentation may depend on Application use cases but must not contain business rules that belong to the inner boundaries.

Generated APIs must carry executable architecture-boundary tests and those tests remain part of generated-solution acceptance.

The root `Domain` layer may remain thin when no stable identity-bearing domain model exists. Folder population is not an architectural objective.

## Consequences

- Existing root architecture tests remain authoritative executable evidence.
- Exported projects remain independently checked for forbidden dependency direction.
- Feature recipes may extend Infrastructure generation without pushing Laravel dependencies inward.
- New domain classes require a real business invariant/lifecycle, not process compliance pressure.
- Cross-layer shortcuts that bypass Application contracts require a new ADR or correction before acceptance.

## Rejected alternatives

### Collapse everything into Laravel controllers/services

Rejected because it would weaken portability, testability and the explicit dependency rule already enforced by tests.

### Move composition code into Domain only to make Domain non-empty

Rejected because the current composition workflow is primarily orchestration over catalog/configuration and export ports. Artificial entities would not add domain semantics.
