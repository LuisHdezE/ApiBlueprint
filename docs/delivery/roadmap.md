# Delivery roadmap

Implementation and documentation advance together. Documentation is evidence of the current executable product, not a separate documentation phase.

## U0.1 - Foundation and executable configurator

- Laravel 13 / PHP 8.3+ baseline
- Clean Architecture boundaries
- API status and catalog endpoints
- Editable landing-page templates
- Explicit endpoint enablement and exposure profiles
- Manifest export v0.1
- Architecture/feature tests
- GitHub Actions CI

## U0.2 - Export engine

- Validate manifest server-side
- Resolve capability/endpoint dependencies
- Generate only selected Laravel vertical slices
- Produce downloadable solution archive
- Generate matching route/OpenAPI/test surface

## U0.3 - Governed API foundation capabilities

- RFC 9457 Problem Details
- authentication strategy
- authorization/RBAC
- request correlation
- rate limiting
- pagination/filter/sort contracts
- idempotency foundation
- audit capability

## Delivery target

The canonical production destination is the Eliasworks ecosystem at `eliasworks.uy`. Deployment automation will be wired to the runtime already provisioned for ApiBlueprint; provider-specific assumptions must not leak into the application architecture.
