# Capacidades gobernadas de API

U0.3 convierte decisiones transversales del configurador en arquitectura exportada.

## Seguridad

`authentication = sanctum` agrega `laravel/sanctum` y protege los perfiles no públicos con `auth:sanctum`. Los perfiles `admin` e `internal` agregan autorización mediante Gates generados. ApiBlueprint no inventa usuarios o roles de dominio: exporta el punto de integración estándar y deja esa identidad bajo control del producto concreto.

## Problem Details y correlación

Las excepciones API se normalizan con `application/problem+json` y textos visibles en español. Cuando `correlation_id` está habilitado, se propaga una cabecera `X-Correlation-ID` válida o se genera un UUID nuevo, y el identificador aparece en los Problem Details.

## Rate limiting

Cuando se habilita, el exportador crea un limitador `api` con el número de solicitudes por minuto indicado en el manifest y lo asocia a las rutas exportadas.

## Paginación, filtros y ordenamiento

Los endpoints `*.list` reciben un contrato común de query:

- `page[size]`;
- `page[cursor]` para estrategia cursor o `page[number]` para offset;
- `filter[campo]=valor` cuando el filtrado está habilitado;
- `sort=campo,-otroCampo` cuando el ordenamiento está habilitado.

El mismo contrato aparece en OpenAPI.

## Idempotencia

Los métodos `POST`, `PUT` y `PATCH` reciben middleware de idempotencia cuando la capacidad está habilitada. La base usa un puerto de Application y un adaptador de Infrastructure respaldado por Laravel Cache. El consumidor debe enviar `Idempotency-Key`.

## Auditoría

La auditoría se modela con un puerto `AuditTrail` y un adaptador inicial `LogAuditTrail`. El middleware registra ruta, método, estado, Correlation ID y usuario cuando exista. Productos que necesiten persistencia relacional pueden sustituir el adaptador sin cambiar Application.

## Principio de exportación

Una capacidad deshabilitada no debe generar infraestructura innecesaria. La selección gobierna dependencias de Composer, middleware, bindings, OpenAPI, pruebas y archivos exportados.
