# ApiBlueprint root domain model

## Purpose

This document defines the domain boundary of the **ApiBlueprint root product**. It describes the stable business concepts that the root composer governs without inventing persistent aggregates that the implementation does not own.

## Bounded context

ApiBlueprint has one root bounded context: **Blueprint Composition**.

Its responsibility is to transform a requested blueprint manifest into a validated, dependency-complete description of an API solution and then export that resolved description as a reproducible Laravel project.

The root product does **not** own customer business entities such as users, products, orders or payments. Those concepts belong to the APIs generated from the Master Feature Library. They are output-domain concepts, not mutable root-domain state.

## Stable concepts

The current stable domain language is conceptual and intentionally represented by configuration plus Application services rather than mutable Domain entities:

- **Feature definition**: canonical endpoint/capability metadata from the Master Feature Library.
- **Requested manifest**: untrusted composition intent supplied by a caller.
- **Resolved manifest**: validated composition after dependency and governance reconciliation.
- **Exposure policy**: public, authenticated, admin or internal exposure semantics.
- **Governance profile**: authentication, RBAC, correlation, rate limiting, pagination, filtering, sorting, idempotency and audit settings.
- **Exported blueprint**: immutable ZIP artifact produced from one resolved manifest.

These concepts have observable business rules, but the root product currently has no lifecycle that requires identity-bearing aggregates, repositories or durable domain persistence.

## Invariants

1. Unselected endpoints are absent from the exported solution.
2. Selected endpoints must exist in the canonical catalog.
3. Exposure values must be canonical.
4. Endpoint and exposure dependencies are resolved before export.
5. Governance settings must be compatible with the selected surface.
6. The resolved manifest is the sole input to the exporter.
7. Generated files are derived from the resolved manifest and registered recipes, not from arbitrary filesystem paths supplied by the caller.
8. The root product does not persist authoritative customer business data.

## Why `app/Domain` remains intentionally thin

`app/Domain` currently contains only its boundary README. This is deliberate, not an unfinished entity model.

The stable composition rules are already expressed in `Application` because they orchestrate canonical catalog data and export ports. Promoting those rules into Domain classes merely to populate a folder would add ceremony without a new invariant, identity or lifecycle.

A future concept moves into `Domain` only when executable behavior demonstrates a framework-independent business model that benefits from domain entities, value objects, policies or domain events.

## Runtime boundary

The root dependency direction remains:

`Presentation -> Application <- Infrastructure`

`Domain` is innermost and framework-free. Application contracts define the ports used by the exporter and catalog adapters. Infrastructure implements those ports and may depend on Laravel, ZIP handling and filesystem/runtime services.

Generated APIs are separate runtime products. Their domain/application boundaries are validated independently by generated architecture tests.

## Data ownership

The root product is stateless with respect to authoritative business data:

- canonical feature/catalog data is repository-owned configuration;
- manifests arrive per request and are resolved in memory;
- exported ZIPs are created in temporary storage and returned to the caller;
- no root business database is authoritative;
- durable audit policy for generated APIs is governed separately.

This model is consistent with the existing `data.architecture`, `data.schema_migrations` and `data.authoritative_database` checks being `N/A` for the root product boundary.
