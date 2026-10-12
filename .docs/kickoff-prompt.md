# Kickoff Prompt — The Museum of Unbuilt Futures

> **Instructions to the coding agent:** This document is the complete implementation brief. Build the site, populate it, test it, and document it. Do not stop at a plan or a scaffold. This is a live demonstration of agentic Joomla development for an audience of experienced Joomla developers.

## 1. Project and environment

Create **The Museum of Unbuilt Futures**, a fictional museum website dedicated to inventions, technologies, architecture, and visions of tomorrow that never came to pass.

The site must be a **Joomla 6.1** installation at:

- **Site root:** `~/Sites/jwcdemo`
- **Local URL:** `https://jwcdemo.akeeba.dev`
- **Joomla 6.1 development working copy:** `~/Projects/master/joomla6`

Create and configure the database yourself, following the established local Joomla-development skill available to you. Inspect that skill and the local environment before making assumptions about database credentials, the web server, or installation procedures. The stated HTTPS hostname is already routed to the local site; do not replace the local environment with Docker or an unrelated stack.

Work on **macOS or Linux**. You may assume that standard developer tools including `git`, `curl`, PHP CLI, and a Sass compiler (`sass` or `scss`) are installed. Inspect available versions and use the installed tools. **All automation, provisioning, build, and image-generation scripts must be written in Bash or PHP.** Do not introduce Python, Node.js, npm, a JavaScript-based build tool, or another scripting runtime. Ordinary frontend JavaScript supplied by Joomla/Bootstrap, or a small amount of browser-side JavaScript genuinely required by the site, is permitted; this is not permission to use JavaScript for build automation.

### Absolute technical requirements

1. **Use a proper Cassiopeia child template. This is mandatory.** Do not create a standalone Joomla template or replace Cassiopeia with a third-party framework.
2. **Use Bootstrap 5.3. This is mandatory.** Respect Joomla's asset-loading mechanisms and the Cassiopeia relationship; do not add a competing CSS framework or load a second conflicting Bootstrap distribution.
3. **Use the exact color palette in §3.** These colors intentionally match the accompanying presentation's visual identity. Do not reinterpret, substitute, or “improve” them.
4. **Generate the site's images using Black Forest Labs' FLUX.2 [pro] model through the BFL API.** Do not use another model, stock photographs, hotlinked images, or unrelated image services as replacements.
5. **Generate images through a reusable Bash/PHP script, from prompts stored under `.docs`.** Do not make one-off undocumented API calls or embed image prompts solely inside script source.
6. **Build the site the Joomla way:** Joomla articles, categories, custom fields, menus, modules, menu-based module assignments, child-template layouts/overrides, and Joomla APIs. **No page builder.**
7. **Implement the complete site, not just the homepage.** Verify it in the browser at the local URL.

Treat those seven items as non-negotiable acceptance criteria. If a prerequisite is genuinely unavailable (for example, no BFL API key), do not silently substitute an alternative; continue what can be completed, clearly report the blocker, and provide the exact command or environment setting required to unblock it.

## 2. Museum concept and editorial voice

**Museum name:** The Museum of Unbuilt Futures  
**Suggested tagline:** *Preserving the tomorrows that never happened.*

The museum celebrates humanity's extraordinary ability to imagine tomorrow—and its equally extraordinary ability to get tomorrow completely wrong. It collects and interprets unrealized inventions and technological proposals from the late 19th century through the late 20th century, with emphasis on the 1950s–1980s.

The writing should be intelligent, believable, imaginative, and occasionally absurd. Present the artifacts **with the seriousness of a real cultural institution**. The humor should arise naturally from the ideas and their invented histories, not from winking copy or jokes about the website being fictional. Make the institution's fictional status reasonably apparent so its exhibits are not mistaken for real historical claims.

Starter artifacts include:

- **The 1958 Personal Nuclear Automobile:** A family car powered by a miniature atomic reactor. What could possibly go wrong?
- **The Transatlantic Pneumatic Railway (1895):** An underwater tube promising near-instant travel between London and New York.
- **The Servo-Valet 3000 (1983):** A domestic robot that eliminates chores, provided somebody maintains its 47 hydraulic systems.
- **The Home Weather Controller (1962):** An appliance promising perfect weather in one's garden, independent of surrounding conditions.
- **The Pocket Parliament (1981):** A handheld electronic device to automate political decision-making.
- **The Lunar Postal Service (1956):** An ambitious lunar mail service with a suspiciously optimistic pricing model.

Invent at least six additional artifacts, along with fictional inventors, archival references, anecdotes, curators, exhibitions, and events. Keep names, dates, descriptions, and institutional facts internally consistent across the site.

## 3. Visual identity: fixed palette, 1980s retrofuturism

Create a visually ambitious museum website that combines:

- 1980s wireframe and retrofuturistic imagery;
- the editorial polish of a contemporary museum or cultural institution;
- dramatic imagery and oversized headings;
- asymmetric page compositions, CSS Grid, Flexbox, and considered whitespace;
- occasional gridlines, geometric technical motifs, and archival-style metadata;
- excellent legibility and accessibility.

It must look like a bespoke design, **not a generic Bootstrap demo** and not an arcade-game interface. Use neon purposefully for accents, links, controls, and emphasis, rather than making every surface glow.

### Exact Bootstrap semantic theme colors

| Bootstrap token | Description | Exact value |
|---|---|---|
| `primary` | Electric cyan | `#36F5F0` |
| `secondary` | Arcade violet | `#A77BFF` |
| `success` | Neon mint | `#65FBD2` |
| `danger` | Hot coral | `#FF5C75` |
| `warning` | Synthwave amber | `#FFB454` |
| `info` | Laser magenta | `#FF4FD8` |
| `light` | Phosphor white | `#E7F4FF` |
| `dark` | Deep space navy | `#090B1A` |

### Other exact color assignments

| Purpose | Exact value |
|---|---|
| Body background | `#090B1A` |
| Body text | `#E7F4FF` |
| Secondary surfaces | `#13172B` |
| Raised surfaces | `#201832` |
| Muted text | `#9AA8C7` |
| Borders | `#343052` |
| Links | `#36F5F0` |
| Link hover | `#65FBD2` |

Use **Bootstrap 5.3 dark color mode** via `data-bs-theme="dark"`. Implement the palette consistently for components, utility classes, buttons, forms, cards, alerts, navigation, borders, and focus states. When Bootstrap's Sass variables and color maps must be customized to generate the correct semantic utilities, do so **before Sass compilation**; do not rely only on last-minute CSS overrides. Inspect the actual Bootstrap 5.3 sources and Cassiopeia in `~/Projects/master/joomla6` and choose a build strategy that works with Joomla 6.1's Web Asset Manager. Avoid loading conflicting copies of Bootstrap. Document the decisions and actual CSS/Sass entry points.

Choose an appropriate display font for prominent titles, readable sans-serif for body text, and monospace for dates, specifications, and archival identifiers. Use fonts in a privacy-conscious, deployable manner—prefer local assets where licensing permits—and document licensing.

Implement responsive layouts for desktop, tablet, and phone. Use semantic HTML, keyboard-accessible navigation, visible focus styles, reasonable contrast, alt text, and `prefers-reduced-motion`. Motion and hover effects may add polish but must not carry essential information.

## 4. Imagery: FLUX.2 [pro], reproducible and consistent

**All bespoke illustrative/content images must be AI-generated using Black Forest Labs' `FLUX.2 [pro]` model.** The common style is deliberately identical to the imagery used in the presentation.

Use this **exact prefix** at the start of every generation prompt:

> `1980s wireframe 3D rendering, neon wireframe colors, soft shadowing, dark background`

Use this **exact suffix** at the end of every generation prompt:

> `No readable text, no labels`

Between them, write a distinctive, detailed subject description suitable for the particular exhibit, exhibition, article, or site section. Do not alter the two fixed strings. Aim for arresting wireframe renderings rather than photographic realism. Titles, labels, dates, and technical captions belong in **real HTML**, not inside generated imagery.

For example, a subject description (without the fixed prefix and suffix) might be: *“An extravagant 1950s family automobile with a compact cylindrical nuclear reactor beneath a transparent rear canopy, three-quarter view, precision technical-model aesthetics, dramatic museum-object composition.”*

### Required prompt repository and generation tooling

Create a documented, reproducible pipeline such as:

- `.docs/image-style.md` — exact global prefix/suffix, visual-direction notes, prompt-writing conventions, image formats, and output rules.
- `.docs/image-prompts.json` — a tracked manifest of per-image prompts with stable IDs, usage/location, target filenames, requested dimensions/aspect ratios, and optional fixed seeds. The script reads the manifest and constructs the full prompt from its individual subject description plus the fixed prefix and suffix.
- `.docs/scripts/generate-images.php` **or** `.docs/scripts/generate-images.sh` — the actual CLI generator, written in PHP or Bash, not another language.
- `.docs/README.md` — invocation examples, environment configuration, safe regeneration procedure, and location of generated assets.

Use the official BFL API for **FLUX.2 [pro]**, currently `POST /v1/flux-2-pro`, with the `x-key` authentication header. Use the environment variable `BFL_API_KEY`; never paste or commit credentials. Allow an optional base URL setting to support BFL's regional endpoints, without changing the selected model. Implement BFL's asynchronous job flow: submit a request, use its returned polling URL, wait for `Ready`, and **download the result immediately** because hosted result URLs are temporary. Handle errors, timeouts, missing credentials, and retryable rate limits sensibly.

Generate an intentional, finite collection of images: enough to support the homepage and distinct page types (roughly 16–25 carefully chosen images is a reasonable starting point). Avoid hundreds of unnecessary paid generations. Expose a way to generate one named image, missing images, or the entire manifest. Prefer reusing deliberately chosen exhibit imagery across related articles and modules. Capture image provenance (prompt, model, size, seed if returned, generated filename) in the manifest or a companion tracked metadata file, so the assets can be reproduced deliberately.

Store the **downloaded site images locally**, for example under `images/muf/` or another documented custom-assets path. Choose practical sizes, crop and optimize as needed (using Bash/PHP and available standard image utilities), set intrinsic dimensions or aspect ratios where practical, and supply alt text. Do not require runtime requests to BFL or another image host. Generated images that the actual site uses must be included in the custom assets tracked by Git.

You may use CSS or locally authored SVG for purely decorative layout motifs (gridlines, separators, subtle shapes), but **do not substitute procedural SVG artwork, stock photos, or another image-generation model for the required FLUX.2 [pro] content imagery**. If the API is unavailable, report that explicitly instead of passing off substitutes as generated images.

## 5. Information architecture and sample content

Build the following distinct areas. Treat the counts as minimums. Use proper menus and working links, not a single page with anchor links pretending to be a website.

### A. Homepage — visual centerpiece

Construct the homepage from multiple, independently manageable **Joomla modules and template positions**. Proposed sections:

1. **Hero / current feature:** arresting image, headline, introductory text, call to action.
2. **Currently Showing:** a varied presentation of exhibition cards.
3. **From the Archives:** a single oversized artifact highlight.
4. **Explore the Collection:** a collection preview, ideally grouped or visually differentiated by subject/decade.
5. **Upcoming Events:** useful event/date cards or a concise chronological strip.
6. **Curator's Pick:** a distinctive editorial module.
7. **From the Journal:** latest articles in an asymmetric magazine-like presentation.
8. **Plan Your Visit:** practical information and relevant calls to action.

Do **not** use eight identical rows of Bootstrap cards. Vary component scale, composition, image treatment, content density, and background surfaces. The homepage should look deliberately art-directed.

Most sections must be populated by independently assigned Joomla modules, ideally drawing from articles/categories/custom fields where appropriate. They must **not** be hard-coded into one article, one template PHP file, or a handcrafted page-builder-like data structure. Give template positions intelligible names; document module assignment, ordering, and content sources.

### B. Exhibitions

Create at least three exhibitions:

- **Tomorrow, Cancelled:** spectacular technology promises that never materialized.
- **The House of the Future:** devices intended to revolutionize domestic life.
- **Cities in the Sky:** abandoned urban megastructures and impossible infrastructure.

Provide a striking overview page and individual exhibition pages with hero imagery, introduction, curatorial narrative, dates, selected artifacts, and visitor information. Use articles, structured custom fields, and suitable Joomla layouts; relate exhibits to exhibitions in a maintainable fashion. Exhibition dates should be meaningful and consistent.

### C. Permanent Collection

Create **at least twelve artifacts** across these categories:

- Transport & Exploration
- Domestic Futures
- Communication & Computing
- Architecture & Urbanism

Provide a browsable, visually engaging collection overview and distinct artifact detail pages with large imagery and an archival/specification sidebar or equivalent composition.

Each artifact should have structured metadata implemented with **Joomla custom fields**, including: conception year, inventor/organization, place of origin, collection category, archival reference, short summary, and the reason the project was abandoned. Put long-form history in normal article content. Render metadata through appropriate Joomla layout/field APIs, **not by embedding it as literal text in template PHP**. New artifacts must be creatable through Joomla's administrator UI without a code change.

### D. What's On

Create at least four upcoming events, for example curator tours, a lecture on failed predictions, a family workshop, retrofuturistic film screenings, and museum after-hours. Give events usable dates, times, and descriptions, using article content and custom fields. Present them in an appealing chronological or timeline-oriented layout. Keep event dates in the future relative to the demo/build date rather than using long-expired dates.

### E. Museum Journal

Create at least five original articles, for example:

- *Why We Thought Everyone Would Own a Flying Car by 1985*
- *The Surprisingly Complicated History of Robot Butlers*
- *When Tomorrow Came in Chrome*
- *The Lost Art of Predicting the Future*
- *Notes from the Museum's Restoration Laboratory*

Use a magazine-like category page with a prominent lead story and varied cards. Individual articles should be pleasant to read at length; they should not reuse the artifact-detail layout indiscriminately.

### F. Plan Your Visit

Create a believable **fictional** location, opening hours, admission prices, accessibility information, transport notes, FAQ, and exhibition-related calls to action. Compose a practical, attractive page from articles and appropriately assigned modules. No payment processing or booking integration is necessary. Reuse institutional facts consistently.

### G. About the Museum

Introduce the fictional institution, its history and mission, an institutional timeline, and a small fictional team. Treat this as an opportunity for a distinctive editorial composition rather than another generic article.

## 6. Joomla-native implementation

This requirement is central to the demonstration: **the result should be as attractive as a bespoke agency site while remaining recognizably Joomla under the hood.**

### Mandatory child template and Bootstrap 5.3

Create an actual **Cassiopeia child template** using the Joomla 6.1-supported mechanism. Inspect Joomla's installed code and existing Cassiopeia implementation first. Preserve a meaningful parent/child relationship, use Joomla's Web Asset Manager correctly, and make overrides and assets only in custom locations. Never modify Joomla core or the parent Cassiopeia files.

Use Bootstrap **5.3** as supplied/compatible with the Joomla 6.1 environment. Adapt the Sass build for the exact palette and preserve Bootstrap semantics/components/utilities. Reuse Cassiopeia infrastructure whenever possible. Do not swap the site to a custom standalone template, even if that seems easier.

### Content and menus

Use Joomla's built-in articles, categories, menus, images, featured states, tags where useful, and custom fields. Use suitable menu item types and aliases. Configure clean internal navigation and breadcrumbs where helpful. Keep editorial copy and metadata in Joomla content, not PHP literals.

### Modules and assignments

Use native Joomla modules extensively: menu/navigation, article listings, custom-content modules, footer content, visitor details, and other appropriate types. Create custom module layouts or child-template module overrides when needed for bespoke composition. Configure module **positions, ordering, and per-menu-item assignments** in Joomla, not just in CSS.

An administrator must be able to change homepage content, rearrange modules, or alter their menu assignments through Joomla's backend without rewriting the homepage.

### At least one genuinely custom module

Build an **Artifact Spotlight** Joomla module, appropriate to Joomla 6.1's extension architecture. Retrieve published artifact articles through Joomla APIs. It should display an image, title, year, short description, and a working article link, with proper custom-field access. Provide useful administrator settings, such as selecting the source category and choosing a specific artifact or random selection. Do not duplicate artifact data in module configuration or source code.

Ensure caching/random selection behavior is sensible, output is escaped appropriately, and the module does not expose unpublished content. Keep its layout overridable where practical.

### Targeted template overrides / alternative layouts

Use overrides or native alternative layouts where they materially improve the result, for example collection category, artifact detail, exhibition detail/listing, journal listing, or article-listing modules. Do not copy core layouts gratuitously. Keep custom code lean and Joomla-version-appropriate.

### Maintainability demonstration

The administrator should be able to perform all of these without modifying code: add an artifact, edit its custom fields, update exhibition dates, publish a journal entry, change event details, reorder homepage modules, and assign modules to different pages.

Use standard Joomla APIs/CLI/web services for programmatic content setup as appropriate. Avoid fragile direct database writes for objects that Joomla APIs must register or manage. Provisioning should be repeatable and, where practical, **idempotent** rather than creating duplicate categories, fields, or modules on each run.

## 7. Files, plans, documentation, and reproducibility

Create these in the site root:

- `.plans/` — intermediate Markdown plans and progress notes. This directory is **not** committed.
- `.docs/` — **tracked** architecture/design/build documentation, fixture manifests, Bash/PHP scripts, image prompts, and regeneration guidance.
- `AGENTS.md` — instructions for subsequent coding agents.
- `.gitignore` — strict exclusions/allowlisting as described below.

### Planning

Follow the available project-planning skill. Break work into pragmatic phases: installation; data/content architecture; template/design tokens and Sass; imagery; modules/overrides; population; browser testing/refinement. Store detailed and updated plans as Markdown under `.plans/`. Do not spend the entire task planning; execute the plan.

### Documentation

At minimum, document:

- Project architecture and local deployment steps.
- Exact visual palette, typography, layout rules, module positions, and CSS/Sass build process.
- How the child template inherits Cassiopeia and loads Bootstrap 5.3, including any asset overrides or compilation strategy.
- Categories, custom fields, menus, module positions, assignments, and overrides.
- All custom Joomla extensions and how to modify them.
- Image prompts, generation script, API environment variables, generation metadata, output paths, and image provenance.
- Bash/PHP scripts for content creation and site provisioning, along with sample-data fixtures.
- How to reinstall/rebuild the **custom parts** on a fresh Joomla 6.1 site.
- How to test the site and any known limitations.

Place the scripts and configuration used to compile the custom template within `.docs` (for example `.docs/scripts/build-css.sh`), with editable Sass source stored in the custom template or another clearly documented tracked location. Do not rely on manual, undocumented steps or a Node/npm toolchain.

**Save this entire kickoff prompt, verbatim, as `.docs/kickoff-prompt.md`.**

### AGENTS.md

Write an `AGENTS.md` file that future agents can use to pick up the project. Include key environment paths, architectural principles, build and test commands, custom extensions, documentation pointers, and the non-negotiable use of Cassiopeia + Bootstrap 5.3. Explicitly require intermediate implementation plans to be written as Markdown files under `.plans/`. Prefer referencing `.docs` to duplicating the full specification.

### Version control and `.gitignore`

Initialize Git if needed, but **do not commit** unless asked.

Only track the **custom implementation and the materials needed to reproduce it**:

- `AGENTS.md` and `.docs/**`;
- the Cassiopeia child template's custom files;
- custom modules/extensions and their assets;
- custom image assets used by the site;
- any other genuinely site-specific files needed for reproducibility.

Exclude **all core Joomla files**, the parent Cassiopeia template, `.plans/`, local Joomla configuration files and credentials, dependencies, caches, temporary outputs, and machine-specific artifacts. A root-level allowlist `.gitignore` may be easiest; ensure allowed custom files nested under Joomla's `templates/`, `media/`, `modules/`, and `images/` directories are actually visible to Git. Verify with `git status --short` and `git check-ignore` that no core files are included and no intended custom files are lost.

Never check in API tokens, passwords, generated private environment files, or other secrets. Locally generated **public site images** are intended to be committed.

## 8. Implementation workflow and quality standard

Inspect the existing installation/development skills, project filesystem, Cassiopeia and Bootstrap sources, and available local tools first. Then proceed autonomously through installation, planning, building, fixture population, and testing.

Use Bash or PHP for setup scripts, database provisioning, content fixtures, Sass compilation orchestration, BFL image generation, and other programmatic tasks. It is fine for data fixtures to be JSON, Markdown, or another declarative format consumed by PHP; the executable scripts themselves must be Bash or PHP.

Create enough original sample content that pages feel inhabited. Avoid Lorem ipsum, generic AI marketing copy, repeated descriptions, empty pages, empty links, dead buttons, irrelevant stock imagery, and one-size-fits-all cards. Every significant navigation and call-to-action link should work. Don't simulate booking or purchasing functionality.

Polish the homepage in particular: dramatic hero, responsive and asymmetric layouts, clear editorial hierarchy, meaningful imagery, and visible diversity among module layouts. Most of the interesting sections should be **independently editable Joomla module instances**.

If a technical assumption proves wrong, inspect the actual Joomla source/behavior, adapt using established Joomla mechanisms, and document the decision. Do not quietly weaken a non-negotiable requirement.

## 9. Testing and acceptance

Run the site at `https://jwcdemo.akeeba.dev` and inspect actual rendered pages with the available browser tools. Test desktop, tablet, and mobile sizes, and resolve significant defects.

Verify:

1. Joomla 6.1 is installed, database-backed, and functioning.
2. An actual **Cassiopeia child template** is installed, enabled, and used by the frontend.
3. **Bootstrap 5.3** is used without competing/conflicting framework assets.
4. The **exact** semantic and surface palette is present in compiled styles, utility classes, and components, with `data-bs-theme="dark"`.
5. The homepage is assembled predominantly from Joomla modules with real positions, order, and menu assignments.
6. Main navigation reaches functioning Exhibitions, Collection, What's On, Journal, Visit, and About pages.
7. Minimum sample-content counts are met and article/category/field relationships work.
8. Artifact fields render properly, including on the custom detail layout.
9. Overrides and alternative layouts render as intended.
10. The **Artifact Spotlight** module works and does not expose unpublished content.
11. Required artwork was generated through **BFL FLUX.2 [pro]**, with prompts/manifests/scripts retained under `.docs`, and the resulting assets load locally.
12. Images and other assets load; no important links are broken and no significant PHP or browser-console errors remain.
13. Responsive behavior, keyboard navigation, focus styles, and basic accessibility are satisfactory.
14. A Joomla administrator can change content and module assignments without touching code.
15. `AGENTS.md`, the verbatim `.docs/kickoff-prompt.md`, documentation, scripts, and `.gitignore` exist and work as claimed.
16. `git status` does not contain Joomla core, `.plans`, credentials, or accidental runtime files.

Do not claim unperformed tests have passed. If external access, credentials, or image generation is blocked, clearly distinguish implemented functionality from unverified or incomplete work.

## 10. Final report

When finished, give a **concise** report covering:

- Local URL and what was implemented.
- Cassiopeia-child/Bootstrap 5.3 strategy and relevant custom paths.
- How the homepage is composed: module positions, module types, content sources, menu assignments, and the most interesting overrides.
- Artifact Spotlight and other custom extension details.
- How to rerun the Bash/PHP build, fixture, and FLUX.2 [pro] image-generation scripts.
- Tests actually completed and any outstanding issues/blockers.

The core point of the presentation is not merely that AI can generate attractive markup. It is that an agent can build a sophisticated, maintainable **Joomla website using Joomla's native architecture**—including a genuine Cassiopeia child template, Bootstrap 5.3, content fields, modules, menu assignments, and targeted overrides—**without a page builder**.

**Proceed with the implementation.**

---

# Appendix A — Session refinements (2026-10-12)

The brief above is unchanged. This appendix records refinements agreed during
the build session that future runs must implement from the start.

## A.1 Responsive imagery pipeline (extends §4)

All layout-rendered imagery must be delivered through a template-resident
helper class (this build: `templates/cassiopeia_muf/html/MufImage.php`,
`require()`'d from the template overrides). Requirements:

- Generate resized derivatives **locally with PHP** (Imagick preferred, GD
  fallback — a working backend must be verified at runtime and failures must
  fall through to the next backend; do not assume Imagick can decode JPEG).
- Transcode derivatives to **WebP when either backend supports it** (check
  both Imagick formats and `imagewebp()`).
- Derivatives live in a dedicated, **gitignored** subdirectory of the
  template's media directory (`media/templates/site/cassiopeia_muf/derived/`);
  they regenerate automatically when the source file is newer.
- Output `<picture>` elements: `<source type="image/webp" srcset sizes>` plus
  an `<img>` carrying the JPEG srcset, the original full-size image as the
  final `src`, intrinsic `width`/`height` (CLS), and accurate `sizes` per
  layout context. Resizing must never re-run AI generation.

## A.2 CLI provisioning facts (extends §6/§8)

- The console bootstrap has **no site router**: never call `Route::_()` in
  provisioning code. Build SEF paths deterministically and make any
  placeholder resolution self-healing (detect placeholders, empty `href=""`,
  or stale prefixes; never overwrite bodies that were hand-edited afterwards).
- Register component and plugin **PSR-4 namespaces manually** before booting
  components in a CLI script, and define `JPATH_COMPONENT*`.
- Nested-set tables (categories, menu items) require `setLocation()` before
  `store()`; add a repair pass for rows saved without it.
- `fulltext` is a reserved word — always quote it in hand-written SQL.
- Joomla 6 specifics: `FieldsHelper` is
  `Joomla\Component\Fields\Administrator\Helper\FieldsHelper`; `mod_search`
  no longer ships (search modules need `mod_finder` + a com_finder index);
  category blog items only populate `lead_items`/`intro_items`/`link_items`
  for the blog layout — custom category layouts read `$this->items`;
  category-listing article items have **no `->link` property** and article
  items have no `category_route` — build routes explicitly.
- Layout class names must not collide between page wrappers and grid
  compositions (incidents: `muf-artifact`, `muf-journal-lead`).
- The BFL API key may be supplied via `op read` with a 1Password secret
  reference; never echo secrets (keep them out of deprecation traces), and
  write image provenance to the manifest **per image** so interrupted batch
  runs lose nothing. Generators should accept a `--limit` for bounded runs.
- Verify image derivatives actually render in the **web** SAPI, not just the
  CLI SAPI (this machine's php-fpm Imagick cannot decode JPEG; GD can).

## A.3 Language

All agent responses must be in English.
