# BP-ADOPT-003 - Generated architecture conformance

BP-ADOPT-003 extiende la garantía de Clean Architecture desde el repositorio generador hacia cada API exportada por ApiBlueprint.

## Objetivo

Una solución generada debe conservar los límites arquitectónicos después de abandonar el repositorio ApiBlueprint. El ZIP exportado incluye por tanto un test PHPUnit ejecutable en:

`tests/Unit/ArchitectureBoundaryTest.php`

El test forma parte del propio artefacto generado y se ejecuta dentro de cada solución durante `scripts/accept-generated-solutions.sh`.

## Implementación

`LaravelZipBlueprintExporter` continúa siendo el generador base. Para evitar refactorizar el exporter monolítico dentro de este checkpoint, `ArchitectureConformanceBlueprintExporter` lo decora y añade exclusivamente el test de arquitectura al ZIP final.

La composición se realiza en `AppServiceProvider`; Application continúa dependiendo únicamente del puerto `BlueprintExporter`.

No se agregan endpoints, no cambia el manifest, no cambia OpenAPI y no cambia comportamiento HTTP de las features.

## Reglas ejecutables

El test generado recorre archivos PHP de las cuatro capas principales y aplica estas restricciones:

| Capa | Dependencias prohibidas |
| --- | --- |
| Domain | Laravel/Illuminate, Symfony, Application, Infrastructure, Presentation y Providers |
| Application | Laravel/Illuminate, Symfony, Infrastructure, Presentation y Providers |
| Infrastructure | Presentation |
| Presentation | Infrastructure |

Además, cada archivo PHP encontrado dentro de una capa debe declarar un namespace consistente con esa capa.

`app/Providers` permanece fuera de estas restricciones porque funciona como composition root y puede enlazar puertos con adaptadores concretos.

## Acceptance

La matriz existente sigue cubriendo:

- Blank / default;
- CRUD / offset-no-rate;
- SaaS / default;
- Commerce / default;
- Commerce / offset-no-rate.

Para cada caso, el gate ahora exige que `tests/Unit/ArchitectureBoundaryTest.php` exista en el ZIP y ejecuta:

```text
php artisan test tests/Unit/ArchitectureBoundaryTest.php
```

antes de ejecutar la suite completa con `php artisan test`.

Esto convierte una fuga de dependencias hacia una capa exterior o hacia Laravel/Symfony desde Domain/Application en una regresión de CI del generador.

## QA de generación

`tests/Feature/GeneratedArchitectureBoundaryExportTest.php` verifica desde el repositorio raíz que el ZIP publicado contiene el test ejecutable y las reglas de dependencia esperadas.

El acceptance generado constituye la prueba de ejecución real del mismo test dentro de los proyectos exportados.

## Frontera del checkpoint

BP-ADOPT-003 cierra únicamente el gap de conformance arquitectónica de los consumidores generados.

No modifica los gaps independientes que mantienen bloqueado `architecture_ready` en Blueprint 0.5.4: modelo de dominio formal, ADRs, security/threat model, audit retention y política explícita de versionado/compatibilidad.

No se realiza deployment de producción como parte de este checkpoint.
