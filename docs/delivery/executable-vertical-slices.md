# Vertical slices ejecutables

U0.6 inicia la sustitución incremental de stubs HTTP 501 por implementaciones completas generadas desde la superficie seleccionada.

## Principio de generación

Una receta ejecutable solo puede materializar infraestructura cuando el endpoint correspondiente forma parte del manifest resuelto. Seleccionar un endpoint no relacionado no debe arrastrar entidades, casos de uso, repositorios, migraciones, controladores o tests de otro recurso.

El primer slice gobernado es `products.show` (`GET /api/v1/products/{id}`).

## Archivos generados para `products.show`

Cuando el endpoint está seleccionado, el ZIP incluye:

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

`AppServiceProvider` vincula `ProductReadRepository` con `DatabaseProductReadRepository` únicamente en ese caso.

## Flujo de ejecución

```text
GET /api/v1/products/{id}
        |
        v
ProductsShowController
        |
        v
GetProduct
        |
        v
ProductReadRepository
        ^
        |
DatabaseProductReadRepository
        |
        v
SQLite products
```

Domain y Application no dependen de Laravel. El acceso a base de datos permanece en Infrastructure y la traducción HTTP en Presentation.

## Contrato HTTP

Producto existente:

```json
{
  "data": {
    "id": "product-001",
    "name": "Producto de prueba"
  }
}
```

Respuesta: HTTP 200.

Producto inexistente:

- HTTP 404;
- `Content-Type: application/problem+json`;
- título visible: `Producto no encontrado`;
- tipo de problema: `https://eliasworks.uy/problems/product-not-found`.

OpenAPI documenta respuestas 200 y 404 y el schema `Product`.

## Persistencia y pruebas

La solución generada usa SQLite como baseline local para este slice. El inicio rápido requiere:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan test
```

Los tests generados usan SQLite en memoria y `RefreshDatabase`, insertan un producto de prueba y validan tanto el camino 200 como el 404.

## Ausencia de infraestructura dormida

Si `products.show` no está seleccionado, no se generan su entidad, contrato, caso de uso, repositorio, migración, binding ni test específico.

Si se selecciona `products.show` sin `products.list`, tampoco se genera `ProductsListController` ni `QueryOptionsParser` por causa de este slice.

## Límite del checkpoint

`products.list` continúa como stub 501. Su conversión a slice ejecutable se realizará por separado para reconciliar correctamente:

- paginación cursor;
- paginación offset;
- tamaño máximo de página;
- filtering permitido;
- sorting permitido;
- metadata de navegación en la respuesta;
- OpenAPI y tests para ambas estrategias.

Los demás endpoints permanecen como stubs 501 hasta incorporar su receta ejecutable.
