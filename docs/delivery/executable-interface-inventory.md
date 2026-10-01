# BP-ADOPT-007C — Executable Interface Inventory

## Boundary and source

The accepted Brownfield scope baseline (`.blueprint/ui/interface-scope-baseline.json`) records three observed Blade surfaces. The initial API Gate passed in BP-ADOPT-007B. This checkpoint reconciles every baseline ID into `.blueprint/ui/interface-inventory.json` without replacing the working views.

| ID | Route | Disposition | Requirement | Slice owner | Priority |
| --- | --- | --- | --- | --- | ---: |
| WEB-001 | `/` | COMMITTED | REQ-UI-001 | `composer-web` | 1 |
| WEB-002 | `/catalogo` | COMMITTED | REQ-UI-002 | `catalog-web` | 2 |
| WEB-003 | `/swagger` | COMMITTED | REQ-UI-003 | `swagger-web` | 3 |

Requirements in `.blueprint/ui/interface-requirements.json` describe the root web client, while `config/blueprint_requirements.php` continues to govern generated API features. The `slice_id` values assign inventory ownership; they do not claim Functional Interface Slice implementation or acceptance.

## Two API boundaries

`.blueprint/root-api-openapi.json` documents existing routes in `routes/api.php`. It assigns stable operationIds to the root composer only. It does not add endpoints, change response behavior or alter the generated Master Feature Library contract used by `.blueprint/api-contract-baseline.json` and `scripts/api-evolution.php`.

| Root operationId | Method and path | Consumer |
| --- | --- | --- |
| `root_status_show` | GET `/api/v1/meta/status` | Operational checks; no inventory view binding |
| `root_blueprint_catalog_show` | GET `/api/v1/blueprint/catalog` | WEB-001, WEB-002 |
| `root_blueprint_openapi_show` | GET `/api/v1/blueprint/openapi` | WEB-003 |
| `root_blueprint_manifest_resolve` | POST `/api/v1/blueprint/resolve` | WEB-001 |
| `root_blueprint_solution_export` | POST `/api/v1/blueprint/export` | WEB-001 |

The response from `root_blueprint_openapi_show` describes generated solutions. Its `auth_login`, `products_list`, `users_create` and other generated operationIds are not root web client operations. The root API document describes only the exercised top-level response media and boundary; its deliberately broad object schemas are not a field-level replacement for the generated contract.

The existing root composer is public. All three views have the `anonymous` role and no permission requirement. Local selection, search, filtering and navigation are explicitly local operations. The download first resolves the manifest, then calls export. API data and actions bind to canonical root operationIds; absent root behavior must be treated as `BLOCKED_BY_API` in future slices, not simulated by UI.

## Verification and next gate

`tests/Feature/ExecutableInterfaceInventoryTest.php` checks stable IDs and dispositions, requirement ownership, root operationIds against actual routes, and every observed Blade API call against its inventory binding. Existing feature tests cover route responses, manifest validation and ZIP export. No new UI or production deployment is part of this checkpoint.

`interface_inventory_ready` can pass only after that test and the project CI are green. Design System, Client Architecture, Functional Interface Slices, Visual & Functional Review, Integration QA and Release remain pending. The next permitted phase is Design System.
