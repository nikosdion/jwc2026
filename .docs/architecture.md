# Architecture — The Museum of Unbuilt Futures

Everything here is standard Joomla 6.1: articles, categories, custom
fields, menus, modules, menu assignments and template overrides. An
administrator can add an artifact, edit its fields, update exhibition
dates, publish a journal entry, change event details, reorder homepage
modules and reassign modules to pages — all without touching code.

## Categories (com_content)

- **The Collection** (`collection`)
  - Transport & Exploration
  - Domestic Futures
  - Communication & Computing
  - Architecture & Urbanism
- **Exhibitions**
- **What's On** (`whats-on`)
- **Museum Journal**
- (Pages live in the core *Uncategorised* category.)

## Custom fields (context `com_content.article`)

Groups and fields are created by the provisioner and editable under
Content > Fields:

- **Artifact Record** — assigned to the four wings: Conception Year,
  Inventor / Organisation, Place of Origin, Archival Reference,
  Short Summary, Reason Abandoned.
- **Exhibition Record** — assigned to Exhibitions: Opening Date,
  Closing Date, Gallery, Curator.
- **Event Record** — assigned to What's On: Date and Time, Venue,
  Admission.

Field values are read in layouts via `FieldsHelper` (through the shared
`templates/cassiopeia_muf/html/muf-tools.php` helper, cached per request).

## Menus

- **MUF Main** (`mufmain`): Home (Featured Articles view — the homepage
  component output is intentionally suppressed by the template in favour of
  the module bands), Exhibitions (category, layout `muf-exhibitions`) with
  three child exhibition items (layout `muf-exhibition`), The Collection
  (category, layout `muf-collection`, includes subcategory articles),
  What's On (layout `muf-events`), Journal (layout `muf-journal`),
  Plan Your Visit + About (articles, layout `muf-page`).
- **MUF Footer** (`muffooter`): alias items pointing at the main entries.

## Modules (all provisioned, assigned via `#__modules_menu`)

| Module | Type | Position | Assignment |
|---|---|---|---|
| Main navigation | mod_menu (`muf-main` layout) | `menu` | all pages |
| Breadcrumbs | mod_breadcrumbs | `breadcrumbs` | all except home |
| Footer: museum / navigate / visit | mod_custom ×2 + mod_menu | `muf-footer-*` | all pages |
| Homepage hero | mod_custom | `muf-hero` | home only |
| Currently Showing | mod_articles_category (`muf-exhibitions`) | `muf-current` | home only |
| From the Archives | **mod_artifactspotlight** (`muf-archive`) | `muf-archive` | home only |
| Explore the Collection | mod_articles_category (`muf-collection`) | `muf-collection` | home only |
| Upcoming Events | mod_articles_category (`muf-events`) | `muf-events` | home only |
| Curator's Pick | mod_custom | `muf-curator` | home only |
| From the Journal | mod_articles_latest (`muf-journal`) | `muf-journal` | home only |
| Plan Your Visit | mod_custom | `muf-visit` | home only |
| Visit sidebar ×2 | mod_custom | `sidebar-right` | visit page only |

The sample modules shipped with the installer are unpublished by the
provisioner (identified by the `muf-provisioned` note on managed modules).

## Template overrides / alternative layouts
(`templates/cassiopeia_muf/html/`)

- `mod_menu/muf-main.php` — two-level nav tree with dropdowns and
  gallery index numbers.
- `mod_articles_category/muf-exhibitions.php` — exhibition cards with
  date ribbons from custom fields.
- `mod_articles_category/muf-collection.php` — wing shelves grouped by
  sub-category.
- `mod_articles_category/muf-events.php` — chronological event rail.
- `mod_articles_latest/muf-journal.php` — asymmetric magazine grid.
- `mod_artifactspotlight/muf-archive.php` — oversized archive highlight.
- `com_content/article/default.php` — dispatcher: routes articles to the
  right composition by their custom-field record (artifact / exhibition /
  event / journal / page), so no per-article menu items are needed.
- `com_content/article/muf-artifact.php`, `muf-exhibition.php`,
  `muf-event.php`, `muf-journal.php`, `muf-page.php` — the compositions.
- `com_content/category/muf-exhibitions.php`, `muf-collection.php`,
  `muf-events.php`, `muf-journal.php` — category listings.
- `modules.php` — module chromes (see design.md).

## Custom extension: mod_artifactspotlight

`modules/mod_artifactspotlight/` — Joomla 6 module with DI service
provider, dispatcher and helper:

- Administrator settings: selection mode (random / latest / specific
  article), source category (includes sub-categories through the nested
  set), "View artifact" link toggle.
- Retrieves published, date- and access-checked articles only; never
  exposes unpublished content.
- Custom fields (year, inventor, summary) are read with `FieldsHelper`;
  output is fully escaped; layout overridable in the usual way.
- Registered by the provisioner as a discover-install equivalent (see
  `.docs/README.md` for why the Installer API is not used in the CLI).

## Caching

The module defaults to no caching (random selection would freeze
otherwise); when global caching is enabled the module caches per the core
`cache_time` parameter, which is the sensible behaviour for a spotlight.
