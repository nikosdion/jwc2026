# The Museum of Unbuilt Futures — build & regeneration guide

Joomla 6.1 demonstration site: a fictional museum of inventions that never
happened, built the Joomla way — Cassiopeia child template, Bootstrap 5.3
dark color mode, custom fields, modules, menu assignments and targeted
overrides. No page builder.

- **Site root:** `~/Sites/jwcdemo`
- **Local URL:** `https://jwcdemo.akeeba.dev`
- **Joomla:** 6.1.4 (official full package; the dev working copy at
  `~/Projects/master/joomla6` was used as the versioned source of truth for
  Sass sources and template behaviour)
- **Stack:** macOS Apache (homebrew) + PHP 8.3 (php-fpm) + MySQL 8.4

## Repository layout (what is tracked)

| Path | What |
|---|---|
| `AGENTS.md` | Hand-off instructions for future coding agents |
| `.docs/kickoff-prompt.md` | The original brief, verbatim |
| `.docs/design.md` | Palette, typography, positions, Sass build |
| `.docs/architecture.md` | Categories, fields, menus, modules, overrides |
| `.docs/image-style.md` | FLUX.2 [pro] prompt frame & output rules |
| `.docs/image-prompts.json` | Image manifest (prompts + provenance) |
| `.docs/fixtures/*.json` | Content fixtures (artifacts, exhibitions, events, journal, pages, institution) |
| `.docs/scripts/build-css.sh` | Compiles the child template Sass |
| `.docs/scripts/provision.php` | Idempotent content/structure provisioning CLI |
| `.docs/scripts/generate-images.php` | FLUX.2 [pro] image generator |
| `templates/cassiopeia_muf/` | The Cassiopeia child template (sources) |
| `modules/mod_artifactspotlight/` | Custom module |
| `images/muf/` | Generated site imagery (populated by the generator) |
| `media/templates/site/cassiopeia_muf/fonts/` | Local OFL fonts (Space Grotesk, Inter, Space Mono) |

`.plans/` is intentionally **not** tracked.

## One-command build

`.docs/scripts/build-demo.sh` chains the whole thing — preflight (php/sass/site
checks) → provisioning → Sass build → missing FLUX images (only when
`BFL_API_KEY` is set; skipped with a note otherwise) → HTTP + structural
verification of 20 checks, exiting non-zero on any failure:

```bash
.docs/scripts/build-demo.sh                 # full build + verify
.docs/scripts/build-demo.sh --skip-images   # never touch the BFL API
```

## Rebuilding the custom parts on a fresh Joomla 6.1

1. Install Joomla 6.1 (any method) and copy the tracked folders above into
   the site root.
2. Register the child template + module as extensions, then provision:

   ```bash
   php .docs/scripts/provision.php          # all steps, idempotent
   php .docs/scripts/build-css.sh           # compile the Sass
   ```

3. Generate imagery (requires a BFL API key):

   ```bash
   export BFL_API_KEY='bfl_...'
   php .docs/scripts/generate-images.php --missing
   ```

4. Done: the site template, menus, modules, assignments, fields and content
   are in place. `provision.php` can be re-run safely at any time; existing
   items are detected and only healed (params, field values, nesting), never
   duplicated.

## Script reference

### `.docs/scripts/build-css.sh`

Compiles `templates/cassiopeia_muf/scss/template.scss` (and `-rtl`,
`editor`, `offline`) with the Dart Sass CLI against Joomla's own vendored
Bootstrap 5.3 sources (`media/vendor/bootstrap/scss`). Output goes to
`media/templates/site/cassiopeia_muf/css/`. Requires `sass` in PATH
(`brew install sass/sass/sass`).

### `.docs/scripts/provision.php`

```
php .docs/scripts/provision.php                # all steps
php .docs/scripts/provision.php --step=menus   # one step
php .docs/scripts/provision.php --dry-run      # plan without writes
```

Steps: `template`, `categories`, `fields`, `articles`, `routes`,
`menus`, `install-module`, `modules`.

Notes for maintainers:

- Articles are saved through com_content's admin model; field **values** are
  written to `#__fields_values` directly (Joomla has no single-table API for
  values outside the form flow).
- Menus/categories are nested-set objects: the provisioner calls
  `setLocation()` before `store()` and repairs nesting on re-runs.
- The module installer cannot run in this console bootstrap (its plugin
  imports rely on web-registered namespaces and fail silently), so
  `mod_artifactspotlight` is registered exactly as discover-install would
  write it: one `#__extensions` row + manifest cache. Its files are already
  in place; nothing is copied.
- Field groups/fields use core table classes; the provisioner registers
  component/plugin namespaces up front so the console bootstrap matches
  the web runtime.

### `.docs/scripts/generate-images.php`

```
BFL_API_KEY=... php .docs/scripts/generate-images.php --missing
BFL_API_KEY=... php .docs/scripts/generate-images.php --id=<image-id>
BFL_API_KEY=... php .docs/scripts/generate-images.php --all [--force]
```

Environment: `BFL_API_KEY` (required), `BFL_BASE_URL` (optional, default
`https://api.bfl.ai`; e.g. `https://api.eu.bfl.ai`). Uses `flux-2-pro`, the
async submit/poll flow, downloads results immediately, writes provenance
back into `.docs/image-prompts.json`. See `.docs/image-style.md`.

## Environment configuration

- Database: `jwcdemo` / user `jwcdemo` (local dev convention on this
  machine: user = password = database name). `configuration.php` is not
  tracked.
- The vhost is generated by `/opt/homebrew/etc/httpd/make_akeeba_dev_conf.php`
  (each `~/Sites/*` directory becomes a `*.akeeba.dev` subdomain); TLS via
  the wildcard Let's Encrypt certificate for `*.akeeba.dev`.
- Admin user: `niko` (super user; password set at install time, not
  committed anywhere).

## Known limitations

- **Imagery is gated on `BFL_API_KEY`.** Every `<img>` on the site points at
  `images/muf/<id>.jpg`; until the generator runs, the alt text renders in
  the framed slots (the layout does not break). This is the one acceptance
  item that cannot be completed without the key — see the final report.
- Search: no search module is published (Joomla 6 ships `mod_finder`, which
  needs a com_finder index; a dead search box was judged worse than none).
- The in-app browser's screenshot surface reports a minimum 500 px width; the
  true 390 px layout was verified via device emulation instead.
