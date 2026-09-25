# ADR-0003 — Treat the root composer as a public control surface, not as the generated API security authority

- Status: Accepted
- Date: 2026-09-24
- Scope: ApiBlueprint root HTTP surface

## Context

The root runtime exposes public catalog, OpenAPI, manifest resolution and ZIP export endpoints consumed by the existing Blade composer/catalog/Swagger views. These endpoints do not operate on customer accounts or customer business data.

Generated APIs are different products: their selected exposure profile, Sanctum authentication, RBAC, rate limiting, idempotency and audit behavior are produced from the resolved manifest and remain authoritative inside the generated runtime.

## Decision

Keep the current root composer endpoints publicly callable while treating all request payloads as untrusted input.

Root controls must therefore focus on:

- strict manifest/catalog allowlisting and bounded values;
- deterministic dependency resolution before export;
- safe archive paths and slugged download filenames;
- generic Problem Details without stack/exception leakage;
- correlation for diagnostics;
- no persistence of secrets or authoritative customer data;
- deployment protections appropriate to a public, potentially expensive export endpoint.

Do not add root authentication merely to satisfy a security checklist because doing so would change the existing public product contract and break the Brownfield UI without an approved product requirement.

Generated API authentication/authorization remains a separate security boundary and is validated independently.

## Consequences

- Security QA must include both root-input/export safety and generated-runtime auth/RBAC controls.
- The public root surface must never be described as trusted simply because it is an administrative/composer plane.
- Any future accounts, saved projects, private templates or organization data would invalidate this ADR's public/no-account assumptions and require a new security/authentication decision.
- Infrastructure-level abuse controls for the public export endpoint remain an operational responsibility in addition to application validation.

## Rejected alternatives

### Require login for all root endpoints now

Rejected because no account/private-data requirement exists and it would be a breaking Brownfield behavior change.

### Treat generated API security as protection for the root composer

Rejected because the runtimes are separate trust boundaries.
