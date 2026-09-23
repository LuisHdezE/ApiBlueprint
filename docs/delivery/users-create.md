# `users.create` canónico

`users.create` amplía la Users Library sin crear una segunda identidad, tabla de usuarios, autenticación o RBAC. Reutiliza `User`, `UserData`, Sanctum, `admin-api`, Problem Details e idempotencia.

## Contrato

- `POST /api/v1/users`
- exposición `admin`
- request: `name`, `email`, `password`, `role` opcional
- `role` por defecto: `user`
- roles admitidos actualmente: `user`, `admin`
- 201: `UserData` sin password
- 401/403/422 gobernados
- email único y password hasheado

## Gate

Catálogo, Swagger maestro, OpenAPI generado, test raíz de exportación y acceptance SaaS deben permanecer sincronizados y verdes.
