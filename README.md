# ApiBlueprint

ApiBlueprint es un **diseñador y blueprint reutilizable de APIs Laravel**. Combina Clean Architecture, plantillas editables, exposición explícita de endpoints y un contrato exportable que gobierna las soluciones Laravel generadas.

## Baseline ejecutable

- Laravel 13 (`PHP >= 8.3`)
- límites de Clean Architecture y test de dependencias
- landing/configurador de API
- plantillas editables: API en blanco, REST CRUD, SaaS y Comercio
- activación/desactivación individual de endpoints
- perfiles de exposición: Público, Autenticado, Administrador e Interno
- manifest v0.3 con gobierno transversal
- Problem Details RFC 9457 y Correlation ID
- selección de Laravel Sanctum, RBAC y rate limiting
- contratos de paginación, filtrado y ordenamiento
- base de idempotencia y auditoría exportables
- `GET /api/v1/meta/status`
- `GET /api/v1/blueprint/catalog`
- `POST /api/v1/blueprint/resolve`
- `POST /api/v1/blueprint/export`
- tests funcionales, contractuales y de arquitectura
- CI con GitHub Actions
- aceptación ejecutable de los ZIP generados para Blank, CRUD, SaaS y Commerce
- validación semántica de OpenAPI generado, Composer, Pint, tests y rutas
- dependencias Composer del repositorio bloqueadas para builds reproducibles
- promoción gobernada a producción mediante `deploy/production`
- smoke post-deploy de landing, status, SHA y exportación ZIP

## Regla principal

> Si un endpoint o una capacidad transversal no están seleccionados por el blueprint, no deben aparecer como infraestructura dormida en la solución generada.

El generador crea rutas, adaptadores de presentación, gobierno transversal, OpenAPI y pruebas requeridas por el manifest seleccionado.

## Arquitectura

```text
Presentation  --->  Application  <---  Infrastructure
                         |
                         v
                       Domain
```

Domain y Application son independientes de Laravel. El framework vive en los bordes exteriores.

## Convención de idioma

El código y los identificadores técnicos se escriben en inglés. La landing, las respuestas visibles, las validaciones, Swagger/OpenAPI y la documentación orientada al consumidor se presentan en español. Ver `docs/governance/language-policy.md`.

## Documentación viva

La documentación evoluciona en los mismos pull requests que la implementación:

- `docs/architecture/clean-architecture.md`
- `docs/product/blueprint-manifest.md`
- `docs/product/templates.md`
- `docs/product/governed-capabilities.md`
- `docs/delivery/roadmap.md`
- `docs/delivery/production-deployment.md`
- `docs/delivery/generated-solution-acceptance.md`
- `docs/governance/language-policy.md`

## Producción

Destino canónico: **Eliasworks (`eliasworks.uy`)**.

`main` no despliega automáticamente. Producción se promueve de forma explícita moviendo `deploy/production` a un SHA aprobado de `main`. El mismo mecanismo permite rollback y siempre exige smoke posterior al deployment. La configuración específica del document root y de la URL pública permanece fuera del código mediante variables del repositorio.
