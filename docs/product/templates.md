# Application Catalog y presets

Los tipos de aplicación son composiciones editables de la Master Feature Library. No poseen implementaciones privadas de login, clientes, productos u otras capacidades compartidas.

## Regla de reutilización

Cada aplicación referencia IDs canónicos de feature. Si `auth.login` ya existe, cualquier aplicación que requiera autenticación reutiliza exactamente esa feature.

No se crean variantes como `commerce.login`, `saas.login` o `booking.login` salvo que representen capacidades semánticamente distintas y hayan pasado por la revisión de reutilización de la biblioteca.

## Catálogo inicial

1. **API en blanco**: composición vacía para seleccionar únicamente las features necesarias.
2. **REST CRUD**: preset inicial de lectura/escritura de clientes.
3. **SaaS inicial**: autenticación, usuarios, roles y auditoría.
4. **Comercio inicial**: autenticación, clientes, productos y pedidos.

Los estados del Application Catalog se calculan desde sus features:

- `ready`: todas las features registradas están implementadas;
- `partial`: cobertura parcialmente implementada;
- `planned`: ninguna de sus features registradas está todavía implementada.

## Alta de nuevos tipos de aplicación

Cuando el análisis de una nueva aplicación identifica un tipo que no existe y se decide trabajar en él, ese tipo se incorpora inmediatamente al Application Catalog, incluso si su cobertura inicial es parcial o planificada.

La fuente de análisis puede incluir imágenes, descripción funcional y contexto adicional. Antes de registrar una feature nueva para esa aplicación, se busca siempre una capacidad equivalente en la Master Feature Library.

## Compatibilidad con el compositor

El modelo canónico usa `applications` y `features`. Mientras el manifest v0.3 y el motor actual continúan evolucionando, ApiBlueprint deriva la colección histórica `templates` desde `applications`.

Seleccionar un preset inicializa la composición. El usuario puede modificar posteriormente las features y perfiles de exposición permitidos antes de exportar.
