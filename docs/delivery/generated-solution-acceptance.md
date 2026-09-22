# Aceptación de soluciones generadas

U0.5 convierte el ZIP exportado por ApiBlueprint en un artefacto verificable de extremo a extremo. El CI ya no se limita a comprobar el generador: exporta proyectos Laravel reales, los descomprime y ejecuta sus propias herramientas y pruebas.

## Matriz de aceptación

El gate cubre cuatro presets representativos:

| Preset | Perfil de gobierno ejercitado | Propósito |
| --- | --- | --- |
| Blank | defaults | valida una solución sin endpoints seleccionados |
| CRUD | offset + rate limiting deshabilitado | cubre paginación offset y ausencia contractual de HTTP 429 |
| SaaS | defaults | cubre autenticación, RBAC y auditoría |
| Commerce | defaults | cubre una superficie multipropósito con rutas compartidas por recurso |

## Gate por solución

Para cada preset, CI ejecuta la siguiente secuencia sobre el ZIP recién generado:

1. exportación mediante el caso de uso real del producto;
2. descompresión en un directorio aislado;
3. verificación de archivos contractuales mínimos;
4. parseo semántico de `openapi/openapi.yaml` rechazando claves YAML duplicadas;
5. comprobación de que paths y métodos OpenAPI coinciden exactamente con el manifest;
6. verificación de parámetros de path, seguridad, paginación y respuesta 429 según gobierno;
7. `composer validate --strict --no-check-lock`;
8. `composer install` sobre el proyecto generado;
9. creación del entorno local y `APP_KEY`;
10. `vendor/bin/pint --test` sin modificar el artefacto;
11. `php artisan test` dentro de la solución generada;
12. smoke del registro de rutas Laravel.

El preset Blank tiene además una comprobación explícita de que `routes/api.php` no contiene declaraciones `Route::`.

## Defectos que este gate convirtió en regresiones

Durante la introducción del gate se corrigieron defectos que no eran visibles desde los tests internos del generador:

- `paths:` vacío se serializaba como `null` en OpenAPI para Blank;
- múltiples métodos sobre una misma URI producían claves YAML duplicadas;
- `page[number]` se documentaba como `string` en lugar de `integer` para paginación offset;
- HTTP 429 se documentaba incluso con rate limiting deshabilitado;
- parámetros `{id}` no quedaban declarados como parámetros de path;
- el contrato Blank generaba un DataProvider vacío;
- faltaban directorios escribibles requeridos por Laravel;
- la metadata Composer generada no superaba validación estricta;
- el PHP exportado no nacía limpio para Pint;
- faltaba Collision para disponer de `php artisan test`;
- el bootstrap generado emitía un warning por un import global innecesario de `Throwable`.

## Alcance y límite

Este gate demuestra que la solución exportada es estructuralmente instalable, formateada, enrutable y testeable. Los controladores de negocio continúan siendo stubs HTTP 501 hasta que una fase posterior implemente vertical slices ejecutables. U0.5 no presenta esos stubs como funcionalidad de negocio terminada.

Los proyectos generados actualmente no incluyen `composer.lock`; durante aceptación, Composer resuelve las restricciones declaradas antes de ejecutar las pruebas. La reproducibilidad del repositorio ApiBlueprint sí permanece gobernada por su `composer.lock` raíz. La estrategia de lock para artefactos exportados se mantiene como una decisión explícita posterior, no como una garantía implícita de U0.5.
