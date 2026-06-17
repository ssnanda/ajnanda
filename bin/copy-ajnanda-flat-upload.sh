#!/usr/bin/env bash
set -euo pipefail

# AJNANDA FLAT UPLOAD SCRIPT v2
# Purpose: copy only the files needed for review into one flat folder.
# No inc/, js/, blocks/ folder structure is created.

if [ ! -f "style.css" ]; then
  echo "Run this from your theme root:"
  echo "  /Users/sandip/Projects/ajnanda"
  exit 1
fi

VERSION="$(sed -n 's/^[[:space:]]*Version:[[:space:]]*//p' style.css | head -n 1)"

if [ -z "$VERSION" ]; then
  VERSION="$(date +%Y%m%d-%H%M%S)"
  echo "Could not find Version in style.css; using timestamp: $VERSION"
fi

DEST_DIR="$HOME/Downloads/ajnanda-upload-$VERSION"

rm -rf "$DEST_DIR"
mkdir -p "$DEST_DIR"

copy_flat_file() {
  local src="$1"
  local flat_name

  if [ ! -f "$src" ]; then
    echo "Skipping missing file: $src"
    return
  fi

  flat_name="$(echo "$src" | sed 's#^\./##; s#/#__#g')"
  cp -v "$src" "$DEST_DIR/$flat_name"
}

copy_flat_files_from_dir() {
  local dir="$1"
  local name_filter="$2"

  if [ ! -d "$dir" ]; then
    echo "Skipping missing directory: $dir"
    return
  fi

  find "$dir" \
    -type f \
    -name "$name_filter" \
    ! -name ".DS_Store" \
    ! -name "*.zip" \
    ! -path "*/node_modules/*" \
    ! -path "*/vendor/*" \
    -print | sort | while IFS= read -r file; do
      copy_flat_file "$file"
    done
}

echo "AJNANDA FLAT UPLOAD SCRIPT v2"
echo "Destination: $DEST_DIR"
echo

# Strict must-have files for current review.
copy_flat_file "functions.php"
copy_flat_file "style.css"
copy_flat_file "theme.json"

# Needed supporting files.
copy_flat_files_from_dir "inc" "*.php"
copy_flat_files_from_dir "js" "*.js"
copy_flat_files_from_dir "blocks/ajnanda-blocks" "*"

cat > "$DEST_DIR/README.txt" <<EOF
AJNanda flat upload snapshot
Version: $VERSION
Created: $(date)

This folder is intentionally flat.

Copied:
- functions.php
- style.css
- theme.json
- inc/*.php as inc__filename.php
- js/*.js as js__filename.js
- blocks/ajnanda-blocks files as blocks__ajnanda-blocks__...

Not copied:
- header.php
- footer.php
- index.php
- page.php
- single.php
- page-builder.php
- front-page.php
- 404.php
- searchform.php
- .DS_Store
- zip files
- releases
- .git
EOF

echo
echo "DONE"
echo "Upload files from this folder:"
echo "$DEST_DIR"
echo
echo "Flat folder check:"
find "$DEST_DIR" -mindepth 1 -maxdepth 1 -type d -print
echo
echo "If the line above printed any folders, something is wrong."
