# ApiBlueprint

ApiBlueprint is a reusable **Laravel API designer and solution blueprint**. It combines Clean Architecture, editable starter templates, explicit endpoint exposure and an exportable contract that will drive generated Laravel solutions.

## Current executable baseline

- Laravel 13 (`PHP >= 8.3`)
- Clean Architecture folders and dependency rule
- landing-page API configurator
- editable Blank, REST CRUD, SaaS and Commerce templates
- endpoint enable/disable controls
- exposure profiles: Public, Authenticated, Admin, Internal
- `.apiblueprint.json` manifest export
- `GET /api/v1/meta/status`
- `GET /api/v1/blueprint/catalog`
- feature + architecture tests
- GitHub Actions CI

## Core rule

> If an endpoint is not selected by the blueprint, it must not become part of the generated solution contract.

The generator will therefore create only the Laravel routes, presentation adapters, use cases, policies, OpenAPI surface and tests required by the selected manifest.

## Architecture

```text
Presentation  --->  Application  <---  Infrastructure
                         |
                         v
                       Domain
```

Domain and Application are framework-independent. Laravel belongs at the outer boundaries.

## Living documentation

Documentation evolves in the same pull requests as implementation:

- `docs/architecture/clean-architecture.md`
- `docs/product/blueprint-manifest.md`
- `docs/product/templates.md`
- `docs/delivery/roadmap.md`

## Production

Canonical delivery target: **Eliasworks (`eliasworks.uy`)**.
