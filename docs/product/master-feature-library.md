# Master Feature Library y Application Catalog

ApiBlueprint es una fábrica de APIs compuesta por una biblioteca creciente de funcionalidades reales, probadas y reutilizables. El usuario selecciona desde el compositor las capacidades que necesita y ApiBlueprint construye una nueva solución Laravel con las piezas correspondientes y sus dependencias.

## 1. Invariante principal: una funcionalidad, una implementación canónica

Una funcionalidad no se implementa una vez por cada tipo de aplicación.

Antes de crear una feature nueva se debe buscar una capacidad equivalente en la **Master Feature Library**:

1. si ya existe y cubre la necesidad, se reutiliza;
2. si existe pero la necesidad amplía razonablemente su contrato, se evoluciona la feature canónica sin duplicarla;
3. solo una capacidad semánticamente distinta recibe un nuevo identificador e implementación.

Ejemplo: `auth.login` pertenece a ApiBlueprint. Commerce, SaaS, CRM, Booking y cualquier aplicación futura referencian el mismo `auth.login`; no existen implementaciones paralelas como `commerce.login` o `crm.login`.

Las aplicaciones son composiciones de IDs de feature. No son propietarias del código de esas features.

## 2. Application Catalog

El **Application Catalog** registra los tipos de solución que ApiBlueprint conoce y sirve como conjunto de presets vivos del compositor.

Cuando se identifica un tipo de aplicación que todavía no existe y se decide trabajar en él, ese tipo de aplicación se registra **inmediatamente** en el catálogo, aunque su cobertura funcional todavía sea parcial.

Estados de aplicación:

- `ready`: todas sus features registradas están implementadas;
- `partial`: al menos una feature está implementada, pero aún faltan otras;
- `planned`: el tipo de aplicación está registrado, pero sus features todavía están pendientes.

El progreso de una aplicación se calcula a partir del estado de sus features y no mediante una lista paralela de progreso.

## 3. Master Feature Library

Cada feature canónica tiene como mínimo:

- identificador estable;
- capability o módulo funcional;
- etiqueta visible;
- resumen;
- método HTTP;
- ruta canónica;
- exposición predeterminada;
- estado de implementación;
- disponibilidad para exportación;
- disponibilidad en OpenAPI/Swagger;
- cobertura de tests;
- aplicaciones que la reutilizan.

Estados iniciales de feature:

- `implemented`: implementación ejecutable y gobernada;
- `in_progress`: trabajo iniciado pero todavía no disponible como pieza terminada;
- `planned`: registrada, pero aún no implementada.

Las primeras features ejecutables de la biblioteca son:

- `products.list`;
- `products.show`;
- `auth.login`;
- `auth.logout`;
- `users.list`.

`auth.login` y `auth.logout` forman el ciclo mínimo canónico de autenticación. `auth.login` es una única feature compartida por SaaS, Commerce y cualquier aplicación futura que necesite autenticación. Exporta usuario e identidad mínimos reutilizables, un puerto de Application, adaptador Laravel Sanctum, validación HTTP, migraciones, contrato OpenAPI y tests reales de emisión de token. No se crea un login independiente por tipo de aplicación.

Cuando una composición selecciona `auth.login`, la estrategia `authentication=none` se reconcilia a `sanctum` y el ajuste queda registrado en el manifest resuelto. `auth.logout` depende de `auth.login` y revoca únicamente el token Sanctum actual, reutilizando la misma identidad, tabla de usuarios y almacenamiento de tokens.

`users.list` reutiliza esa misma identidad y el gate `admin-api`. La paginación cursor/offset y la validación estructural de listados son infraestructura compartida con `products.list`; una nueva feature de listado no debe copiar esos algoritmos.

## 4. Ingreso de nuevas aplicaciones mediante imagen + descripción

Durante el desarrollo, una aplicación externa puede entrar al proceso mediante una o más imágenes acompañadas normalmente por una descripción funcional.

El análisis combina:

- lo observable en la interfaz;
- la descripción suministrada;
- el contexto funcional disponible;
- la Master Feature Library existente.

La inferencia se separa en tres niveles:

- **observado**: comportamiento o información visible de forma explícita;
- **inferido**: capacidad backend razonablemente necesaria para soportar lo observado y descrito;
- **por definir**: información que la evidencia no permite decidir de forma segura.

Antes de convertir una capacidad inferida en una feature nueva, se ejecuta siempre la comparación contra la biblioteca para evitar duplicación funcional.

Si del análisis surge un tipo de aplicación nuevo y se decide desarrollarlo, su registro en el Application Catalog forma parte del primer cambio del trabajo, no de una fase posterior.

## 5. Tres proyecciones sincronizadas

La biblioteca canónica alimenta permanentemente tres vistas del mismo conocimiento:

### Compositor / Landing

Permite seleccionar un tipo de aplicación y las features que formarán una nueva solución exportada.

### Administración del catálogo

`/catalogo` muestra tipos de aplicación, cobertura, features, madurez, exportabilidad, Swagger, tests y reutilización entre aplicaciones.

### Swagger vivo

`/swagger` consume `GET /api/v1/blueprint/openapi` y muestra las features implementadas cuyo contrato OpenAPI está listo.

Swagger no es documentación de cierre: se actualiza en el mismo cambio que termina una feature. `auth.login` publica su request body, respuesta con token Bearer y errores 401/422 desde el mismo checkpoint que lo vuelve ejecutable. `users.list` publica filtros, sorting, paginación, esquema seguro de usuario y errores 401/403/422 en el mismo cambio que lo incorpora a la biblioteca.

## 6. Compatibilidad con el motor actual

Desde U0.7, `applications` y `features` son la fuente canónica. Las colecciones históricas `templates` y `endpoints` continúan disponibles como proyecciones de compatibilidad para el resolver y el exportador existentes.

Esto permite evolucionar el modelo sin romper el manifest v0.3 ni las soluciones de aceptación actuales.

## 7. Gate de feature terminada

Una feature no debe considerarse terminada únicamente porque exista un controlador.

El gate exige que la misma feature quede reconciliada en:

- implementación ejecutable;
- dependencias y composición;
- tests;
- OpenAPI/Swagger;
- Master Feature Library;
- Application Catalog cuando corresponda;
- compositor/exportador;
- documentación viva.

La aplicación estricta de `exportable=false` en el resolver/compositor se realizará de forma gobernada después de verificar compatibilidad con los presets y el acceptance de soluciones generadas.
