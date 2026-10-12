# AGENTS.md — The Museum of Unbuilt Futures

Guidance for coding agents picking up this project. The canonical spec is
`.docs/kickoff-prompt.md`; this file is the orientation map. **Prefer
reading `.docs/` over trusting this summary.**

## Environment

- Site root: `~/Sites/jwcdemo` — Joomla **6.1.4**, installed from the
  official full package (built media included; the git checkout at
  `~/Projects/master/joomla6` has *no* built media and must not be served).
- Local URL: `https://jwcdemo.akeeba.dev` (homebrew Apache vhost, TLS via
  wildcard Let's Encrypt; vhosts regenerate from `~/Sites/*` via
  `/opt/homebrew/etc/httpd/make_akeeba_dev_conf.php`).
- DB: MySQL 8.4, database/user/password `jwcdemo` (local convention).
- PHP 8.3 via php-fpm for the site; PHP CLI 8.5 for scripts. `sass` (Dart
  Sass CLI) must be in PATH. **All automation is Bash or PHP — never add
  Node/npm/Python tooling.**

## Non-negotiables

1. Cassiopeia **child template** (`templates/cassiopeia_muf`,
   `<parent>cassiopeia</parent>`). Never a standalone template; never edit
   `templates/cassiopeia/` or core files.
2. **Bootstrap 5.3** as vendored by Joomla (`media/vendor/bootstrap`). One
   distribution only; no competing CSS frameworks; no second Bootstrap.
3. The **exact palette** in `.docs/design.md` — change tokens in
   `scss/muf/_variables.scss` before compilation, not with overrides.
4. Content imagery comes from **BFL FLUX.2 [pro]** only, via
   `.docs/scripts/generate-images.php` and `.docs/image-prompts.json`.
   Never substitute stock photos, other models, or procedural SVG for
   content imagery. If the API key is missing, report the blocker — do not
   fake imagery.
5. Build the site the **Joomla way**: articles, categories, custom fields,
   menus, modules, menu assignments, layouts/overrides. No page builder;
   no hard-coded homepage in template PHP.

## Working rules

- Write intermediate plans and progress notes as Markdown under `.plans/`
  (not committed). Keep `.docs/` accurate when you change architecture,
  build steps or fixtures.
- Keep provisioning **idempotent**: re-running
  `.docs/scripts/provision.php` must never duplicate or corrupt. Extend
  fixtures (`.docs/fixtures/*.json`) rather than writing one-off SQL.
- Nested-set objects (categories, menu items) need `setLocation()` before
  `store()` — see the provisioner for the pattern.
- One command for everything: `./.docs/scripts/build-demo.sh` (chains
  provision → build-css → images (when BFL_API_KEY is set) → verification).
- After changing Sass only: `./.docs/scripts/build-css.sh`, then clear
  `cache/`. Compiled CSS lives in
  `media/templates/site/cassiopeia_muf/css/` and is **not** tracked.
- After changing fixtures/structure: `php .docs/scripts/provision.php`.
- After adding images to the manifest: `php .docs/scripts/generate-images.php --missing`.

## Key paths

| Path | Purpose |
|---|---|
| `.docs/kickoff-prompt.md` | The brief (verbatim) |
| `.docs/README.md` | Build, rebuild, script reference |
| `.docs/design.md` | Design system, positions, Sass build |
| `.docs/architecture.md` | Categories, fields, menus, modules, overrides |
| `.docs/image-style.md` + `image-prompts.json` | Image pipeline |
| `.docs/fixtures/` + `.docs/scripts/provision.php` | Content & structure |
| `.docs/scripts/build-css.sh` | Sass compilation |
| `.docs/scripts/generate-images.php` | FLUX.2 [pro] generator |
| `templates/cassiopeia_muf/` | Child template (scss sources, layouts, chromes) |
| `modules/mod_artifactspotlight/` | Custom module (DI provider, helper, layouts) |
| `images/muf/` | Generated imagery (tracked once generated) |

## Testing

- Verify pages over HTTP(S) with a real browser at desktop/tablet/phone
  widths before claiming done. Note: headless Chrome enforces a ~500 px
  minimum window width — use device emulation for true phone widths.
- `curl -s -o /dev/null -w '%{http_code}' https://jwcdemo.akeeba.dev/<route>`
  for smoke checks; keep an eye on the PHP error log.
- Acceptance checklist lives in `.docs/kickoff-prompt.md` §9.
