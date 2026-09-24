#!/usr/bin/env python3
from __future__ import annotations

import argparse
import hashlib
import json
import subprocess
import sys
import tempfile
import zipfile
from pathlib import Path

CASES = (
    ("blank", "default"),
    ("crud", "offset-no-rate"),
    ("saas", "default"),
    ("commerce", "default"),
    ("commerce", "offset-no-rate"),
)


def stable_hash(value: object) -> str:
    encoded = json.dumps(value, ensure_ascii=False, sort_keys=True, separators=(",", ":")).encode("utf-8")
    return hashlib.sha256(encoded).hexdigest()


def export_case(root: Path, work: Path, template: str, profile: str) -> dict[str, object]:
    archive = work / f"{template}-{profile}.zip"
    subprocess.run(
        ["php", str(root / "scripts" / "export-template.php"), template, str(archive), profile],
        cwd=root,
        check=True,
        stdout=subprocess.DEVNULL,
    )

    files: dict[str, str] = {}
    with zipfile.ZipFile(archive) as generated:
        members = [name for name in generated.namelist() if not name.endswith("/")]
        roots = {name.split("/", 1)[0] for name in members if "/" in name}
        if len(roots) != 1:
            raise RuntimeError(f"Expected exactly one generated project root for {template}:{profile}, got {sorted(roots)}")

        project_root = next(iter(roots)) + "/"
        for name in sorted(members):
            if not name.startswith(project_root):
                raise RuntimeError(f"Archive member is outside generated root: {name}")
            relative = name[len(project_root):]
            files[relative] = hashlib.sha256(generated.read(name)).hexdigest()

    return {
        "template": template,
        "profile": profile,
        "file_count": len(files),
        "revision": stable_hash(files),
        "files": files,
    }


def current_snapshot(root: Path, source_revision: str) -> dict[str, object]:
    with tempfile.TemporaryDirectory(prefix="apiblueprint-export-parity-") as tmp:
        work = Path(tmp)
        cases = [export_case(root, work, template, profile) for template, profile in CASES]

    return {
        "schema_version": "1.0",
        "source_revision": source_revision,
        "case_count": len(cases),
        "revision": stable_hash({f"{case['template']}:{case['profile']}": case["revision"] for case in cases}),
        "cases": cases,
    }


def by_case(snapshot: dict[str, object]) -> dict[str, dict[str, object]]:
    return {
        f"{case['template']}:{case['profile']}": case
        for case in snapshot.get("cases", [])
        if isinstance(case, dict)
    }


def verify(baseline: dict[str, object], current: dict[str, object]) -> int:
    expected_cases = by_case(baseline)
    actual_cases = by_case(current)
    errors: list[str] = []

    if set(expected_cases) != set(actual_cases):
        errors.append(
            "Case set changed: "
            f"missing={sorted(set(expected_cases) - set(actual_cases))}, "
            f"added={sorted(set(actual_cases) - set(expected_cases))}"
        )

    for case_id in sorted(set(expected_cases) & set(actual_cases)):
        expected = expected_cases[case_id]
        actual = actual_cases[case_id]
        expected_files = expected.get("files", {})
        actual_files = actual.get("files", {})
        if not isinstance(expected_files, dict) or not isinstance(actual_files, dict):
            errors.append(f"{case_id}: invalid file fingerprint map")
            continue

        missing = sorted(set(expected_files) - set(actual_files))
        added = sorted(set(actual_files) - set(expected_files))
        changed = sorted(
            path
            for path in set(expected_files) & set(actual_files)
            if expected_files[path] != actual_files[path]
        )
        if missing or added or changed:
            errors.append(f"{case_id}: missing={missing}, added={added}, changed={changed}")

    if errors:
        print("Generated export behavior drift detected:", file=sys.stderr)
        for error in errors:
            print(f"- {error}", file=sys.stderr)
        return 1

    print(
        "Generated export behavior parity: PASS "
        f"({len(actual_cases)} cases, revision={current['revision']})"
    )
    return 0


def main() -> int:
    parser = argparse.ArgumentParser()
    subparsers = parser.add_subparsers(dest="command", required=True)

    capture = subparsers.add_parser("capture")
    capture.add_argument("output", type=Path)
    capture.add_argument("source_revision")

    check = subparsers.add_parser("verify")
    check.add_argument("baseline", type=Path)

    args = parser.parse_args()
    root = Path(__file__).resolve().parents[1]

    if args.command == "capture":
        snapshot = current_snapshot(root, args.source_revision)
        args.output.parent.mkdir(parents=True, exist_ok=True)
        args.output.write_text(json.dumps(snapshot, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
        print(f"Captured generated export behavior revision {snapshot['revision']}")
        return 0

    baseline = json.loads(args.baseline.read_text(encoding="utf-8"))
    current = current_snapshot(root, "current")
    return verify(baseline, current)


if __name__ == "__main__":
    raise SystemExit(main())
