# Blueprint Manifest v0.3

El manifest es la descripción canónica de la superficie y del gobierno transversal que una API exportada puede contener.

## Regla principal

Un endpoint no seleccionado no forma parte de la solución exportada. Ocultarlo de Swagger no equivale a deshabilitarlo.

## Validación del lado servidor

El navegador envía intención de configuración. Antes de exportar, ApiBlueprint vuelve a validar el manifest y reconstruye `method`, `path`, `capability` y descripciones desde el catálogo canónico. Los valores manipulados por el cliente no se consideran fuente de verdad.

Cada endpoint resuelto contiene:

- identificador estable;
- capability técnica y etiqueta visible;
- resumen en español;
- método HTTP y ruta canónica;
- perfil de exposición: `public`, `authenticated`, `admin` o `internal`;
- indicador `auto_added` cuando fue requerido como dependencia.

## Gobierno transversal

`governance` forma parte del contrato y no es metadata decorativa. La versión 0.3 gobierna:

- `authentication`: `none` o `sanctum`;
- `rbac`;
- `correlation_id`;
- `rate_limiting.enabled` y `rate_limiting.requests_per_minute`;
- `pagination.strategy`, `pagination.default_size` y `pagination.max_size`;
- `filtering`;
- `sorting`;
- `idempotency`;
- `audit`.

Las combinaciones incompatibles son rechazadas. Una superficie con endpoints protegidos no puede exportarse con autenticación `none`. Los perfiles `admin` e `internal` requieren RBAC. Si se selecciona `audit.list`, el resolvedor habilita auditoría y registra el ajuste en `resolution.governance_adjustments`.

## Dependencias

Los perfiles protegidos pueden exigir endpoints de autenticación. También existen dependencias explícitas entre endpoints. El resolvedor agrega las dependencias obligatorias antes de exportar y las registra en `resolution.auto_added` para que nunca aparezcan de forma invisible.

## Exportación

`POST /api/v1/blueprint/export` genera un ZIP Laravel cuya estructura depende del manifest resuelto. Puede incluir:

- `.apiblueprint.json`;
- rutas con `auth:sanctum`, `can:*`, rate limiting, idempotencia y auditoría según corresponda;
- Problem Details RFC 9457 en español;
- middleware de Correlation ID;
- base de idempotencia respaldada por cache;
- puerto de auditoría y adaptador inicial a logs;
- contrato de paginación, filtrado y ordenamiento para endpoints de colección;
- contrato OpenAPI en español con seguridad y parámetros gobernados;
- pruebas contractuales de rutas y middleware;
- estructura inicial de Clean Architecture.

Los casos de uso concretos continúan siendo stubs HTTP 501 hasta que el producto exportado implemente su dominio real. La infraestructura transversal, en cambio, ya se genera desde el contrato.
