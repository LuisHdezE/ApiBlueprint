# ADR-0002 — Keep the root composer stateless and separate from generated runtimes

- Status: Accepted
- Date: 2026-09-24
- Scope: ApiBlueprint root runtime

## Context

ApiBlueprint serves a catalog/composer and exports Laravel API projects. The root product does not own the customer data that those generated projects may later persist.

Adding a root business database or sharing runtime state between the composer and generated APIs would blur data ownership and create infrastructure that is not required by current behavior.

## Decision

The root ApiBlueprint runtime remains stateless with respect to authoritative business data.

- Catalog and feature definitions are repository-owned configuration.
- Requested manifests are request-scoped input.
- Resolved manifests are computed in memory.
- Export archives use temporary files and are returned to the caller.
- Generated APIs are independent deployable runtimes and own their selected persistence/security capabilities.
- Root and generated runtimes do not share an authoritative database.

A root database may be introduced only for a new root-owned capability with explicit data ownership, migrations, security model and an ADR.

## Consequences

- Root `data.architecture`, `data.schema_migrations` and `data.authoritative_database` are legitimately not applicable today.
- Generated solution database requirements are verified in generated acceptance instead of being projected onto the root product.
- Export reproducibility is based on manifest + repository revision rather than server-side mutable project state.
- Temporary ZIP storage must not become an undocumented durable store.

## Rejected alternatives

### Persist every requested manifest in a root database

Rejected because history/account/workspace behavior is not currently a product requirement and would create unnecessary sensitive/operational state.

### Share the root database with generated APIs

Rejected because it would collapse trust and ownership boundaries between the factory and its outputs.
