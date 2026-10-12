#!/usr/bin/env bash
# ============================================================================
# build-css.sh — compile the cassiopeia_muf child template Sass
#
# Uses the Dart Sass CLI (`sass`, https://sass-lang.com/dart-sass) against:
#   * the child template's Sass tree  (templates/cassiopeia_muf/scss)
#   * Bootstrap 5.3 sources vendored by Joomla itself
#     (media/vendor/bootstrap/scss) — no second Bootstrap copy, ever
#
# Output: media/templates/site/cassiopeia_muf/css/{template,template-rtl}.min.css
# ============================================================================
set -euo pipefail

SITE_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
TEMPLATE_DIR="$SITE_ROOT/templates/cassiopeia_muf"
SCSS_DIR="$TEMPLATE_DIR/scss"
BOOTSTRAP_SCSS="$SITE_ROOT/media/vendor/bootstrap/scss"
OUT_DIR="$SITE_ROOT/media/templates/site/cassiopeia_muf/css"

if ! command -v sass >/dev/null 2>&1; then
    echo "ERROR: the Dart Sass CLI ('sass') was not found in PATH." >&2
    echo "Install with: brew install sass/sass/sass" >&2
    exit 1
fi

if [ ! -d "$BOOTSTRAP_SCSS" ]; then
    echo "ERROR: Bootstrap sources not found at $BOOTSTRAP_SCSS" >&2
    echo "This site must run on a full Joomla 6.1 installation." >&2
    exit 1
fi

mkdir -p "$OUT_DIR"

COMMON_ARGS=(
    --style=compressed
    --no-source-map
    --silence-deprecation=import
    --silence-deprecation=global-builtin
    --load-path="$BOOTSTRAP_SCSS"
    --load-path="$SCSS_DIR"
)

echo "Compiling $SCSS_DIR/template.scss ..."
sass "${COMMON_ARGS[@]}" "$SCSS_DIR/template.scss" "$OUT_DIR/template.min.css"

echo "Compiling $SCSS_DIR/template-rtl.scss ..."
sass "${COMMON_ARGS[@]}" "$SCSS_DIR/template-rtl.scss" "$OUT_DIR/template-rtl.min.css"

# Also build the editor/offline styles that ship with the Cassiopeia tree,
# so the child template can serve its own copies.
for entry in editor offline; do
    if [ -f "$SCSS_DIR/$entry.scss" ]; then
        echo "Compiling $SCSS_DIR/$entry.scss ..."
        sass "${COMMON_ARGS[@]}" "$SCSS_DIR/$entry.scss" "$OUT_DIR/$entry.min.css" || \
            echo "WARNING: $entry.scss failed to compile (non-fatal)." >&2
    fi
done

echo "Done. Output in $OUT_DIR"
ls -la "$OUT_DIR"
