#!/usr/bin/env bash
#
# Helper script to build and release a new version of changelog-helper.
# Walks through the steps documented in README.md "Build & release new version".

set -euo pipefail

CHANGELOG="./CHANGELOG.md"
HELPER="./changelog-helper"
BUILD_PATH="./builds/changelog-helper"

if [ ! -x "$HELPER" ]; then
    echo "Error: $HELPER not found or not executable." >&2
    exit 1
fi

if [ ! -f "$CHANGELOG" ]; then
    echo "Error: $CHANGELOG not found." >&2
    exit 1
fi

# Read the latest released version from CHANGELOG.md (first "## [X.Y.Z]" entry).
latest=$(grep -E '^## \[[0-9]+\.[0-9]+\.[0-9]+\]' "$CHANGELOG" | head -n1 \
    | sed -E 's/^## \[([0-9]+\.[0-9]+\.[0-9]+)\].*/\1/' || true)

if [ -z "${latest:-}" ]; then
    latest="0.0.0"
fi

IFS='.' read -r CUR_MAJOR CUR_MINOR CUR_PATCH <<< "$latest"

next_patch="${CUR_MAJOR}.${CUR_MINOR}.$((CUR_PATCH + 1))"
next_minor="${CUR_MAJOR}.$((CUR_MINOR + 1)).0"
next_major="$((CUR_MAJOR + 1)).0.0"

echo "Latest version: $latest"
echo
echo "Select the next version:"
echo "  1) patch  -> $next_patch"
echo "  2) minor  -> $next_minor"
echo "  3) major  -> $next_major"
echo "  4) custom"
echo "  5) quit"
echo

read -r -p "Choice [2]: " choice
choice="${choice:-2}"

case "$choice" in
    1) level="patch"; new_version="$next_patch" ;;
    2) level="minor"; new_version="$next_minor" ;;
    3) level="major"; new_version="$next_major" ;;
    4)
        read -r -p "Enter custom version (X.Y.Z): " new_version
        if [[ ! "$new_version" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
            echo "Error: invalid version '$new_version'." >&2
            exit 1
        fi
        IFS='.' read -r N_MAJOR N_MINOR N_PATCH <<< "$new_version"
        if   [ "$N_MAJOR" -gt "$CUR_MAJOR" ]; then level="major"
        elif [ "$N_MINOR" -gt "$CUR_MINOR" ]; then level="minor"
        else                                       level="patch"
        fi
        ;;
    5) echo "Aborted."; exit 0 ;;
    *) echo "Invalid choice." >&2; exit 1 ;;
esac

echo
echo "About to release: $latest -> $new_version ($level)"
read -r -p "Continue? [y/N]: " confirm
if [[ ! "$confirm" =~ ^[Yy]$ ]]; then
    echo "Aborted."
    exit 0
fi

run() {
    echo "+ $*"
    "$@"
}

run "$HELPER" app:build changelog-helper --build-version="$new_version"
run git add "$BUILD_PATH"
run "$HELPER" add added "Added new release build for $new_version"
run git add "$BUILD_PATH"
run git add "$CHANGELOG"
run "$HELPER" release "$level" 1

echo
read -r -p "Push commits and tags to origin now? [y/N]: " push_confirm
if [[ "$push_confirm" =~ ^[Yy]$ ]]; then
    run git push
    run git push --tags
else
    echo "Skipped 'git push'. Run it manually when ready."
fi

echo
echo "Release $new_version complete."
