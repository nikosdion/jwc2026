# Design system — The Museum of Unbuilt Futures

1980s retrofuturism with the editorial polish of a contemporary museum.
Neon is used purposefully for accents, links and controls — surfaces do not
glow. The faint technical gridlines and the mono "archival metadata"
typeface carry the institution's voice.

## Palette (exact, non-negotiable)

| Token | Value | Usage |
|---|---|---|
| primary | `#36F5F0` | electric cyan — links, accents, primary buttons |
| secondary | `#A77BFF` | arcade violet |
| success | `#65FBD2` | neon mint — link hover, positive accents |
| danger | `#FF5C75` | hot coral — "reason abandoned" boxes |
| warning | `#FFB454` | synthwave amber — event accents |
| info | `#FF4FD8` | laser magenta — editorial accents |
| light | `#E7F4FF` | phosphor white — body text |
| dark | `#090B1A` | deep space navy — body background |

Surfaces: secondary `#13172B`, raised `#201832`; muted text `#9AA8C7`;
borders `#343052`. Link hover `#65FBD2`.

These values are Sass **plain assignments** in
`templates/cassiopeia_muf/scss/muf/_variables.scss`, injected before
Bootstrap's own variables are compiled in — the palette lives in the
compiled utilities and components, not in post-hoc overrides.

## Dark color mode

The site uses Bootstrap 5.3 dark color mode: `$enable-dark-mode: true` and
`<html data-bs-theme="dark">`. Light and dark token sets are intentionally
identical (the museum is dark-first), so any color mode yields the palette.

## Typography (local, OFL-licensed)

| Role | Family | Notes |
|---|---|---|
| Display | Space Grotesk 500/700 | headings, hero, wordmark |
| Body | Inter 400/400i/700 | long-form reading |
| Archival metadata | Space Mono 400/700 | dates, specs, references, nav |

Fonts are self-hosted WOFF2 (latin subsets) at
`media/templates/site/cassiopeia_muf/fonts/` — no third-party requests.
Type scale uses `clamp()` for fluid display headings; body measure is
`max-width: 46rem` for prose.

## Layout

- Page shell: CSS Grid with named tracks
  (`[full-start] … [main-start] … [main-end] … [full-end]`), max width
  `82rem`, faint 4 rem gridlines over deep navy.
- Homepage bands alternate three surface treatments (base / raised /
  feature) for rhythm; each band is a full-width grid row fed by its own
  module position.
- Asymmetry is structural: hero 1.05fr/0.95fr with perspective figure,
  artifact detail 7fr/5fr with sticky record card, journal grid 7/5 split,
  event rail with hairline separators.
- Sidebar (when published) carves a `minmax(14rem, 21rem)` track out of the
  main column at ≥lg and stacks below content on smaller screens.

## Module positions (child template)

Homepage bands (in display order): `muf-hero`, `muf-current`,
`muf-archive`, `muf-collection`, `muf-events`, `muf-curator`, `muf-journal`,
`muf-visit`.

Site chrome: `menu` (main nav), `breadcrumbs`, `topbar`, `main-top`,
`main-bottom`, `sidebar-left`, `sidebar-right`, `footer` positions
(`muf-footer-brand`, `muf-footer-nav`, `muf-footer-visit`), `debug`.

Module chromes provided by the template (`html/modules.php`): `none`,
`card` (inherited pattern), `muf-raw` (verbatim output, visually hidden
title), `muf-frame` ("museum label" frame with mono uppercase title).

## Sass build

- Entry: `templates/cassiopeia_muf/scss/template.scss`
  (RTL reuses the LTR build: Bootstrap 5.3 handles RTL through its own
  pipeline; `template-rtl.scss` imports `template`).
- The Cassiopeia Sass tree is vendored unmodified from joomla-cms tag
  `6.1.4` (`build/media_source/templates/site/cassiopeia/scss`) under
  `scss/{blocks,tools,vendor,global,system}`; only the entry points are
  replaced and the `muf/` layer is added.
- Bootstrap sources: `media/vendor/bootstrap/scss` (Joomla's vendored
  Bootstrap 5.3) via `--load-path`. **No second Bootstrap copy exists.**
- Build: `.docs/scripts/build-css.sh` (Dart Sass, compressed, no source
  maps). Output: `media/templates/site/cassiopeia_muf/css/` (build output,
  untracked).

## Accessibility

- Semantic landmarks, skip link, keyboard-operable nav (Bootstrap collapse
  + dropdown), visible cyan focus rings (`$focus-ring-color`).
- `prefers-reduced-motion` disables animations/transitions; decorative
  motion never carries information.
- Contrast: cyan on navy ≈ 13:1, muted on navy ≈ 8:1, phosphor white on
  navy ≈ 15:1.
- Print stylesheet strips atmospheric backgrounds and keeps the archival
  record legible.

## Imagery

See `.docs/image-style.md`. All content imagery is FLUX.2 [pro] wireframe
renderings with a fixed prompt frame; the museum's fiction explains the
style (the Restoration Laboratory "reconstructs" lost machines as luminous
wireframe studies). Decorative motifs (gridlines, the visit-page transit
map) are pure CSS.

## Responsive imagery (MufImage)

Layout-rendered images are delivered through
`templates/cassiopeia_muf/html/MufImage.php` (used via the `muf_picture()`
helper in `html/muf-tools.php`): resized JPEG + WebP derivatives are
generated on demand (Imagick with automatic GD fallback — verified in the
web SAPI, where this machine's Imagick lacks the JPEG delegate), cached in
the gitignored `media/templates/site/cassiopeia_muf/derived/` directory, and
emitted as `<picture>` with a WebP srcset, a JPEG srcset, the original as
final fallback, intrinsic dimensions, and per-layout `sizes`. Derivatives
regenerate automatically when a source image is replaced (e.g. after a FLUX
re-run).
