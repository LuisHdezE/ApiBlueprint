# ApiBlueprint security model

## Scope

This model covers two distinct trust boundaries:

1. **ApiBlueprint root composer**: public Blade/web + `/api/v1/blueprint/*` composition/export surface.
2. **Generated API runtime**: the exported Laravel application whose auth/RBAC/rate-limit/idempotency/audit behavior is selected by the resolved manifest.

The root product is not an authentication proxy for generated APIs.

## Assets

### Root composer

- canonical Master Feature Library and requirements/traceability configuration;
- resolved manifest integrity;
- exporter recipes and generated source templates;
- CI/evidence artifacts;
- server secrets such as `APP_KEY` and deployment credentials;
- availability of the public composition/export service.

### Generated runtime

- bearer tokens and credentials when Sanctum is enabled;
- authorization roles/abilities;
- generated business data stores;
- idempotency records;
- durable semantic audit records;
- application secrets and deployment configuration.

## Trust boundaries and actors

### Anonymous root caller

The existing composer is public. All root manifests, project names and request metadata are untrusted. Public access is an intentional Brownfield behavior, not a trust assertion.

### Repository/CI maintainer

May change catalog definitions, recipes, governance code and evidence. Git/PR governance and CI are the integrity boundary for those changes.

### Generated API consumer

May be anonymous, authenticated, admin or internal according to the generated endpoint exposure profile. Generated middleware is authoritative for runtime access decisions.

### Deployment operator

Owns root/generated application secrets, HTTPS termination, runtime filesystem permissions, log/audit access and infrastructure-level abuse controls.

## Root security controls

### Input and composition integrity

`ResolveBlueprintManifest` validates schema version, bounded project name, known template IDs, known endpoint IDs, canonical exposure values, typed/bounded governance options and dependency compatibility before export.

Unknown catalog IDs are rejected. Endpoint dependencies and governance adjustments are resolved before the exporter receives the manifest.

### Export safety

The exporter derives archive paths from repository-owned file keys and a slug generated from `project.name`. Caller input is not used as an arbitrary filesystem path. ZIP creation uses a temporary server file which is read and removed before the response is returned.

The response filename is based on the same slug and sends `X-Content-Type-Options: nosniff`.

### Error confidentiality

Root API exceptions are rendered as governed Problem Details. Unexpected 5xx errors use a generic message rather than exposing exception text or stack traces.

### Correlation

Root requests pass through `CorrelationIdMiddleware` for operational traceability. Correlation IDs are diagnostic metadata, not authentication.

### Secrets and persistence

The root application does not accept or persist bearer credentials as part of its manifest contract and owns no authoritative customer database. Runtime/deployment secrets stay in environment/server configuration and are excluded from exported source artifacts.

## Generated API security controls

When selected by the manifest, generated APIs provide:

- Laravel Sanctum bearer authentication;
- RBAC gates for admin/internal exposure;
- per-minute rate limiting keyed by authenticated user ID or client IP;
- idempotency keys for governed state-changing operations;
- generic RFC 9457-compatible errors;
- correlation ID propagation;
- semantic audit events with recursive secret redaction;
- OpenAPI security declarations matching protected operations.

The generated runtime, not the Blade client, is authoritative for authentication/authorization.

## Sensitive-data handling

The following values must never be persisted merely because they appear in transport/runtime context:

- passwords and password hashes;
- bearer/authorization headers;
- access/refresh/reset tokens;
- API keys and secrets;
- signing/private-key material;
- raw credential-bearing request payloads.

Semantic audit keeps only the minimum accountable metadata required by the event contract and recursively redacts sensitive keys/values.

## Availability and abuse boundary

ZIP export is computationally more expensive than catalog/status reads. The root endpoint is public, so deployment protections such as HTTPS termination, request/body limits, connection limits and reverse-proxy/WAF throttling are part of operations.

ApiBlueprint does not claim that public availability can be protected solely by generated-API middleware because the root composer and generated runtime are separate products.

## Change triggers

This security model must be revisited when any of these appear:

- root user accounts or private workspaces;
- saved manifests/projects;
- private/custom recipe uploads;
- root authoritative database state;
- server-side third-party credentials supplied by users;
- tenant separation;
- remote code/plugin execution;
- production signing keys or licensing issuer capabilities.
