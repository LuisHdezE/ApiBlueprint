# Vertical slices ejecutables

U0.6 sustituye incrementalmente stubs HTTP 501 por implementaciones completas generadas únicamente cuando la superficie correspondiente forma parte del manifest resuelto.

## Principio de generación

Una receta ejecutable solo materializa la infraestructura que necesita el endpoint seleccionado. Un endpoint de lectura puntual no debe arrastrar paginación, y un listado no debe generar el caso de uso `show` si no fue pedido.

Los dos primeros slices gobernados pertenecen a Productos:

- `products.show` → `GET /api/v1/products/{id}`;
- `products.list` → `GET /api/v1/products`.

Domain y Application permanecen libres de Laravel. Persistencia vive en Infrastructure y la traducción HTTP en Presentation.

## `products.show`

Cuando está seleccionado, el ZIP genera:

```text
app/
├── Domain/Products/Product.php
├── Application/Products/
│   ├── Contracts/ProductReadRepository.php
│   └── UseCases/GetProduct.php
├── Infrastructure/Products/DatabaseProductReadRepository.php
└── Presentation/Http/Controllers/Generated/ProductsShowController.php

database/
├── database.sqlite
└── migrations/2026_01_01_000000_create_products_table.php

tests/Feature/ProductsShowVerticalSliceTest.php
```

`AppServiceProvider` vincula `ProductReadRepository` con `DatabaseProductReadRepository` únicamente cuando `products.show` está presente.

Contrato HTTP:

- producto existente → HTTP 200 con `{ "data": { ... } }`;
- producto inexistente → HTTP 404, `application/problem+json`, título `Producto no encontrado`;
- OpenAPI declara 200, 404 y schema `Product`.

## `products.list`

Cuando está seleccionado, el ZIP genera la infraestructura compartida de productos y además:

```text
app/
├── Application/Products/
│   ├── Contracts/ProductListRepository.php
│   ├── Data/ProductPage.php
│   └── UseCases/ListProducts.php
├── Infrastructure/Products/DatabaseProductListRepository.php
├── Presentation/Http/Controllers/Generated/ProductsListController.php
└── Presentation/Http/Support/ProductListQueryValidator.php

tests/Feature/ProductsListVerticalSliceTest.php
```

`AppServiceProvider` vincula `ProductListRepository` con `DatabaseProductListRepository` solo cuando `products.list` fue seleccionado.

### Paginación cursor

La estrategia `cursor` usa keyset pagination real. El cursor es opaco, contiene los valores del último registro visible y queda ligado al orden aplicado. No utiliza `OFFSET` disfrazado.

Respuesta:

```json
{
  "data": [
    { "id": "product-002", "name": "Alpha" },
    { "id": "product-004", "name": "Beta" }
  ],
  "meta": {
    "strategy": "cursor",
    "page_size": 2,
    "has_more": true,
    "next_cursor": "..."
  }
}
```

Un cursor malformado o incompatible con el sorting solicitado produce HTTP 422 mediante Problem Details en español.

### Paginación offset

La estrategia `offset` utiliza `page[number]` y `page[size]` y devuelve totales explícitos:

```json
{
  "data": [],
  "meta": {
    "strategy": "offset",
    "page_size": 25,
    "page_number": 1,
    "total": 0,
    "total_pages": 0,
    "has_more": false
  }
}
```

### Filtering y sorting

Cuando filtering está habilitado, Productos acepta:

- `filter[id]`: coincidencia exacta;
- `filter[name]`: coincidencia parcial.

Cuando sorting está habilitado, `sort` acepta `id` y `name`, separados por coma. El prefijo `-` indica orden descendente. No se permiten campos repetidos ni campos desconocidos. Se añade `id` como desempate estable cuando no forma parte del sorting solicitado.

Ejemplos:

```text
GET /api/v1/products?filter[name]=ta&sort=name
GET /api/v1/products?page[size]=20&sort=-name,id
```

Parámetros incompatibles con la estrategia o un sorting inválido producen HTTP 422 con RFC 9457.

## OpenAPI

`products.list` publica:

- HTTP 200 con `data[]` y `ProductListMeta`;
- HTTP 422 para parámetros inválidos;
- HTTP 429 solo cuando rate limiting está habilitado;
- `page[cursor]` o `page[number]` según la estrategia;
- `filter[id]` y `filter[name]` cuando filtering está habilitado;
- contrato de sorting limitado a `id` y `name`.

## Persistencia y pruebas

Si cualquiera de los slices de Productos está seleccionado, el proyecto exportado incluye SQLite y la migración de `products`.

Inicio rápido:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan test
```

Los tests generados usan SQLite en memoria y `RefreshDatabase`. La dependencia `mockery/mockery` se añade a `require-dev` solo cuando existe un slice persistente de Productos.

El acceptance de ApiBlueprint ejecuta Commerce con dos perfiles:

- cursor por defecto;
- offset con rate limiting deshabilitado.

Ambos proyectos exportados deben pasar OpenAPI semántico, Composer, Pint, tests y route smoke.

## Ausencia de infraestructura dormida

- `products.show` sin `products.list` no genera `ProductsListController`, `ProductListRepository`, `ListProducts`, validador ni test de listado.
- `products.list` sin `products.show` no genera `ProductReadRepository`, `GetProduct`, `DatabaseProductReadRepository`, `ProductsShowController` ni su test.
- la entidad `Product`, SQLite y la migración se comparten únicamente cuando al menos uno de los dos slices existe.

Los demás endpoints continúan como stubs HTTP 501 hasta incorporar su propia receta ejecutable.
