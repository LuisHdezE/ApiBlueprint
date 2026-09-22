#!/usr/bin/env python3

import json
import re
import sys
from pathlib import Path

import yaml
from yaml.constructor import ConstructorError


class UniqueKeyLoader(yaml.SafeLoader):
    pass


def construct_unique_mapping(loader, node, deep=False):
    mapping = {}
    for key_node, value_node in node.value:
        key = loader.construct_object(key_node, deep=deep)
        if key in mapping:
            raise ConstructorError(
                "while constructing a mapping",
                node.start_mark,
                f"found duplicate key ({key})",
                key_node.start_mark,
            )
        mapping[key] = loader.construct_object(value_node, deep=deep)
    return mapping


UniqueKeyLoader.add_constructor(
    yaml.resolver.BaseResolver.DEFAULT_MAPPING_TAG,
    construct_unique_mapping,
)


def fail(message: str) -> None:
    raise AssertionError(message)


def main() -> int:
    if len(sys.argv) != 2:
        print("Usage: validate-generated-openapi.py <generated-project-root>", file=sys.stderr)
        return 2

    project_root = Path(sys.argv[1])
    manifest_path = project_root / ".apiblueprint.json"
    openapi_path = project_root / "openapi" / "openapi.yaml"

    manifest = json.loads(manifest_path.read_text(encoding="utf-8"))
    document = yaml.load(openapi_path.read_text(encoding="utf-8"), Loader=UniqueKeyLoader)

    if not isinstance(document, dict):
        fail("OpenAPI document must be a mapping.")
    if document.get("openapi") != "3.1.0":
        fail("Generated OpenAPI version must be 3.1.0.")

    paths = document.get("paths")
    if not isinstance(paths, dict):
        fail("Generated OpenAPI paths must be a mapping.")

    expected_operations: dict[str, set[str]] = {}
    for endpoint in manifest["endpoints"]:
        expected_operations.setdefault(endpoint["path"], set()).add(endpoint["method"].lower())

    if set(paths.keys()) != set(expected_operations.keys()):
        fail(
            "OpenAPI paths do not match manifest. "
            f"Expected {sorted(expected_operations.keys())}, got {sorted(paths.keys())}."
        )

    for path, path_item in paths.items():
        if not isinstance(path_item, dict):
            fail(f"OpenAPI path item for {path} must be a mapping.")

        actual_methods = {
            key
            for key in path_item.keys()
            if key in {"get", "post", "put", "patch", "delete", "options", "head", "trace"}
        }
        if actual_methods != expected_operations[path]:
            fail(
                f"OpenAPI operations for {path} do not match manifest. "
                f"Expected {sorted(expected_operations[path])}, got {sorted(actual_methods)}."
            )

    governance = manifest["governance"]
    rate_limiting_enabled = governance["rate_limiting"]["enabled"]
    pagination_strategy = governance["pagination"]["strategy"]

    for endpoint in manifest["endpoints"]:
        path = endpoint["path"]
        method = endpoint["method"].lower()
        path_item = paths.get(path)
        if not isinstance(path_item, dict):
            fail(f"Missing OpenAPI path item for {path}.")

        operation = path_item.get(method)
        if not isinstance(operation, dict):
            fail(f"Missing OpenAPI operation {method.upper()} {path}.")

        expected_operation_id = endpoint["id"].replace(".", "_")
        if operation.get("operationId") != expected_operation_id:
            fail(f"Unexpected operationId for {endpoint['id']}.")

        responses = operation.get("responses")
        if not isinstance(responses, dict):
            fail(f"Missing responses for {endpoint['id']}.")

        if rate_limiting_enabled and "429" not in responses:
            fail(f"Rate-limited endpoint {endpoint['id']} must document HTTP 429.")
        if not rate_limiting_enabled and "429" in responses:
            fail(f"Endpoint {endpoint['id']} documents HTTP 429 while rate limiting is disabled.")

        if endpoint["exposure"] != "public" and governance["authentication"] == "sanctum":
            security = operation.get("security")
            if security != [{"bearerAuth": []}]:
                fail(f"Protected endpoint {endpoint['id']} must document bearerAuth.")

        raw_parameters = operation.get("parameters", [])
        if not isinstance(raw_parameters, list):
            fail(f"Parameters for {endpoint['id']} must be a list.")

        by_name = {
            parameter.get("name"): parameter
            for parameter in raw_parameters
            if isinstance(parameter, dict) and isinstance(parameter.get("name"), str)
        }

        for placeholder in re.findall(r"\{([^}]+)\}", path):
            parameter = by_name.get(placeholder)
            if not isinstance(parameter, dict):
                fail(f"Path parameter {placeholder} is missing for {endpoint['id']}.")
            if parameter.get("in") != "path" or parameter.get("required") is not True:
                fail(f"Path parameter {placeholder} for {endpoint['id']} must be required and in path.")
            schema = parameter.get("schema", {})
            if not isinstance(schema, dict) or schema.get("type") != "string":
                fail(f"Path parameter {placeholder} for {endpoint['id']} must use a string schema.")

        if endpoint["id"].endswith(".list"):
            if not raw_parameters:
                fail(f"List endpoint {endpoint['id']} must document query parameters.")

            page_key = "page[cursor]" if pagination_strategy == "cursor" else "page[number]"
            if page_key not in by_name:
                fail(f"List endpoint {endpoint['id']} is missing {page_key}.")

            schema = by_name[page_key].get("schema", {})
            expected_type = "string" if pagination_strategy == "cursor" else "integer"
            if schema.get("type") != expected_type:
                fail(
                    f"{page_key} for {endpoint['id']} must be {expected_type}, "
                    f"got {schema.get('type')}."
                )

    print(f"OpenAPI acceptance PASS: {project_root.name}")
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except (AssertionError, ConstructorError, KeyError, json.JSONDecodeError, yaml.YAMLError) as error:
        print(f"OpenAPI acceptance FAIL: {error}", file=sys.stderr)
        raise SystemExit(1)
