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

## U0.3 - Capacidades gobernadas de la API

- Problem Details RFC 9457 global
- estrategia de autenticación
- autorización/RBAC
- correlación de requests
- rate limiting
- contratos de paginación, filtrado y ordenamiento
- base de idempotencia
- capability de auditoría

## U0.4 - Entrega continua y runtime público

- pipeline de deployment desde `main`
- publicación del configurador en Eliasworks
- smoke tests posteriores al deployment
- rollback/documentación de operación

## Destino de entrega

El destino canónico de producción es el ecosistema Eliasworks en `eliasworks.uy`. La automatización de deployment se conectará al runtime ya provisionado para ApiBlueprint sin filtrar supuestos del proveedor hacia la arquitectura de aplicación.
