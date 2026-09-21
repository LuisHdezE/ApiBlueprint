# Clean Architecture baseline

ApiBlueprint starts with four explicit boundaries and grows them only when executable behavior requires it.

## Dependency rule

`Presentation -> Application <- Infrastructure`, while `Domain` remains the innermost business layer. Domain and Application must not depend on Laravel or HTTP/persistence details.

- **Domain**: entities, value objects, domain services, policies and domain events.
- **Application**: use cases, commands/queries, DTOs and ports/contracts.
- **Infrastructure**: Laravel/database/external-system adapters implementing Application ports.
- **Presentation**: HTTP controllers, requests/resources and other delivery adapters.

The first catalog flow already follows the rule: `BlueprintCatalogController -> GetBlueprintCatalog -> BlueprintCatalog <- ConfigBlueprintCatalog`.

Architecture tests guard framework independence for Domain and Application from the first pull request.
