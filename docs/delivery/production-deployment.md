# Deployment de producción

ApiBlueprint separa **merge** de **promoción a producción**. Un merge a `main` no modifica Eliasworks por sí solo.

## Topología operativa

- repositorio canónico: `LuisHdezE/ApiBlueprint`;
- rama de desarrollo estable: `main`;
- referencia de promoción: `deploy/production`;
- hosting: Eliasworks en Hosting Montevideo;
- transporte: FTP a `ftp.eliasworks.uy:21`;
- usuario FTP: `luis@eliasworks.uy`;
- secreto GitHub requerido: `FTP_PASSWORD`;
- destino remoto: variable `APIBLUEPRINT_FTP_SERVER_DIR`;
- URL pública: variable `APIBLUEPRINT_PUBLIC_URL`.

El directorio remoto debe terminar en `/`. El document root configurado en cPanel debe apuntar a la carpeta `public` dentro de ese directorio de aplicación.

Ejemplo conceptual:

```text
APIBLUEPRINT_FTP_SERVER_DIR = public_html/<directorio-apiblueprint>/
document root               = public_html/<directorio-apiblueprint>/public
```

El valor concreto del directorio y de la URL pública se mantiene fuera del código para no acoplar la arquitectura al proveedor ni inventar rutas de hosting.

## Contrato del entorno

El servidor mantiene su propio `.env`; el workflow nunca lo crea, reemplaza ni elimina. `deployment/production.env.example` documenta el contrato mínimo esperado.

Requisitos principales:

- `APP_ENV=production`;
- `APP_DEBUG=false`;
- `APP_KEY` provisionada fuera del repositorio;
- `APP_URL` igual a la URL pública real;
- PHP compatible con Laravel 13;
- extensión PHP `zip`, necesaria para exportar soluciones;
- `storage` y `bootstrap/cache` escribibles por PHP.

### Provisionamiento inicial

Antes del primer smoke que atraviesa el stack web de Laravel, el directorio raíz de la aplicación debe disponer de un `.env` persistente basado en `deployment/production.env.example`.

La `APP_KEY` debe generarse fuera del repositorio y conservarse como secreto del entorno. No debe copiarse a GitHub, documentación, logs de CI ni comentarios de PR.

El release FTP excluye `.env` de forma deliberada. Esa exclusión evita sobrescribir secretos durante promociones posteriores, pero también significa que el primer deployment no puede fabricar por sí solo una configuración productiva inexistente.

La ausencia de `APP_KEY` puede permitir que endpoints API que no requieren el encrypter respondan correctamente mientras rutas del stack web fallan con `Illuminate\Encryption\MissingAppKeyException`. Por eso el smoke exige validar tanto `/api/v1/meta/status` como la landing `/`.

## Promoción

La promoción se ejecuta moviendo `deploy/production` a un commit aprobado que ya pertenezca a la historia de `main`.

El workflow `.github/workflows/deploy-production.yml` rechaza cualquier SHA que no sea ancestro de `main`. Después:

1. valida que exista `composer.lock`;
2. valida Composer en modo estricto;
3. instala las dependencias bloqueadas;
4. ejecuta Pint, tests y smoke de rutas antes del deploy;
5. reinstala dependencias de producción sin paquetes de desarrollo;
6. construye un directorio de release inmutable;
7. publica el release por FTP;
8. ejecuta smoke contra el runtime público.

La acción FTP está fijada a un commit concreto. `dangerous-clean-slate` permanece desactivado para no borrar archivos de runtime ajenos al release.

## Smoke posterior al deployment

El deployment solo se considera aceptado cuando pasan estas comprobaciones:

- landing pública HTTP 200 y marca `ApiBlueprint`;
- `GET /api/v1/meta/status` devuelve `status=ok` y `blueprint_schema=0.3`;
- `GET /release.json` reporta exactamente el SHA promovido;
- `POST /api/v1/blueprint/export` genera un ZIP válido usando `deployment/smoke-manifest.json`.

La última prueba confirma además que la extensión `zip` funciona realmente en producción.

La evidencia manual o automatizada del smoke debe registrar el SHA validado sin incluir secretos. El cierre inicial de U0.4 se conserva en `docs/delivery/production-runtime-acceptance.md` y `.blueprint/evidence/u0-4-production-runtime.json`.

## Evidencia de release

Cada deployment publica `public/release.json` con:

- aplicación;
- commit desplegado;
- referencia de promoción;
- workflow run;
- fecha UTC de release.

No contiene secretos.

## Rollback

Rollback usa el mismo mecanismo de promoción, no un camino alternativo:

1. identificar un SHA anterior de `main` con CI conocido como verde;
2. obtener aprobación explícita para rollback;
3. mover `deploy/production` a ese SHA;
4. dejar que el mismo workflow reconstruya y redepliegue desde `composer.lock`;
5. exigir nuevamente el smoke completo;
6. verificar que `release.json` coincide con el SHA restaurado.

Mover `deploy/production` hacia atrás requiere actualizar la referencia de forma forzada, pero nunca reescribe `main`.

## Regla de aprobación

Ni crear ni mergear un PR autoriza por sí mismo una mutación de producción. La promoción de `deploy/production` requiere un gate explícito separado.
