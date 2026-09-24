# BP-ADOPT-007 — Client/UI governance

## Current boundary: BP-ADOPT-007A Interface Scope Baseline

ApiBlueprint adopts the Blueprint 0.5.4 client-delivery model as a Brownfield consumer. The current web client is intentionally preserved: Laravel Blade views with inline CSS/JavaScript remain the observed implementation until an approved slice-specific migration boundary exists.

This checkpoint does **not** authorize a UI rewrite and does not replace Blade with React. Blueprint 0.5.4 treats framework, language and UI toolkit as consumer choices.

## Observed web surfaces

The Brownfield scope baseline records exactly three existing web interfaces:

| ID | Interface | Route | Source |
| --- | --- | --- | --- |
| `WEB-001` | Compositor de API | `/` | `resources/views/welcome.blade.php` |
| `WEB-002` | Catálogo maestro | `/catalogo` | `resources/views/catalog.blade.php` |
| `WEB-003` | Swagger vivo | `/swagger` | `resources/views/swagger.blade.php` |

All three are classified `OBSERVED` / `EXISTING`. No proposed replacement interface is introduced by this checkpoint.

## Maturity and API boundary

`.blueprint/ui/interface-scope-baseline.json` uses `maturity: SCOPE_BASELINE`.

That maturity is descriptive planning evidence only. It does not authorize implementation, does not create Functional Interface Slices and does not satisfy `interface_inventory_ready`.

The observed UI consumes administrative runtime endpoints including:

- `GET /api/v1/blueprint/catalog`;
- `GET /api/v1/blueprint/openapi`;
- `POST /api/v1/blueprint/resolve`;
- `POST /api/v1/blueprint/export`.

Those administrative dependencies are real, but canonical client `operationId` bindings are not invented here. They remain `unresolved_api_needs` until the API Gate and administrative API contract are reconciled.

Likewise, the current requirements catalog primarily governs generated API features. BP-ADOPT-007A therefore does not fabricate UI requirement IDs. Formal UI requirement bindings must exist before conversion to `EXECUTABLE_INVENTORY`.

## Gate semantics

This checkpoint may close only the pre-API Interface Scope Baseline gate:

- `ui.interface_scope_baseline`;
- `ui.interface_scope_traceability`;
- `ui.brownfield_observed_interface_scope`;
- `interface_scope_ready`.

It must **not** promote any of the following while `api_gate` is blocked:

- `interface_inventory_ready`;
- `design_system_ready`;
- `client_architecture_ready`;
- `functional_slice_ready`;
- `visual_functional_review_pass`;
- `integration_qa_pass`.

`functional_slices` and `scoped_gates` therefore remain empty at this boundary.

## QA

`tests/Feature/InterfaceScopeBaselineTest.php` proves that:

1. the baseline is Brownfield `SCOPE_BASELINE` at schema 0.5.4;
2. its stable IDs are exactly `WEB-001`, `WEB-002` and `WEB-003`;
3. each item maps to a real named route and existing Blade view;
4. the three observed routes respond successfully in the application test runtime;
5. every item carries unresolved API needs;
6. no `operation_ids`, slice IDs or executable reconciliation fields are smuggled into the pre-API baseline.

## Next boundary

BP-ADOPT-007B begins only after the initial `api_gate` is legitimately reconciled to PASS. At that point the baseline is reconciled into `EXECUTABLE_INVENTORY`, every API-backed dependency receives a real canonical `operationId`, requirements/permissions are bound, and committed interfaces are assigned to Functional Interface Slices.

No production deployment is part of BP-ADOPT-007A.
