# BP-ADOPT-002 — Requisitos y trazabilidad contractual

ApiBlueprint mantiene una sola identidad canónica por feature. Los requisitos, actores, reglas de negocio, casos de uso y criterios de aceptación existen como catálogos referenciables; la relación entre esos conceptos y una operación HTTP vive dentro de la feature canónica de `config/blueprint.php`.

## Fuente de verdad

- `config/blueprint_requirements.php`: define actores, requisitos funcionales/no funcionales, reglas de negocio, criterios de aceptación, casos de uso, políticas de permiso, políticas de idempotencia y catálogo de eventos de auditoría.
- `config/blueprint.php`: define cada feature una sola vez y enlaza sus IDs de trazabilidad.
- `traceability`: proyección derivada exclusivamente de las features implementadas. No es un segundo registro editable.
- `/api/v1/blueprint/openapi`: proyecta la misma relación mediante extensiones `x-apiblueprint-*`.

## Contrato mínimo de una feature implementada

Toda feature con `implementation_status=implemented` debe declarar:

- `requirement_ids`;
- `use_case_ids`;
- `acceptance_criteria_ids`;
- `business_rule_ids` cuando existan reglas específicas;
- `permission_policy`;
- `idempotency_policy`;
- `audit_events`;
- `test_evidence`;
- `traceability_status=complete`.

Las features planificadas pueden conservar trazabilidad pendiente hasta que entren en implementación. No se permite marcar una feature implementada como trazable si sus referencias no existen.

## Permission matrix

Las políticas canónicas se mantienen separadas del framework:

- `public`: operación pública;
- `authenticated`: token válido, sin ability adicional;
- `admin-api`: autenticación y ability `admin-api`;
- `internal-api`: autenticación y ability `internal-api`.

El test contractual exige que la política coincida con `default_exposure` de la feature.

## Idempotency matrix

La matriz refleja el runtime generado actual:

- `auth.login` y `auth.logout`: `excluded`;
- `POST`, `PUT` y `PATCH` restantes: `required_when_enabled`;
- otros métodos: `not_applicable`.

Esta regla queda protegida por prueba para evitar drift entre catálogo y middleware generado.

## Auditoría

BP-ADOPT-002 define el catálogo y el mapping operación → evento de auditoría. Esto cierra el contrato de trazabilidad, pero no declara cerrada la QA semántica de auditoría.

El runtime actual captura metadatos transversales del request: ruta, método, path, status, correlation ID y actor disponible. La emisión semántica y su QA positiva/negativa pertenecen a BP-ADOPT-004.

## OpenAPI

Cada operación implementada incorpora:

- `x-apiblueprint-requirements`;
- `x-apiblueprint-use-cases`;
- `x-apiblueprint-acceptance-criteria`;
- `x-apiblueprint-business-rules`;
- `x-apiblueprint-permission-policy`;
- `x-apiblueprint-idempotency-policy`;
- `x-apiblueprint-audit-events`;
- `x-apiblueprint-test-evidence`.

`operationId` continúa derivándose del feature ID estable, por ejemplo `users.create` → `users_create`.

## QA

`BlueprintTraceabilityContractTest` valida de forma ejecutable:

1. unicidad y referencias internas de los catálogos;
2. cobertura completa de cada feature implementada;
3. coherencia de permiso con exposición;
4. coherencia de idempotencia con el runtime generado;
5. existencia física de la evidencia de tests referenciada;
6. proyección exacta a la matriz derivada y a OpenAPI.

El objetivo no es producir documentación ornamental. Es impedir que requisito, operación, permiso, auditoría y prueba evolucionen por caminos separados.
