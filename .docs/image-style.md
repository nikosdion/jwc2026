# Image style — The Museum of Unbuilt Futures

All bespoke illustrative imagery on this site is generated with **Black Forest
Labs FLUX.2 [pro]** through the official BFL API. This document is the single
source of truth for how that imagery is commissioned, generated and stored.

## The fixed prompt frame

Every generation prompt is assembled by `.docs/scripts/generate-images.php`
as exactly:

```
{PREFIX} {subject description} {SUFFIX}
```

- **PREFIX (exact, never altered):**
  `1980s wireframe 3D rendering, neon wireframe colors, soft shadowing, dark background`
- **SUFFIX (exact, never altered):**
  `No readable text, no labels`

Between them sits a distinctive subject description written for the particular
exhibit, exhibition, event or section. Prompts are stored as subject
descriptions only, in `.docs/image-prompts.json`; the script adds the frame.

## Visual direction

- Arresting **wireframe renderings**, not photographic realism. The museum's
  diegetic explanation: the Restoration Laboratory "reconstructs" lost
  machines as luminous wireframe studies.
- Neon accents (electric cyan, arcade violet, laser magenta) against deep
  space navy — the same palette as the site (`#36F5F0`, `#A77BFF`, `#FF4FD8`
  on `#090B1A`).
- Titles, labels, dates and technical captions are **real HTML**, never
  baked into imagery.
- Purely decorative layout motifs (gridlines, separators, the visit-page
  transit map) may be CSS or hand-authored SVG — these are not content
  imagery and are never presented as generated art.

## Formats and sizes

| Slot | Size | Aspect | Used by |
|---|---|---|---|
| hero / exhibition | 1440 × 810 | 16:9 | homepage hero, exhibition heroes |
| artifact | 1080 × 1080 | 1:1 | collection tiles, spotlight, artifact detail |
| editorial portrait | 1080 × 1350 | 3:4 | Curator's Pick, journal entries |
| journal wide | 1440 × 810 | 16:9 | Journal lead images, pages |

Files are downloaded as JPEG and stored under `images/muf/<id>.jpg`, served
locally. No runtime requests to BFL (or any image host) happen on the site.

## Model and endpoint

- Model: `flux-2-pro` via `POST {BFL_BASE_URL}/v1/flux-2-pro`
- Auth: `x-key: $BFL_API_KEY` (never committed)
- Base URL default `https://api.bfl.ai`; override with `BFL_BASE_URL`
  (e.g. `https://api.eu.bfl.ai`) — the model path never changes.
- Async flow: submit → poll the returned `polling_url` → on `Ready`,
  download `result.sample` **immediately** (hosted URLs expire after minutes).

## Output rules

- Generated files are committed to Git (they are public site imagery).
- Provenance (prompt, model, size, seed if returned, generation time) is
  written back into `.docs/image-prompts.json` by the generator.
- Regeneration is deliberate: `--id=<id>` regenerates one image,
  `--missing` only what is absent, `--all` everything (confirm first —
  generation is billed).
- Never substitute another model, stock photography or procedural SVG for
  content imagery. If the API is unavailable, report the blocker instead.
