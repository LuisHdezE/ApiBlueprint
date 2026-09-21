# Blueprint Manifest v0.2

El manifest es la descripción canónica de lo que una API exportada puede contener.

## Regla principal

Un endpoint no seleccionado no forma parte de la solución exportada. Ocultarlo de Swagger no equivale a deshabilitarlo.

## Validación del lado servidor

El navegador envía únicamente intención de configuración. Antes de exportar, ApiBlueprint vuelve a validar el manifest y reconstruye `method`, `path`, `capability` y descripciones desde el catálogo canónico. Los valores manipulados por el cliente no se consideran fuente de verdad.

Cada endpoint resuelto contiene:

- identificador estable;
- capability técnica y etiqueta visible;
- resumen en español;
- método HTTP y ruta canónica;
- perfil de exposición: `public`, `authenticated`, `admin` o `internal`;
- indicador `auto_added` cuando fue requerido como dependencia.

## Dependencias

Los perfiles protegidos pueden exigir endpoints de autenticación. También existen dependencias explícitas entre endpoints. El resolvedor agrega las dependencias obligatorias antes de exportar y las registra en `resolution.auto_added` para que nunca aparezcan de forma invisible.

## Exportación

`POST /api/v1/blueprint/export` genera un ZIP Laravel que contiene únicamente la superficie resuelta, incluyendo:

- `.apiblueprint.json`;
- `routes/api.php`;
- controladores stub para los endpoints exportados;
- contrato OpenAPI en español;
- test de registro de rutas;
- estructura inicial de Clean Architecture.

Los endpoints generados responden inicialmente con HTTP 501 hasta implementar su caso de uso real.
