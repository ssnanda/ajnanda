#!/usr/bin/env bash
set -euo pipefail

VERSION="$(sed -n 's/^[[:space:]]*Version:[[:space:]]*//p' style.css | head -n 1)"

if [ -z "$VERSION" ]; then
  echo "Could not find Version in style.css"
  exit 1
fi

DEST_DIR="$HOME/Downloads/ajnanda-sources/$VERSION"

rm -rf "$DEST_DIR"
mkdir -p "$DEST_DIR"

cp -v \
  functions.php \
  style.css \
  theme.json \
  inc/duplicate-content.php \
  inc/github-theme-updater.php \
  inc/theme-details-updater-button.php \
  js/main.js \
  js/editor-controls.js \
  blocks/ajnanda-blocks/loader.php \
  blocks/ajnanda-blocks/index.js \
  blocks/ajnanda-blocks/frontend.js \
  blocks/ajnanda-blocks/editor.css \
  "$DEST_DIR/"

# Cannot copy this as style.css because root style.css already uses that name.
# Keep it flat, but rename it clearly.
cp -v blocks/ajnanda-blocks/style.css "$DEST_DIR/blocks-style.css"

cat > "$DEST_DIR/README.txt" <<EOF
AJNanda source snapshot
Version: $VERSION
Created: $(date)

Copied files:
- functions.php
- style.css
- theme.json
- duplicate-content.php
- github-theme-updater.php
- theme-details-updater-button.php
- main.js
- editor-controls.js
- loader.php
- index.js
- frontend.js
- editor.css
- blocks-style.css

Note:
blocks-style.css = blocks/ajnanda-blocks/style.css
EOF

echo "Copied AJNanda source files to: $DEST_DIR"
