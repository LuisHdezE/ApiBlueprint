# Blueprint Manifest v0.1

The manifest is the canonical description of what an exported API is allowed to contain.

## Baseline rule

An endpoint that is not enabled is not exported as part of the solution contract. Hiding an endpoint from documentation is not equivalent to disabling it.

Each enabled endpoint records:

- stable endpoint id
- capability
- HTTP method
- route path
- exposure profile: `public`, `authenticated`, `admin`, or `internal`

The landing currently exports a `.apiblueprint.json` manifest. A later slice will consume the same manifest to generate the Laravel solution package, route registrations, use-case skeletons, authorization policies, OpenAPI surface and tests.

This keeps the UI, generator and documentation anchored to one contract rather than parallel configuration sources.
