# ApiBlueprint threat model

## Applicability

Threat modeling is **applicable** because the root product exposes a public manifest-resolution/export surface that processes untrusted input and generates downloadable source archives. Generated APIs also introduce authentication, authorization, persistence and audit boundaries when those capabilities are selected.

This is a focused STRIDE-style model for the current root + generated-runtime boundaries, not a claim of exhaustive penetration testing.

## Threats and controls

| ID | Category | Boundary | Threat | Existing control | Residual / follow-up |
| --- | --- | --- | --- | --- | --- |
| TM-01 | Tampering | Root manifest | Caller submits unknown endpoint/template/exposure/governance values to produce unsupported code | `ResolveBlueprintManifest` allowlists catalog IDs/exposures, validates governance types/ranges and resolves dependencies before export | Keep resolver tests and catalog reconciliation in CI |
| TM-02 | Tampering / path traversal | Root export | Project name attempts `../`, separators, quotes or CRLF to affect ZIP paths/download filename | Exporter derives a restricted slug and uses repository-owned relative file paths | Security QA must prove hostile names cannot escape slug/header/archive root |
| TM-03 | Information disclosure | Root API | Unexpected exception leaks stack, filesystem path or exception message | Root Problem Details maps 5xx to generic public text | Keep `APP_DEBUG=false` in production and test response confidentiality where executable |
| TM-04 | Denial of service | Root export | Repeated ZIP generation consumes CPU/memory/disk | Manifest fields/endpoints are bounded by canonical catalog; temp archive is removed after read | Deployment must provide request/body/connection and abuse throttling; root currently has no account-based quota |
| TM-05 | Spoofing | Generated API | Caller uses missing/invalid bearer token against protected endpoint | Sanctum auth middleware + Newman 401 acceptance | Revalidate on auth strategy changes |
| TM-06 | Elevation of privilege | Generated API | Authenticated non-admin calls admin endpoint | RBAC gate + Newman 403 acceptance | Revalidate permission policy/role model changes |
| TM-07 | Replay / duplicate mutation | Generated API | Client retries a protected mutation causing duplicate side effects | Idempotency-Key middleware + replay cache + operational 400/replay acceptance | Store semantics are deployment-dependent; retain impact testing on changes |
| TM-08 | Denial of service | Generated API | Client floods an endpoint | Configured `throttle:api` with per-user/IP limiter | BP-ADOPT-007B adds runtime 429 evidence |
| TM-09 | Information disclosure | Generated errors | Exceptions or validation paths leak internals/secrets | Generated Problem Details uses generic 5xx detail and controlled validation extensions | Keep debug disabled in deployment; do not add raw exception messages to extensions |
| TM-10 | Information disclosure | Audit | Password/token/header values enter durable audit | Semantic event contract excludes request bodies/headers and recursively redacts sensitive keys/Bearer values | Existing audit runtime QA must remain green |
| TM-11 | Repudiation | Generated API | Significant operations cannot be correlated to actor/outcome | Semantic event code, actor, target, outcome and correlation ID are recorded | Retention/access policy must preserve accountable records long enough for operations |
| TM-12 | Tampering | Audit | Normal application code alters/removes accountable audit records | Current JSONL semantic sink is append-oriented and not exposed through ordinary generated CRUD | Filesystem/operator privileges remain a trusted administrative boundary |
| TM-13 | Supply chain | Root/generated build | Dependency or recipe changes silently alter generated behavior | Exact export parity guard, generated acceptance, pinned Newman, Composer lock for root, PR/CI governance | Dependency advisory review remains an operations/maintenance concern |
| TM-14 | Secret disclosure | Repository/deploy | APP_KEY or deployment credentials enter source/export artifact | `.env` is excluded from deployment release/export; generated project contains `.env.example` with empty APP_KEY | Secret provisioning remains deployment-owned; never record real values in evidence |

## Trust assumptions

- GitHub repository write access and approved merge actions are privileged administrative operations.
- The hosting filesystem/operator can read application files and logs; OS/hosting account compromise is outside the application authorization boundary.
- TLS termination is required for production transport but is provided by deployment infrastructure, not implemented inside Laravel.
- Generated API consumers cannot be trusted merely because they originate from the root composer UI.

## Residual risks accepted for the current baseline

### Public root export abuse

The root composer intentionally remains public. Application input validation limits semantic complexity, but it does not replace infrastructure rate/body/connection controls. This is an operational availability risk, not authorization to add an undocumented login requirement.

### File-backed audit administrator access

The reference semantic audit sink is append-oriented JSONL. An OS/hosting administrator can still alter files. Stronger WORM/external audit storage is deployment-specific and should be selected when regulatory/risk requirements demand it.

### Dependency vulnerability horizon

CI proves current behavioral/security contracts, not the absence of all future ecosystem advisories. Dependency update/advisory review remains ongoing maintenance.

## Re-evaluation triggers

Re-run this threat model when root authentication/accounts, persistent root state, private recipes/plugins, tenanting, remote integrations, signing keys or new cross-cutting auth/security contract changes are introduced.
