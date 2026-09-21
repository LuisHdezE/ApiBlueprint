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

Implementación de release preparada en rama y pendiente de merge/promoción productiva:

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

U0.4 se considera cerrado únicamente después de mergear su PR, promover un SHA aprobado a `deploy/production` y obtener smoke verde en el runtime público.

## Destino de entrega

El destino canónico de producción es el ecosistema Eliasworks en `eliasworks.uy`. La automatización de deployment se conecta al runtime provisionado mediante configuración externa y no filtra supuestos del proveedor hacia la arquitectura de aplicación.
