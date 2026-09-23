# U0.4 - Production Runtime Acceptance

Fecha de aceptación: 2026-09-23.

## Alcance

Este documento registra la evidencia de cierre de U0.4 para el runtime público de ApiBlueprint en `https://apiblueprint.eliasworks.uy`.

La aceptación se realizó sobre el release ya promovido mediante `deploy/production`:

- commit desplegado: `b4b9953d5c44384177294b2d60d247e62f322ec9`;
- workflow run: `35669837271`;
- referencia de promoción: `deploy/production`.

No se ejecutó un nuevo deployment para obtener esta evidencia. Se verificó el release que ya se encontraba publicado.

## Bloqueo inicial y causa

La transferencia FTP y el archivo estático `public/release.json` estaban correctos, pero la landing `/` respondía HTTP 500.

El log productivo confirmó `Illuminate\Encryption\MissingAppKeyException` con el mensaje `No application encryption key has been specified.`. El runtime no disponía todavía de una `APP_KEY` productiva persistente.

El workflow excluye deliberadamente `.env` del release. Esto preserva secretos y configuración de runtime, pero exige provisionar el archivo `.env` del servidor antes del primer smoke que atraviesa el stack web de Laravel.

La corrección operativa consistió exclusivamente en provisionar el `.env` persistente de producción según `deployment/production.env.example`, con una `APP_KEY` generada fuera del repositorio. Ningún secreto se registró en Git, en esta evidencia ni en la documentación.

## Smoke público aceptado

### Landing

`GET /`

Resultado: HTTP 200 y contenido HTML de ApiBlueprint.

Esta comprobación confirma conjuntamente DNS, TLS, virtual host, document root, `public/index.php`, bootstrap Laravel y stack web.

### Estado del runtime

`GET /api/v1/meta/status`

Resultado: HTTP 200 con:

```json
{
  "name": "ApiBlueprint",
  "status": "ok",
  "message": "ApiBlueprint está operativo.",
  "api_version": "v1",
  "blueprint_schema": "0.3"
}
```

### Evidencia del release

`GET /release.json`

Resultado: HTTP 200 con:

```json
{
  "application": "ApiBlueprint",
  "commit": "b4b9953d5c44384177294b2d60d247e62f322ec9",
  "promotion_ref": "deploy/production",
  "workflow_run": "35669837271"
}
```

El SHA reportado coincide con el release promovido que se estaba verificando.

### Exportación ZIP

`POST /api/v1/blueprint/export`

Payload: `deployment/smoke-manifest.json` correspondiente al release promovido.

Resultado:

- HTTP 200;
- cuerpo binario con prefijo `PK`;
- ZIP generado correctamente en producción.

La prueba confirma además que la extensión ZIP requerida por el exportador está disponible en el runtime productivo.

## Nota de reproducibilidad en Windows PowerShell 5.1

Para pruebas manuales, un JSON escrito con `Set-Content -Encoding UTF8` puede incluir BOM UTF-8. Ese BOM hizo que una primera prueba manual se interpretara como manifest vacío y devolviera HTTP 422.

La repetición se ejecutó escribiendo el JSON como UTF-8 sin BOM. El cuerpo comenzó con `7B-22-73` (`{"s`) y el export respondió HTTP 200 con firma `PK`.

Este incidente pertenece únicamente al cliente manual de prueba. No fue un defecto del endpoint de producción ni requirió cambios de aplicación.

## Criterio de cierre

U0.4 exige:

- landing pública HTTP 200 con marca ApiBlueprint;
- status público HTTP 200 con `status=ok` y `blueprint_schema=0.3`;
- `release.json` correspondiente al SHA promovido;
- exportación ZIP válida usando el manifest de smoke.

Las cuatro comprobaciones pasaron el 2026-09-23.

**Resultado: U0.4 RUNTIME ACCEPTANCE PASS.**
