# ApiBlueprint

ApiBlueprint es una **fábrica y compositor reutilizable de APIs Laravel**. Mantiene una biblioteca creciente de funcionalidades reales, probadas y reutilizables; desde el landing se seleccionan los tipos de aplicación, endpoints y capacidades que deben formar una nueva solución Laravel exportada.

## Baseline ejecutable

- Laravel 13 (`PHP >= 8.3`)
- límites de Clean Architecture y test de dependencias
- landing/compositor de soluciones
- Application Catalog con API en blanco, REST CRUD, SaaS y Comercio
- Master Feature Library canónica
- activación/desactivación individual de endpoints
- perfiles de exposición: Público, Autenticado, Administrador e Interno
- manifest v0.3 con gobierno transversal
- Problem Details RFC 9457 y Correlation ID
- selección de Laravel Sanctum, RBAC y rate limiting
- contratos de paginación, filtrado y ordenamiento
- base de idempotencia y auditoría exportables
- `GET /api/v1/meta/status`
- `GET /api/v1/blueprint/catalog`
- `GET /api/v1/blueprint/openapi`
- `POST /api/v1/blueprint/resolve`
- `POST /api/v1/blueprint/export`
- administración visual del catálogo en `/catalogo`
- Swagger vivo de la Master Feature Library en `/swagger`
- tests funcionales, contractuales y de arquitectura
- CI con GitHub Actions
- aceptación ejecutable de los ZIP generados para Blank, CRUD, SaaS y Commerce
- validación semántica de OpenAPI generado, Composer, Pint, tests y rutas
- `products.show` ejecutable de extremo a extremo
- `products.list` ejecutable con cursor, offset, filtering, sorting y validación
- dependencias Composer del repositorio bloqueadas para builds reproducibles
- promoción gobernada a producción mediante `deploy/production`
- smoke post-deploy de landing, status, SHA y exportación ZIP

## Reglas principales

> Si un endpoint o una capacidad transversal no están seleccionados por el blueprint, no deben aparecer como infraestructura dormida en la solución generada.

> Una funcionalidad se implementa una sola vez en la Master Feature Library. Las aplicaciones la reutilizan por referencia; no se crean variantes duplicadas por tipo de aplicación.

> Cuando se identifica un nuevo tipo de aplicación y se decide trabajarlo, se registra inmediatamente en el Application Catalog, aunque su cobertura inicial sea parcial.

El generador crea rutas, adaptadores de presentación, gobierno transversal, OpenAPI y pruebas requeridas por el manifest seleccionado. Las features con receta ejecutable generan además sus capas de dominio, aplicación e infraestructura. Las features todavía pendientes permanecen claramente identificadas en el catálogo hasta completar su implementación gobernada.

## Arquitectura

```text
Presentation  --->  Application  <---  Infrastructure
                         |
                         v
                       Domain
```

Domain y Application son independientes de Laravel. El framework vive en los bordes exteriores.

## Catálogo vivo

La misma fuente canónica alimenta:

1. **Compositor / Landing**: selecciona la composición de la nueva solución.
2. **Administración del catálogo** (`/catalogo`): muestra aplicaciones, cobertura, features, estado, exportabilidad, Swagger, tests y reutilización.
3. **Swagger vivo** (`/swagger`): muestra las features implementadas cuyo contrato OpenAPI está listo.

Las colecciones `templates` y `endpoints` se mantienen como proyecciones compatibles del modelo canónico `applications` + `features` mientras evoluciona el motor de composición.

## Convención de idioma

El código y los identificadores técnicos se escriben en inglés. El landing, las respuestas visibles, las validaciones, Swagger/OpenAPI y la documentación orientada al consumidor se presentan en español. Ver `docs/governance/language-policy.md`.

## Documentación viva

La documentación evoluciona en los mismos pull requests que la implementación:

- `docs/architecture/clean-architecture.md`
- `docs/product/blueprint-manifest.md`
- `docs/product/templates.md`
- `docs/product/governed-capabilities.md`
- `docs/product/master-feature-library.md`
- `docs/delivery/roadmap.md`
- `docs/delivery/production-deployment.md`
- `docs/delivery/generated-solution-acceptance.md`
- `docs/delivery/executable-vertical-slices.md`
- `docs/governance/language-policy.md`

## Producción

Destino canónico: **Eliasworks (`eliasworks.uy`)**.

`main` no despliega automáticamente. Producción se promueve de forma explícita moviendo `deploy/production` a un SHA aprobado de `main`. El mismo mecanismo permite rollback y siempre exige smoke posterior al deployment. La configuración específica del document root y de la URL pública permanece fuera del código mediante variables del repositorio.
