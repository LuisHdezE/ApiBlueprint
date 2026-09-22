# Roadmap de entrega

Implementación y documentación avanzan juntas. La documentación evidencia el producto ejecutable actual y no constituye una fase separada.

## U0.1 - Foundation y configurador ejecutable ✅

- Laravel 13 / PHP 8.3+
- límites de Clean Architecture
- endpoints de estado y catálogo
- plantillas editables en la landing
- selección explícita de endpoints y perfiles de exposición
- manifest v0.1
- tests de arquitectura y funcionales
- CI con GitHub Actions

## U0.2 - Motor de exportación ✅

- política de idioma: código en inglés, superficie visible en español
- validación del manifest en servidor
- reconstrucción canónica de endpoints desde el catálogo
- resolución explícita de dependencias
- generación exclusiva de la superficie seleccionada
- ZIP Laravel descargable
- rutas, OpenAPI en español y tests derivados del mismo manifest

## U0.3 - Capacidades gobernadas de la API ✅

- Problem Details RFC 9457 global
- estrategia de autenticación
- autorización/RBAC
- correlación de requests
- rate limiting
- contratos de paginación, filtrado y ordenamiento
- base de idempotencia
- capability de auditoría

## U0.4 - Entrega continua y runtime público 🚧

La implementación de release ya está mergeada en `main` y el SHA aprobado fue promovido mediante `deploy/production`. El build y la transferencia FTP hacia Eliasworks concluyeron correctamente. El cierre permanece pendiente del smoke público, bloqueado temporalmente por la disponibilidad DNS del subdominio productivo.

- `composer.lock` obligatorio para builds reproducibles;
- CI rechaza releases sin lock;
- producción separada del merge mediante `deploy/production`;
- solo se aceptan SHAs pertenecientes a la historia de `main`;
- build de producción sin dependencias de desarrollo;
- publicación FTP gobernada hacia Eliasworks;
- `.env` productivo preservado fuera del release;
- `release.json` con evidencia del SHA desplegado;
- smoke de landing, status, SHA y exportación ZIP;
- rollback mediante la misma referencia de promoción;
- documentación operacional en `docs/delivery/production-deployment.md`.

U0.4 se considera cerrado únicamente cuando el runtime público complete el smoke verde. La promoción y transferencia ya realizadas no sustituyen esa evidencia.

## U0.5 - Aceptación de soluciones generadas ✅

El gate de aceptación ejecutable está integrado en `main` y exporta y prueba proyectos Laravel reales antes de permitir su promoción:

- presets Blank, CRUD, SaaS y Commerce;
- descompresión aislada de cada ZIP;
- OpenAPI 3.1 parseado semánticamente y sin claves YAML duplicadas;
- paths y métodos reconciliados contra el manifest;
- parámetros de path, seguridad, paginación y HTTP 429 gobernados;
- `composer validate` e instalación real de dependencias;
- PHP generado limpio para Pint;
- `php artisan test` dentro de cada solución exportada;
- smoke de rutas Laravel;
- Blank sin rutas API seleccionadas;
- documentación del gate en `docs/delivery/generated-solution-acceptance.md`.

U0.5 cerró con PR #5 y CI post-merge verde. Los vertical slices ejecutables se desarrollan de forma incremental a partir de U0.6.

## U0.6 - Vertical slices ejecutables 🚧

El objetivo es sustituir progresivamente los stubs HTTP 501 por implementaciones completas sin convertir el proyecto exportado en un megaprojecto dormido.

### Checkpoint 1: `products.show` ✅

- `Product` en Domain sin dependencia de Laravel;
- `ProductReadRepository` y `GetProduct` en Application;
- `DatabaseProductReadRepository` y migración SQLite en Infrastructure;
- controlador HTTP real en Presentation;
- binding del puerto únicamente cuando `products.show` está seleccionado;
- respuesta 200 con recurso y 404 mediante Problem Details en español;
- OpenAPI 200/404 y schema `Product`;
- test generado con SQLite en memoria y `RefreshDatabase`;
- ausencia de infraestructura de listado cuando no fue seleccionada.

Checkpoint integrado por PR #6 con CI pre-merge y post-merge verdes.

### Checkpoint 2: `products.list` 🚧

- `ProductListRepository`, `ProductPage` y `ListProducts` en Application;
- `DatabaseProductListRepository` en Infrastructure;
- `ProductsListController` y `ProductListQueryValidator` en Presentation;
- cursor keyset real, no offset codificado como cursor;
- estrategia offset con `page[number]`, `total` y `total_pages`;
- `page[size]` limitado por la gobernanza del manifest;
- filtros permitidos `id` y `name`;
- sorting determinista por `id`/`name`, con `id` como desempate estable;
- 422 RFC 9457 para parámetros inválidos y cursores incompatibles;
- OpenAPI 200/422, `ProductListMeta` y parámetros específicos por estrategia;
- tests generados para cursor, offset, filtering, sorting y validación;
- acceptance Commerce ejecutado en cursor y offset;
- ausencia de infraestructura `products.show` cuando solo se selecciona el listado.

Este checkpoint permanece abierto hasta obtener CI verde completo y merge aprobado.

Los demás endpoints siguen respondiendo 501 hasta recibir su propia receta ejecutable.

Ver `docs/delivery/executable-vertical-slices.md`.

## Destino de entrega

El destino canónico de producción es el ecosistema Eliasworks en `eliasworks.uy`. La automatización de deployment se conecta al runtime provisionado mediante configuración externa y no filtra supuestos del proveedor hacia la arquitectura de aplicación.
