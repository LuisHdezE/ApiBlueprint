# BP-ADOPT-006 — Maintainability hardening

## Objetivo

BP-ADOPT-006 reduce el hotspot de mantenibilidad del exportador ZIP sin cambiar el comportamiento exportado. El checkpoint adopta recetas registrables para las vertical slices y soportes compartidos que antes vivían dentro de `LaravelZipBlueprintExporter`.

La condición de éxito es estricta: el refactor solo es aceptable si los cinco perfiles de generated-solution acceptance conservan exactamente las mismas rutas y bytes de archivo que la baseline previa al cambio.

## Baseline previa

La baseline se capturó desde `main` en `aaafa404995204683f5be14d90aa8779e8ba97be`, antes de mover las plantillas:

- `LaravelZipBlueprintExporter.php`: 3423 líneas / 121318 bytes;
- `blank:default`: 39 archivos;
- `crud:offset-no-rate`: 56 archivos;
- `saas:default`: 81 archivos;
- `commerce:default`: 73 archivos;
- `commerce:offset-no-rate`: 73 archivos;
- revisión semántica conjunta: `8819e039a03093579f2eff02cc751f60dab496a535889e38bcbcfb3ab498f80d`.

La baseline versionada vive en `.blueprint/export-behavior-baseline.json`. Los timestamps y metadata interna del ZIP se excluyen del fingerprint; la comparación usa únicamente path relativo + SHA-256 del contenido de cada archivo.

## Arquitectura resultante

`LaravelZipBlueprintExporter` conserva la orquestación transversal del ZIP y las capacidades de governance. La selección de artefactos de vertical slices se delega a `LaravelFeatureRecipeRegistry`.

Las recetas registradas son:
1. `auth.login`;
2. `auth.logout`;
3. `support.users`;
4. `users.list`;
5. `users.show`;
6. `users.create`;
7. `support.products`;
8. `products.show`;
9. `products.list`;
10. `support.list-query`.

Cada receta puede aportar archivos, controller generado, imports/bindings del Service Provider y dependencias `require-dev`. La registry rechaza IDs duplicados, colisiones de archivos con contenido distinto, múltiples controllers para un mismo endpoint y constraints Composer incompatibles.

`support.list-query` conserva una semántica histórica deliberada: `QueryOptions` y `QueryOptionsParser` se exportan para cualquier endpoint cuyo ID termine en `.list`; `DatabaseQueryPaginator` y `ListQueryValidator` solo se exportan cuando existe `users.list` o `products.list`. Esta diferencia quedó protegida por la baseline después de que el primer refactor intentara estrecharla accidentalmente.

## Resultado de mantenibilidad

Después de la extracción, `LaravelZipBlueprintExporter.php` queda en 1155 líneas / 50466 bytes. Las plantillas de las vertical slices se trasladaron literalmente a recipes; 51 métodos movidos fueron comparados contra el blob original durante el refactor antes de ejecutar CI.

El objetivo no es minimizar líneas totales del repositorio. El objetivo es separar responsabilidades y hacer registrable la incorporación de features sin volver a crecer un único exportador central.

## QA ejecutable

CI ejecuta `scripts/export-behavior-fingerprint.py verify .blueprint/export-behavior-baseline.json` antes del generated-solution acceptance. Un path añadido, eliminado o con bytes distintos hace fallar el gate e informa el caso y archivo exactos.
CI #133 (`35939177086`) pasó sobre el refactor con:

- Pint;
- 40 root tests / 936 assertions;
- API evolution guard + self-test;
- generated export behavior parity;
- route contract smoke;
- generated solution acceptance;
- Operational Postman contract;
- evidence upload.

La paridad final vuelve a la revisión congelada `8819e039a03093579f2eff02cc751f60dab496a535889e38bcbcfb3ab498f80d` para los cinco perfiles.

## No-claims

BP-ADOPT-006 no:

- añade endpoints o cambia el Master OpenAPI;
- cambia manifest, auth, RBAC, idempotency o audit semantics;
- modifica el contrato de consumidores;
- desbloquea `architecture_ready`;
- cierra Domain/ADR/security/threat-model/audit-retention;
- promueve ni modifica producción.

Los bloqueos independientes permanecen gobernados por `.blueprint/status.yaml`.
