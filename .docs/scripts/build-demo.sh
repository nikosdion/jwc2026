#!/usr/bin/env bash
# ============================================================================
# build-demo.sh — one-command rebuild of The Museum of Unbuilt Futures
#
# Chains the full custom build on a Joomla 6.1 site:
#   1. preflight (php, sass, site present)
#   2. provision.php   — idempotent structure + content (template, categories,
#                        fields, articles, menus, modules, assignments)
#   3. build-css.sh    — compile the cassiopeia_muf Sass
#   4. generate-images.php --missing — ONLY when BFL_API_KEY is set
#   5. verification    — HTTP smoke checks on the key routes
#
# Usage:
#   .docs/scripts/build-demo.sh            # full build + verify
#   .docs/scripts/build-demo.sh --skip-images
#   SITE_URL=https://jwcdemo.akeeba.dev .docs/scripts/build-demo.sh
#
# Environment:
#   SITE_URL     default https://jwcdemo.akeeba.dev
#   BFL_API_KEY  optional; without it the image step is skipped (the site
#                renders alt text in the framed slots until images exist)
#
# Exit codes: 0 success; non-zero = the failed step's code.
# ============================================================================
set -euo pipefail

SITE_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
SITE_URL="${SITE_URL:-https://jwcdemo.akeeba.dev}"
SKIP_IMAGES=0

for arg in "$@"; do
    case "$arg" in
        --skip-images) SKIP_IMAGES=1 ;;
        -h|--help)
            sed -n '2,20p' "${BASH_SOURCE[0]}"
            exit 0
            ;;
        *) echo "Unknown option: $arg" >&2; exit 64 ;;
    esac
done

step() { printf '\n\033[1m== %s ==\033[0m\n' "$1"; }

# --- 1. Preflight -----------------------------------------------------------
step "1/5 Preflight"

command -v php >/dev/null 2>&1 || { echo "ERROR: php not found in PATH." >&2; exit 1; }
command -v curl >/dev/null 2>&1 || { echo "ERROR: curl not found in PATH." >&2; exit 1; }
command -v sass >/dev/null 2>&1 || { echo "ERROR: sass (Dart Sass CLI) not found. brew install sass/sass/sass" >&2; exit 1; }

[ -f "$SITE_ROOT/configuration.php" ] || { echo "ERROR: $SITE_ROOT is not a Joomla site root." >&2; exit 1; }
[ -d "$SITE_ROOT/media/vendor/bootstrap/scss" ] || { echo "ERROR: vendored Bootstrap sources missing — full Joomla install required." >&2; exit 1; }

echo "  site:    $SITE_ROOT"
echo "  url:     $SITE_URL"
echo "  php:     $(php -r 'echo PHP_VERSION;')"
echo "  sass:    $(sass --version)"
php -r 'exit(extension_loaded("imagick") || extension_loaded("gd") ? 0 : 1);' \
    && echo "  images:  imagick/gd available" \
    || echo "  images:  WARNING no imagick/gd — responsive derivatives disabled"

# --- 2. Provision ------------------------------------------------------------
step "2/5 Provision (idempotent)"
php "$SITE_ROOT/.docs/scripts/provision.php"

# --- 3. Compile Sass ---------------------------------------------------------
step "3/5 Compile Sass"
bash "$SITE_ROOT/.docs/scripts/build-css.sh"

# --- 4. Imagery ---------------------------------------------------------------
step "4/5 Imagery"
if [ "$SKIP_IMAGES" -eq 1 ]; then
    echo "  skipped (--skip-images)"
elif [ -n "${BFL_API_KEY:-}" ]; then
    php "$SITE_ROOT/.docs/scripts/generate-images.php" --missing
else
    echo "  BFL_API_KEY not set — skipping image generation."
    echo "  The site renders alt text in the framed slots until you run:"
    echo "    BFL_API_KEY=... php .docs/scripts/generate-images.php --missing"
fi

# --- 5. Verification -----------------------------------------------------------
step "5/5 Verification"

ROUTES=(
    ""
    "exhibitions"
    "exhibitions/tomorrow-cancelled"
    "exhibitions/the-house-of-the-future"
    "exhibitions/cities-in-the-sky"
    "the-collection"
    "the-collection/transport-exploration/the-personal-nuclear-automobile"
    "the-collection/domestic-futures/the-servo-valet-3000"
    "the-collection/communication-computing/the-pocket-parliament"
    "the-collection/architecture-urbanism/arcology-spire-heliopolis-one"
    "whats-on"
    "journal"
    "journal/why-we-thought-everyone-would-own-a-flying-car-by-1985"
    "plan-your-visit"
    "about-the-museum"
)

failures=0
for route in "${ROUTES[@]}"; do
    code=$(curl -s -o /dev/null -w "%{http_code}" --max-time 30 "$SITE_URL/$route")
    if [ "$code" = "200" ]; then
        printf "  \033[32m✓\033[0m /%s\n" "$route"
    else
        printf "  \033[31m✗ /%s → HTTP %s\033[0m\n" "$route" "$code" >&2
        failures=$((failures + 1))
    fi
done

# Structural checks
check() { # <route> <literal substring> <description>
    local body
    body="$(curl -s --max-time 30 "$SITE_URL/$1")"
    if [[ "$body" == *"$2"* ]]; then
        printf "  \033[32m✓\033[0m %s\n" "$3"
    else
        printf "  \033[31m✗ %s\033[0m\n" "$3" >&2
        failures=$((failures + 1))
    fi
}

check "" 'class="muf-band'                  "homepage module bands render"
check "" 'type="image/webp"'                 "responsive <picture> output"
check "the-collection" 'muf-shelf__items'    "collection shelves render"
check "the-collection/transport-exploration/the-personal-nuclear-automobile" 'muf-artifact__record' "artifact record card renders"
check "plan-your-visit" "accordion"          "visit page renders"

echo
echo "  Super User (demo): admin / "'@dminU$3R123'" — change when restoring from a backup."
if [ "$failures" -eq 0 ]; then
    printf '\033[32mBuild verified: %s — all checks passed.\033[0m\n' "$SITE_URL"
    exit 0
else
    printf '\033[31mBuild finished with %d failing check(s) — see output above.\033[0m\n' "$failures" >&2
    exit 2
fi
