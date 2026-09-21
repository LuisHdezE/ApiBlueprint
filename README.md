# ApiBlueprint

ApiBlueprint es un **diseñador y blueprint reutilizable de APIs Laravel**. Combina Clean Architecture, plantillas editables, exposición explícita de endpoints y un contrato exportable que gobierna las soluciones Laravel generadas.

## Baseline ejecutable

- Laravel 13 (`PHP >= 8.3`)
- límites de Clean Architecture y test de dependencias
- landing/configurador de API
- plantillas editables: API en blanco, REST CRUD, SaaS y Comercio
- activación/desactivación individual de endpoints
- perfiles de exposición: Público, Autenticado, Administrador e Interno
- contrato `.apiblueprint.json`
- `GET /api/v1/meta/status`
- `GET /api/v1/blueprint/catalog`
- tests funcionales y de arquitectura
- CI con GitHub Actions

## Regla principal

> Si un endpoint no está seleccionado por el blueprint, no debe formar parte del contrato ni de la solución generada.

El generador crea únicamente las rutas Laravel, adaptadores de presentación, casos de uso, políticas, superficie OpenAPI y tests requeridos por el manifest seleccionado.

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
- `docs/delivery/roadmap.md`
- `docs/governance/language-policy.md`

## Producción

Destino canónico: **Eliasworks (`eliasworks.uy`)**.
