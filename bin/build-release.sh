#!/usr/bin/env bash
set -euo pipefail

THEME_SLUG="ajnanda"
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
RELEASES_DIR="$ROOT_DIR/releases"
STYLE_FILE="$ROOT_DIR/style.css"
FUNCTIONS_FILE="$ROOT_DIR/functions.php"

usage() {
  cat <<'USAGE'
Usage:
  ./bin/build-release.sh
  ./bin/build-release.sh [options]

Default behavior:
  Shows a release menu. Press Enter for patch.

Options:
  --version X.Y.Z       Set theme version before packaging
  --bump patch          Increment patch version before packaging
  --bump minor          Increment minor version before packaging
  --bump major          Increment major version before packaging
  --no-bump             Package current version without changing it
  --slug NAME           ZIP folder/package slug, default: ajnanda
  --root PATH           Theme root path, default: parent of this script's bin folder
  --help                Show this help

Creates:
  releases/<slug>-<version>.zip
  releases/<slug>.zip

Version sources updated:
  style.css header: Version: X.Y.Z
  functions.php: define('AJNANDA_THEME_VERSION', 'X.Y.Z'); if present
USAGE
}

get_version() {
  awk -F': *' 'tolower($1)=="version" {print $2; exit}' "$STYLE_FILE" | tr -d '\r'
}

validate_version() {
  local version="$1"
  if [[ ! "$version" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo "Error: version must use X.Y.Z format, example 1.0.1" >&2
    exit 1
  fi
}

bump_version() {
  local current="$1"
  local part="$2"
  local major minor patch
  IFS='.' read -r major minor patch <<< "$current"

  case "$part" in
    patch) patch=$((patch + 1)) ;;
    minor) minor=$((minor + 1)); patch=0 ;;
    major) major=$((major + 1)); minor=0; patch=0 ;;
    *) echo "Error: bump must be patch, minor, or major" >&2; exit 1 ;;
  esac

  echo "$major.$minor.$patch"
}

set_version() {
  local version="$1"
  validate_version "$version"

  perl -0pi -e "s/Version:\s*[0-9]+\.[0-9]+\.[0-9]+/Version: $version/" "$STYLE_FILE"

  if grep -q "AJNANDA_THEME_VERSION" "$FUNCTIONS_FILE"; then
    perl -0pi -e "s/define\(\s*'AJNANDA_THEME_VERSION'\s*,\s*'[^']+'\s*\);/define('AJNANDA_THEME_VERSION', '$version');/" "$FUNCTIONS_FILE"
  fi

  perl -0pi -e "s/(wp_enqueue_style\(\s*'ncllc-pro-style'[^;]*,\s*)'[0-9]+\.[0-9]+\.[0-9]+'/\${1}'$version'/g" "$FUNCTIONS_FILE"
  perl -0pi -e "s/(wp_enqueue_script\(\s*'ncllc-pro-script'[^;]*,\s*)'[0-9]+\.[0-9]+\.[0-9]+'/\${1}'$version'/g" "$FUNCTIONS_FILE"
  perl -0pi -e "s/(wp_enqueue_style\(\s*'ncllc-pro-editor-style'[^;]*,\s*)'[0-9]+\.[0-9]+\.[0-9]+'/\${1}'$version'/g" "$FUNCTIONS_FILE"
  perl -0pi -e "s/(wp_enqueue_script\(\s*'ncllc-pro-editor-controls'[^;]*,\s*)'[0-9]+\.[0-9]+\.[0-9]+'/\${1}'$version'/g" "$FUNCTIONS_FILE"
}

require_files() {
  [[ -f "$STYLE_FILE" ]] || { echo "Error: missing style.css at $STYLE_FILE" >&2; exit 1; }
  [[ -f "$FUNCTIONS_FILE" ]] || { echo "Error: missing functions.php at $FUNCTIONS_FILE" >&2; exit 1; }
  command -v zip >/dev/null 2>&1 || { echo "Error: zip command is required" >&2; exit 1; }
  command -v rsync >/dev/null 2>&1 || { echo "Error: rsync command is required" >&2; exit 1; }
}

choose_version_from_menu() {
  local current="$1"
  local choice=""
  local custom_version=""

  while true; do
    cat >&2 <<MENU
Current version: $current

Choose release type:
  1) patch  ($(bump_version "$current" patch))
  2) minor  ($(bump_version "$current" minor))
  3) major  ($(bump_version "$current" major))
  4) custom version
  5) no bump / package current version
MENU

    read -r -p "Enter choice (1-5, default=1): " choice
    choice="${choice:-1}"

    case "$choice" in
      1|patch|p)
        echo "$(bump_version "$current" patch)"
        return 0
        ;;
      2|minor|m)
        echo "$(bump_version "$current" minor)"
        return 0
        ;;
      3|major|M)
        echo "$(bump_version "$current" major)"
        return 0
        ;;
      4|custom|c)
        read -r -p "Enter version X.Y.Z: " custom_version
        validate_version "$custom_version"
        echo "$custom_version"
        return 0
        ;;
      5|none|no|n|current)
        echo "$current"
        return 0
        ;;
      *)
        echo "Please choose 1, 2, 3, 4, or 5." >&2
        echo "" >&2
        ;;
    esac
  done
}

build_zip() {
  local tmp_dir package_dir old_pwd
  tmp_dir="$(mktemp -d)"
  package_dir="$tmp_dir/$THEME_SLUG"
  old_pwd="$(pwd)"

  mkdir -p "$package_dir"

  rsync -a \
    --exclude='.git' \
    --exclude='.DS_Store' \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='releases' \
    --exclude='.vscode' \
    --exclude='.idea' \
    --exclude='__MACOSX' \
    --exclude='*.swp' \
    --exclude='*.swo' \
    --exclude='*~' \
    "$ROOT_DIR/" "$package_dir/"

  cd "$tmp_dir"

  zip -r "$VERSIONED_ZIP" "$THEME_SLUG" \
    -x "*/.DS_Store" \
    -x "*/__MACOSX/*" >/dev/null

  cp "$VERSIONED_ZIP" "$LATEST_ZIP"

  cd "$old_pwd"
  rm -rf "$tmp_dir"
}

VERSION_OVERRIDE=""
BUMP_PART=""
NO_BUMP="false"

while [[ $# -gt 0 ]]; do
  case "$1" in
    --version)
      VERSION_OVERRIDE="${2:-}"
      shift 2
      ;;
    --bump)
      BUMP_PART="${2:-}"
      shift 2
      ;;
    --no-bump)
      NO_BUMP="true"
      shift
      ;;
    --slug)
      THEME_SLUG="${2:-}"
      shift 2
      ;;
    --root)
      ROOT_DIR="$(cd "${2:-}" && pwd)"
      RELEASES_DIR="$ROOT_DIR/releases"
      STYLE_FILE="$ROOT_DIR/style.css"
      FUNCTIONS_FILE="$ROOT_DIR/functions.php"
      shift 2
      ;;
    --help|-h)
      usage
      exit 0
      ;;
    *)
      echo "Error: unknown option $1" >&2
      usage
      exit 1
      ;;
  esac
done

require_files

CURRENT_VERSION="$(get_version)"
validate_version "$CURRENT_VERSION"

ACTION_COUNT=0
[[ -n "$VERSION_OVERRIDE" ]] && ACTION_COUNT=$((ACTION_COUNT + 1))
[[ -n "$BUMP_PART" ]] && ACTION_COUNT=$((ACTION_COUNT + 1))
[[ "$NO_BUMP" == "true" ]] && ACTION_COUNT=$((ACTION_COUNT + 1))

if (( ACTION_COUNT > 1 )); then
  echo "Error: use only one of --version, --bump, or --no-bump" >&2
  exit 1
fi

if [[ -n "$VERSION_OVERRIDE" ]]; then
  NEXT_VERSION="$VERSION_OVERRIDE"
elif [[ -n "$BUMP_PART" ]]; then
  NEXT_VERSION="$(bump_version "$CURRENT_VERSION" "$BUMP_PART")"
elif [[ "$NO_BUMP" == "true" ]]; then
  NEXT_VERSION="$CURRENT_VERSION"
else
  if [[ -t 0 ]]; then
    NEXT_VERSION="$(choose_version_from_menu "$CURRENT_VERSION")"
  else
    NEXT_VERSION="$(bump_version "$CURRENT_VERSION" patch)"
  fi
fi

validate_version "$NEXT_VERSION"

if [[ "$NEXT_VERSION" != "$CURRENT_VERSION" ]]; then
  set_version "$NEXT_VERSION"
fi

VERSION="$(get_version)"
validate_version "$VERSION"

mkdir -p "$RELEASES_DIR"
VERSIONED_ZIP="$RELEASES_DIR/$THEME_SLUG-$VERSION.zip"
LATEST_ZIP="$RELEASES_DIR/$THEME_SLUG.zip"

rm -f "$VERSIONED_ZIP" "$LATEST_ZIP"
rm -rf "$RELEASES_DIR/$THEME_SLUG"

build_zip

echo "Current version: $CURRENT_VERSION"
echo "New version: $VERSION"
echo "Updated: $STYLE_FILE"
echo "Updated: $FUNCTIONS_FILE"
echo "Built: $VERSIONED_ZIP"
echo "Built: $LATEST_ZIP"
echo ""
echo "Verify with:"
echo "  unzip -l \"$VERSIONED_ZIP\" | head"
