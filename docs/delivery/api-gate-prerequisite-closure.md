# BP-ADOPT-007B — API Gate prerequisite closure

## Objective

Close the architecture/security prerequisites that still block the initial Blueprint 0.5.4 API Gate before any `EXECUTABLE_INVENTORY` or client implementation is allowed.

This checkpoint does **not** implement Client Architecture, Design System, Functional Interface Slices or UI redesign.

## Architecture evidence

### Domain model

`docs/architecture/domain-model.md` defines the root bounded context as **Blueprint Composition** and records why `app/Domain` is intentionally thin. ApiBlueprint owns composition rules and export semantics, not the customer business entities of generated APIs.

No fake aggregate is introduced solely to satisfy the process.

### ADRs

- `ADR-0001`: preserve explicit Clean Architecture boundaries and executable generated conformance.
- `ADR-0002`: keep the root composer stateless and separate from generated runtimes.
- `ADR-0003`: preserve the existing public root composer while treating every manifest as untrusted; generated API auth/RBAC remains a separate authoritative boundary.

### Security model and threat model

`docs/architecture/security-model.md` separates root and generated-runtime assets/actors/trust boundaries.

`docs/architecture/threat-model.md` makes threat modeling applicable for the current public export surface and documents controls/residual risks for manifest tampering, path/header injection, information disclosure, denial of service, authentication/authorization, replay/idempotency, rate limiting, audit secrecy/tampering, supply chain and deployment secrets.

## Audit retention/access policy

`docs/governance/audit-retention-policy.md` defines the policy boundary required by the canonical event/audit skill:

- root operational logs are not promoted to durable business audit;
- generated semantic audit remains server-side authoritative and append-oriented;
- secrets are excluded;
- normal business CRUD cannot mutate the audit sink;
- a generated production deployment must explicitly choose retention/archive/deletion/access/legal-hold behavior appropriate to its domain;
- the reusable generator does not fabricate one universal legal retention duration;
- synthetic CI/demo audit data is ephemeral while sanitized QA summaries may follow CI artifact retention.

## Security QA matrix

Security QA composes already-proven BP-ADOPT-004 runtime scenarios with new BP-ADOPT-007B checks instead of duplicating the operational suite.

| Security concern | Executable evidence | Expected result |
| --- | --- | --- |
| Invalid credentials | Newman `negative_auth_login_invalid` | 401 Problem Details |
| Missing authentication | Newman `negative_users_list_unauthenticated` | 401 Problem Details |
| Privilege escalation | Newman `negative_users_list_forbidden` | 403 Problem Details |
| Missing idempotency control | Newman `negative_users_create_missing_idempotency` | 400 Problem Details |
| Invalid mutation payload | Newman `negative_users_create_validation` | 422 Problem Details |
| Replay semantics | Newman `idempotency_replay_users_create` | original response + `X-Idempotent-Replay: true` |
| Password response disclosure | Newman `users_create` assertion | password absent from response |
| Durable audit secret disclosure | BP-ADOPT-004 audit runtime validation | no password/token/Authorization/Bearer markers |
| Root unexpected exception disclosure | `SecurityQaContractTest` | generic 500, no exception marker/class |
| Root filename/header/path injection | `SecurityQaContractTest` + `run-security-qa.sh` | safe slug, no injected response header, all ZIP entries under sanitized root |
| Runtime request flooding | `run-security-qa.sh` generated API with `2/min` | 200, 200, 429 + governed Problem Details + correlation |

`tests/Feature/SecurityQaContractTest.php` also freezes the required Newman scenario names so removing a security-negative request silently breaks CI.

## Runtime security harness

`scripts/run-security-qa.sh`:

1. starts the root ApiBlueprint runtime;
2. exports a manifest with hostile `project.name` containing traversal and CRLF/header-looking input;
3. proves the response filename is `evil-x-evil-injected.zip`, no `X-Evil` response header exists and every ZIP entry remains below the sanitized root;
4. exports `deployment/security-qa-manifest.json` with public `products.list` and rate limit `2/min`;
5. installs/migrates the generated Laravel solution in isolated SQLite;
6. starts that generated API;
7. performs three requests from the same client identity and requires status sequence `200, 200, 429`;
8. requires the 429 to be `application/problem+json` and preserve `X-Correlation-ID`;
9. writes only a sanitized summary to `storage/app/qa/security-qa-report.json`.

## Deliberate boundaries

- This is not penetration testing and does not claim immunity to unknown vulnerabilities.
- Root export abuse still requires deployment-level body/connection/WAF/reverse-proxy controls because the public root composer has no account quota.
- Dependency advisory review is ongoing maintenance, not proof that no future advisory will exist.
- Strong WORM/external audit storage is consumer/deployment-specific when risk/regulation requires it.
- No production deployment or promotion belongs to this checkpoint.
- No executable client work is authorized unless the resulting project-scoped `api_gate` actually becomes PASS.

## Gate intent

If all versioned evidence and CI runtime checks pass, this checkpoint may reconcile:

- `architecture.domain_model` → PASS;
- `architecture.decision_records` → PASS;
- `architecture.security_model` → PASS;
- `architecture.threat_model` → PASS (applicable and evaluated);
- `audit.retention_policy` → PASS;
- `architecture_ready` → PASS;
- `api_contract_ready` → PASS once its architecture prerequisite is satisfied;
- `api.security_qa` → PASS;
- `api_qa_pass` → PASS;
- `api_gate` → PASS.

Only after that project gate is genuinely green may BP-ADOPT-007 continue from the Brownfield `SCOPE_BASELINE` to `EXECUTABLE_INVENTORY`.
