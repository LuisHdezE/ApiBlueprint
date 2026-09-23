# Aceptación de soluciones generadas

U0.5 convierte el ZIP exportado por ApiBlueprint en un artefacto verificable de extremo a extremo. El CI no se limita a comprobar el generador: exporta proyectos Laravel reales, los descomprime y ejecuta sus propias herramientas y pruebas.

BP-ADOPT-003 extiende ese gate para comprobar también los límites de Clean Architecture dentro del artefacto exportado.

## Matriz de aceptación

El gate cubre cuatro presets representativos y cinco casos ejecutables:

| Preset | Perfil de gobierno ejercitado | Propósito |
| --- | --- | --- |
| Blank | defaults | valida una solución sin endpoints seleccionados |
| CRUD | offset + rate limiting deshabilitado | cubre paginación offset y ausencia contractual de HTTP 429 |
| SaaS | defaults | cubre autenticación, RBAC y auditoría |
| Commerce | defaults | cubre una superficie multipropósito con rutas compartidas por recurso |
| Commerce | offset + rate limiting deshabilitado | repite la superficie multipropósito bajo un perfil alternativo de gobierno |

## Gate por solución

Para cada caso, CI ejecuta la siguiente secuencia sobre el ZIP recién generado:

1. exportación mediante el caso de uso real del producto;
2. descompresión en un directorio aislado;
3. verificación de archivos contractuales mínimos;
4. verificación de `tests/Unit/ArchitectureBoundaryTest.php` dentro del ZIP;
5. parseo semántico de `openapi/openapi.yaml` rechazando claves YAML duplicadas;
6. comprobación de que paths y métodos OpenAPI coinciden exactamente con el manifest;
7. verificación de parámetros de path, seguridad, paginación y respuesta 429 según gobierno;
8. `composer validate --strict --no-check-lock`;
9. `composer install` sobre el proyecto generado;
10. creación del entorno local y `APP_KEY`;
11. `vendor/bin/pint --test` sin modificar el artefacto;
12. `php artisan test tests/Unit/ArchitectureBoundaryTest.php` dentro de la solución generada;
13. `php artisan test` para la suite completa del proyecto exportado;
14. smoke del registro de rutas Laravel.

El preset Blank tiene además una comprobación explícita de que `routes/api.php` no contiene declaraciones `Route::`.

## Conformance arquitectónica

El test generado protege la dirección de dependencias de las capas exportadas:

- Domain no puede depender de Application, Infrastructure, Presentation, Providers, Laravel/Illuminate ni Symfony;
- Application no puede depender de Infrastructure, Presentation, Providers, Laravel/Illuminate ni Symfony;
- Infrastructure no puede depender de Presentation;
- Presentation no puede depender de Infrastructure;
- cada archivo PHP bajo una capa debe declarar un namespace consistente con su ubicación.

`app/Providers` se mantiene como composition root y queda fuera de estas restricciones para poder enlazar puertos de Application con adaptadores de Infrastructure.

El contrato completo se documenta en `docs/delivery/generated-architecture-conformance.md`.

## Defectos que este gate convirtió en regresiones

Durante la evolución del gate se han convertido en regresiones defectos que no eran visibles desde los tests internos del generador:

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
- el bootstrap generado emitía un warning por un import global innecesario de `Throwable`;
- una futura fuga de dependencia entre capas queda bloqueada ahora por el test de arquitectura que viaja dentro del ZIP.

## Alcance y límite

Este gate demuestra que la solución exportada es estructuralmente instalable, formateada, enrutable, testeable y conforme con los límites de Clean Architecture definidos para las capas generadas.

Las features marcadas como implementadas/exportables en la Master Feature Library pueden incluir vertical slices ejecutables. Las features planificadas no se presentan como funcionalidad terminada ni deben evadir la gobernanza de exportación.

Los proyectos generados actualmente no incluyen `composer.lock`; durante aceptación, Composer resuelve las restricciones declaradas antes de ejecutar las pruebas. La reproducibilidad del repositorio ApiBlueprint sí permanece gobernada por su `composer.lock` raíz. La estrategia de lock para artefactos exportados se mantiene como una decisión explícita posterior, no como una garantía implícita del gate actual.
