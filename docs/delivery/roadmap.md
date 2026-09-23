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

U0.5 cerró con PR #5 y CI post-merge verde.

## U0.6 - Primeras features ejecutables reutilizables ✅

U0.6 demostró que una feature seleccionada puede materializarse como vertical slice real sin generar infraestructura de otra feature no seleccionada.

### `products.show` ✅

- `Product` en Domain sin dependencia de Laravel;
- `ProductReadRepository` y `GetProduct` en Application;
- `DatabaseProductReadRepository` y migración SQLite en Infrastructure;
- controlador HTTP real en Presentation;
- binding del puerto únicamente cuando `products.show` está seleccionado;
- respuesta 200 con recurso y 404 mediante Problem Details en español;
- OpenAPI 200/404 y schema `Product`;
- test generado con SQLite en memoria y `RefreshDatabase`;
- ausencia de infraestructura de listado cuando no fue seleccionada.

Integrado por PR #6 con CI pre-merge y post-merge verdes.

### `products.list` ✅

- `ProductListRepository`, `ProductPage` y `ListProducts` en Application;
- `DatabaseProductListRepository` en Infrastructure;
- `ProductsListController` y `ProductListQueryValidator` en Presentation;
- cursor keyset real;
- estrategia offset con `page[number]`, `total` y `total_pages`;
- `page[size]` gobernado;
- filtros permitidos `id` y `name`;
- sorting determinista por `id`/`name`, con `id` como desempate estable;
- 422 RFC 9457 para parámetros inválidos y cursores incompatibles;
- OpenAPI 200/422;
- tests generados para cursor, offset, filtering, sorting y validación;
- acceptance Commerce ejecutado en cursor y offset;
- ausencia de infraestructura `products.show` cuando solo se selecciona el listado.

Integrado por PR #7 con CI verde.

Estas dos implementaciones constituyeron las primeras features reales de la biblioteca maestra.

## U0.7 - Master Feature Library + Application Catalog ✅

U0.7 formalizó el modelo de producto que gobierna el crecimiento de ApiBlueprint.

### Reglas de producto

- una funcionalidad se implementa una sola vez y se reutiliza entre aplicaciones;
- antes de crear una feature se busca una equivalente existente;
- una feature parcialmente compatible se evoluciona antes de considerar una duplicación;
- un tipo de aplicación nuevo se registra inmediatamente cuando se decide trabajarlo;
- las aplicaciones pueden tener cobertura `ready`, `partial` o `planned`;
- imagen + descripción + contexto funcional forman la entrada habitual para analizar nuevas aplicaciones;
- la inferencia separa lo observado, lo inferido y lo pendiente de definición;
- Catálogo administrativo, Swagger y Compositor son proyecciones sincronizadas de la misma fuente canónica.

### Catalog Foundation ✅

- `features` como Master Feature Library canónica;
- `applications` como Application Catalog canónico;
- `templates` y `endpoints` derivados como proyecciones compatibles con manifest v0.3;
- estado de implementación, exportabilidad, OpenAPI y tests por feature;
- cobertura de aplicación calculada desde sus features;
- asociación inversa feature → aplicaciones reutilizadoras;
- test que prohíbe IDs de feature duplicados y referencias inexistentes;
- administración visual en `/catalogo`;
- OpenAPI vivo en `GET /api/v1/blueprint/openapi`;
- Swagger UI en `/swagger` mostrando solo features implementadas y OpenAPI-ready;
- documentación en `docs/product/master-feature-library.md`.

Integrado por PR #8 con CI pre-merge y post-merge verdes.

## U0.8 - Authentication Library 🚧

Objetivo: comenzar a ampliar la biblioteca sobre la fundación U0.7 con una capacidad transversal de alta reutilización y una sola implementación canónica compartida por todas las aplicaciones.

### Checkpoint 1 - `auth.login` ✅

- una única feature `auth.login` reutilizada por SaaS, Commerce y aplicaciones futuras;
- `AuthenticationGateway` como puerto de Application;
- `LoginUser` como caso de uso independiente de Laravel;
- `SanctumAuthenticationGateway` como adaptador de Infrastructure;
- identidad mínima compartida mediante `User` reutilizable;
- migraciones de `users` y `personal_access_tokens` generadas únicamente cuando la composición las necesita;
- emisión de token Laravel Sanctum real;
- validación de email, password y device name en español;
- 200 con usuario y token Bearer;
- 401 RFC 9457 para credenciales inválidas;
- 422 RFC 9457 para request inválido;
- `authentication=none` reconciliado a `sanctum` cuando se selecciona `auth.login`;
- OpenAPI/Swagger actualizado en el mismo checkpoint;
- catálogo administrativo actualizado automáticamente desde el estado canónico de la feature;
- test raíz que verifica el contenido del ZIP sin infraestructura de Products;
- acceptance SaaS y Commerce ejecutando exactamente la misma receta de login.

### Checkpoint 2 - `auth.logout` 🚧

- una única feature `auth.logout` reutilizable por cualquier aplicación;
- dependencia explícita de `auth.login`, sin identidad ni migraciones duplicadas;
- revocación exclusiva del token Sanctum actual;
- `TokenRevocationGateway` y `LogoutUser` independientes de Laravel;
- `SanctumTokenRevocationGateway` como adaptador;
- endpoint protegido con 204 y 401 documentados;
- login/logout excluidos de idempotency key;
- Swagger, catálogo y tests actualizados en el mismo checkpoint;
- acceptance ejecutando login → logout → token revocado.

### Siguientes checkpoints previstos
- hacer que el compositor distinga claramente features implementadas, en desarrollo y pendientes;
- impedir de forma gobernada exportar features no implementadas sin romper el acceptance histórico;
- desacoplar las recetas de feature del exporter monolítico y convertirlas en unidades registrables;
- continuar incorporando nuevas aplicaciones y features sin duplicación funcional.

## Destino de entrega

El destino canónico de producción es el ecosistema Eliasworks en `eliasworks.uy`. La automatización de deployment se conecta al runtime provisionado mediante configuración externa y no filtra supuestos del proveedor hacia la arquitectura de aplicación.
