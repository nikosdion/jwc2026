# The Museum of Unbuilt Futures — jwc2026

Demo for creating a Joomla! site with AI

This is part of my **"Practical AI for Joomla Developers: Beyond the Hype and
the Panic"** presentation for the Joomla World Conference 2026.

## The site's brief

We are creating a site for a fictional museum called "The Museum of Unbuilt
Futures". Their mission is "preserving the tomorrows that never happened":
it collects unrealized inventions of the late 19th–20th century — personal
nuclear automobiles, transatlantic pneumatic railways, robot butlers — and
presents them with the seriousness of a real cultural institution.

The site has an events calendar built with core content and custom fields,

We are going for a 1980s retrofuturistic aesthetic: dark mode, neon colors,
wireframe 3D graphics (with light shading to make it look retrofuturistic
instead of 1980s bleak), monospace fonts. This is implemented as a custom
Cassiopeia child theme.

> [!IMPORTANT]
> For the custom theme to compile, you need to provide the location of the
> Joomla working copy. Checking out the latest dev branch of Joomla is not
> enough. You will also need to run `npm ci` in it. You will also need
> `scss` in your path to compile SCSS to CSS.

## What makes this interesting for Joomla developers

Everything is standard, administrator-maintainable Joomla 6.1 — produced
from a single prompt:

- A genuine **Cassiopeia child template** (`templates/cassiopeia_muf`) with a
  custom dark retrofuturist palette compiled **into** Bootstrap 5.3's Sass
  (dark color mode, exact semantic tokens, local OFL fonts).
- **Custom fields** drive the archival record on every artifact (year,
  inventor, origin, reference, reason abandoned) — editable in the backend,
  rendered through layouts.
- A custom **Artifact Spotlight** module (Joomla 6 DI architecture) with
  random/latest/specific selection and sub-category support.
- The homepage is assembled from **eight independently assigned modules**;
  category listings, article details and module layouts are template
  overrides, including an article dispatcher that routes by field record.
- All bespoke imagery was generated with **BFL FLUX.2 [pro]** through a
  reproducible pipeline (prompt manifest, provenance, regeneration script),
  and is served through an on-device responsive **WebP/JPEG `<picture>`
  pipeline** — no build tools beyond the Sass CLI.
- Idempotent **PHP provisioning CLI**: categories, fields, articles, menus,
  modules and assignments rebuild from fixtures without duplication.

## The kickoff script

**[`.docs/kickoff-prompt.md`](.docs/kickoff-prompt.md)** is the complete
kickoff prompt given to the AI coding assistant. It is the single document
from which the entire site — template, module, fixtures, documentation,
scripts and generated artefacts — was produced, and it carries an appendix
of refinements discovered during the session. Point an agent at a fresh
Joomla 6.1 install with that file and this repository's tracked contents,
and it can rebuild the whole site.

## Rebuild from a fresh Joomla 6.1

```bash
# after copying the tracked folders into a Joomla 6.1 site root:
php .docs/scripts/provision.php      # structure + content (idempotent)
.docs/scripts/build-css.sh           # compile the Sass
export BFL_API_KEY='bfl_...'
php .docs/scripts/generate-images.php --missing

# or everything at once (preflight → provision → css → images → verify):
.docs/scripts/build-demo.sh
```

See `.docs/README.md` for the full reference, `.docs/design.md` for the
design system, and `.docs/architecture.md` for the Joomla structure.

## Super User credentials

| | |
|---|---|
| Username | `admin` |
| Password | `@dminU$3R123` |

These are the credentials baked into this demonstration instance. **When
restoring the site from a backup archive, change the Super User password
immediately** (Users → Manage, or during the restoration's finalise step).

📦 **Backup archive:** [download from iCloud](https://www.icloud.com/iclouddrive/0cd6rf9DlUxq1cy0dtZSPZXcw)

## A few words about the intention

Generative AI **does not** replace the human site integrator; it's a tool to
make their life easier.

The site you get is a good starting point, not a complete deliverable. The AI
step is just there to automate a lot of the boring stuff. Setting up Joomla,
creating modules, configuring everything, putting up some sample content,
creating the child template and its build pipeline, simple custom extensions
(modules), etc. You still need to provide a detailed brief, you still need to go
through the entire site and touch things up, you still need to make changes to
the custom template, test on mobile, optimise performance, optimise SEO and so
on. For some of these you can use the AI agent to automate or at least save
time. Other steps require good, old-fashioned human effort.

> [!TIP]
> If you are building an e-shop you can very much use generative AI to wade
> through product lists delivered in various random formats by the client and
> insert them into the site, saving you hours to weeks of work.
>
> Same goes for content-heavy sites where the client provides everything as a
> mishmash of Word documents, PDF files, and in some cases hardcopy. There is no
> point in being a hero doing all that boring work when you can have the
> computer do it for you.

Another point I'd like to make is that generative AI does not make page builder
extensions obsolete. Using generative AI to create a site The One True Joomla
Way (custom template, modules, lots of config) is a diametrical opposite process
to how you build sites with a page builder, or why you use this kind of software
in the first place. The idea behind The One True Joomla Way of building sites is
that everything is built out of blocks which are infinitely configurable. This
is great for long-term sites (future upgrades and template refreshes or rebuild
will require minimal if any changes to the content) but front-load the work
during initial implementation.

My whole point with this repo is to show how you can use generative AI to
automate a lot of the initial, time-consuming, boring work if you're building
sites the same way I do: custom template, modules, structured data, template
overrides. If that's not your jam, that's cool. Joomla does not lock you into a
specific build process (WordPress, I am looking at you!). You do you.

## Tools used for this build

All my AI coding assistants use my [project management skills](https://github.com/nikosdion/pm-skill-demo)
and [local development skills](https://github.com/nikosdion/devskills).

Communicating with Joomla from the AI coding assistant uses my
[MCP4Joomla](https://github.com/nikosdion/joomla-mcp-php) MCP server running on
my local machine (Linux or Mac).

I used Duck.ai, Siri AI, and ChatGPT for research, color cheme evaluation,
and prompt refinement. I used Kimi Code to implement the build. The build itself
uses BlackForestLabs' FLUX.2 [pro] to generate the illustrations of the
fictitious products.

The oversight, and any mistakes I may have made, are mine.

## Credits & licensing

- Site design, code, fixtures and documentation: generated by an AI coding
  assistant under the kickoff prompt above; reviewed by a human.
- Template code derives from Cassiopeia — GPL-2.0-or-later. Custom module —
  GPL-2.0-or-later.
- Fonts: Space Grotesk, Inter, Space Mono (SIL OFL 1.1), self-hosted.
- Imagery: generated with Black Forest Labs FLUX.2 [pro]; see
  `.docs/image-prompts.json` for per-image prompts and provenance.
- All persons, exhibits and events are lovingly invented. Any resemblance to
  actual futures, living or cancelled, is purely coincidental.

## License

The Museum of Unbuilt Futures — Demo for creating a Joomla! site with AI

Copyright (C) 2026 Nicholas K. Dionysopoulos

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, see <https://www.gnu.org/licenses/>.

