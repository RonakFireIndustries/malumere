---
version: alpha
name: Ma Lumière — Dark Espresso
description: A dark-first editorial clinic system. Warm-black canvas and espresso surfaces hold cream Cormorant display type, quiet Manrope body copy, champagne-metal accents, cream pill buttons, arch-topped photography and hairline rules. Adapted from the Oura DESIGN.md in voltagent/awesome-design-md and inverted into a permanent night.
colors:
  canvas: "#0a0806"
  canvas-soft: "#0f0b08"
  espresso: "#17100b"
  surface: "#1a120c"
  surface-2: "#22170f"
  surface-3: "#2c1d13"
  brown: "#3a271a"
  ink: "#f4ede3"
  ink-strong: "#fbf6ee"
  stone: "#c8b9a8"
  muted: "#9a8b7b"
  gold: "#c9a26f"
  gold-soft: "#debe8e"
  gold-deep: "#a87f4f"
  hairline: "rgba(244, 237, 227, 0.12)"
  hairline-strong: "rgba(244, 237, 227, 0.22)"
  on-gold: "#1a120c"
  error: "#e07a66"
  success: "#82b89a"
  warning: "#d9b072"

typography:
  display:
    fontFamily: Cormorant Garamond
    fontSize: clamp(2.6rem, 6vw, 4.6rem)
    fontWeight: 400
    lineHeight: 1.12
  display-hero:
    fontFamily: Cormorant Garamond
    fontSize: 4rem
    fontWeight: 400
    lineHeight: 1.12
  heading-2:
    fontFamily: Cormorant Garamond
    fontSize: clamp(2rem, 4.5vw, 3.2rem)
    fontWeight: 400
    lineHeight: 1.12
  heading-3:
    fontFamily: Cormorant Garamond
    fontSize: clamp(1.45rem, 3vw, 2rem)
    fontWeight: 400
    lineHeight: 1.12
  lead:
    fontFamily: Manrope
    fontSize: 1.12rem
    fontWeight: 400
    lineHeight: 1.7
  body:
    fontFamily: Manrope
    fontSize: 1rem
    fontWeight: 400
    lineHeight: 1.7
  body-sm:
    fontFamily: Manrope
    fontSize: 0.9rem
    fontWeight: 400
    lineHeight: 1.7
  button:
    fontFamily: Manrope
    fontSize: 0.95rem
    fontWeight: 600
    lineHeight: 1
  eyebrow:
    fontFamily: Manrope
    fontSize: 0.72rem
    fontWeight: 700
    lineHeight: 1.2
    letterSpacing: 0.18em

rounded:
  sm: 8px
  md: 16px
  lg: 26px
  arch: "999px 999px 26px 26px"
  full: 999px

spacing:
  xs: 0.5rem
  sm: 0.875rem
  md: 1.5rem
  lg: 2.5rem
  xl: 4rem
  2xl: clamp(5rem, 10vw, 8rem)

components:
  header:
    backgroundColor: "rgba(10, 8, 6, 0.82)"
    textColor: "{colors.ink}"
    borderColor: "{colors.hairline}"
    rounded: "{rounded.none}"
  hero:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink-strong}"
    typography: "{typography.display-hero}"
  button-primary:
    backgroundColor: "{colors.ink}"
    textColor: "{colors.canvas}"
    typography: "{typography.button}"
    rounded: "{rounded.full}"
  button-outline:
    backgroundColor: transparent
    textColor: "{colors.ink}"
    borderColor: "{colors.hairline-strong}"
    typography: "{typography.button}"
    rounded: "{rounded.full}"
  chip:
    backgroundColor: "{colors.surface-2}"
    textColor: "{colors.ink}"
    borderColor: "{colors.hairline}"
    rounded: "{rounded.full}"
  card:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.stone}"
    borderColor: "{colors.hairline}"
    rounded: "{rounded.lg}"
  input:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    borderColor: "{colors.hairline-strong}"
    rounded: "{rounded.md}"
  footer:
    backgroundColor: "{colors.canvas-soft}"
    textColor: "{colors.stone}"
    borderColor: "{colors.hairline}"

# Ma Lumière — Dark Espresso Design System

The site is permanently dark. It takes Oura's warm-paper editorial system and
inverts it: where Oura begins on cream and drops into black bands, Ma Lumière
begins in the dark and lifts into espresso-brown surfaces. The result should
read like a candle-lit clinic journal — quiet, expensive, warm — never like a
generic "dark mode" with blue greys.

The implementation lives in `assets/css/site.css` (`:root` tokens at the top).
The booking widget mirrors the tokens in
`wp-content/plugins/ma-lumiere-clinic-manager/public/css/booking.css`.

## Overview

Three surface worlds pace the scroll:

1. **Abyss** (`--color-canvas` #0a0806) — the page floor and default section.
2. **Espresso** (`--color-espresso` #17100b / `--color-surface` #1a120c) —
   tinted bands, journey, CTA, footer and resting cards.
3. **Clay light** (`--color-surface-2` #22170f / `--color-surface-3` #2c1d13) —
   elevated cards, chips, inputs and hover states.

Cream type and champagne accents supply all of the contrast. Depth comes from
surface shifts and 1px hairlines, not from heavy shadows.

**Key characteristics**

- Warm-black canvas, never pure `#000` as a large flat field.
- Cormorant Garamond for display and headings; Manrope for body and UI.
- Cream pill primary buttons; outline pills for secondary actions.
- Champagne gold reserved for eyebrows, icons, links on hover and hairline
  emphasis — not for large fills.
- Arch-topped photography (`--radius-arch`) plus soft 26px card corners.
- Section rhythm is generous; empty dark space is part of the composition.

## Colors

### Surface

| Token | Value | Use |
|---|---|---|
| `--color-canvas` | `#0a0806` | Page floor, hero base, default sections |
| `--color-canvas-soft` | `#0f0b08` | Footer, quiet closing band |
| `--color-espresso` | `#17100b` | Tinted sections, gradient end stop |
| `--color-surface` | `#1a120c` | Cards, inputs, booking panel |
| `--color-surface-2` | `#22170f` | Elevated cards, chips, icon wells |
| `--color-surface-3` | `#2c1d13` | Deep brown highlight in gradients |
| `--color-brown` | `#3a271a` | Clay accent surface |

### Text

| Token | Value | Use |
|---|---|---|
| `--color-ink` | `#f4ede3` | Primary text, nav, buttons' fill |
| `--color-ink-strong` | `#fbf6ee` | Display headings |
| `--color-stone` | `#c8b9a8` | Body copy |
| `--color-muted` | `#9a8b7b` | Meta, captions, quiet labels |

### Accent & semantic

| Token | Value | Use |
|---|---|---|
| `--color-gold` | `#c9a26f` | Eyebrows, icons, focus ring, links |
| `--color-gold-soft` | `#debe8e` | Hover, italic emphasis in headings |
| `--color-gold-deep` | `#a87f4f` | Pressed metal |
| `--color-on-gold` | `#1a120c` | Text on gold fills |
| `--color-error` | `#e07a66` | Validation |
| `--color-success` | `#82b89a` | Confirmations |
| `--color-warning` | `#d9b072` | Warnings |

### Legacy aliases

Templates still reference the old light palette. These names are kept and
remapped so no template needs rewriting:

| Legacy | Maps to |
|---|---|
| `--color-ivory` | `--color-ink` (text, **not** a background) |
| `--color-cream` | `--color-espresso` (tinted section ground) |
| `--color-champagne` | `--color-gold` |
| `--color-sand` | `--color-gold-soft` |
| `--color-rose` / `--color-rose-deep` | `--color-gold` / `--color-ink` |
| `--color-rose-tint` | `--color-surface-2` |
| `--color-charcoal` | `--color-ink` |
| `--color-slate` | `--color-muted` |
| `--color-line` / `--color-line-dark` | hairlines |
| `--color-white` | `--color-surface` |

Never use `--color-ivory` as a section background — it is cream text now.

## Typography

- **Display / headings** — Cormorant Garamond, weight 400, line-height 1.12.
  Large sizes carry the elegance; do not bold them up.
- **Body / UI** — Manrope. Body 1rem/1.7 in `--color-stone`.
- **Eyebrows** — Manrope 0.72rem, 700, uppercase, `0.18em` tracking, gold.
  Usually flanked by a short hairline rule.
- **Italic emphasis** — serif italics in `--color-gold-soft`, used sparingly in
  hero and section headlines.

Hierarchy is created by scale and space, not weight or colour saturation.

## Layout

- Container `1200px`, narrow `860px`; gutter `clamp(1.25rem, 4vw, 2.5rem)`.
- Section padding: `--sp-xl` inside a section, `--sp-2xl`
  (`clamp(5rem, 10vw, 8rem)`) between worlds.
- Header height token `--header-h: 84px`; hero offsets by it.
- Grids collapse 4 → 2 → 1; hero and doctor sections stack below 860px.
- Breakpoints in use: 1024px, 860px, 560px.

## Elevation & depth

| Level | Treatment | Use |
|---|---|---|
| 0 | No shadow, hairline only | Most cards, hero media, images |
| 1 | `--shadow-sm` | Resting cards |
| 2 | `--shadow-md` | Hover / floating cards |
| 3 | `--shadow-lg` | Modals, sticky panels |

Photography is framed with a 1px `--color-hairline` ring rather than a blurred
drop shadow, which otherwise reads as a grey rectangle on the dark canvas.

## Shapes

| Token | Value | Use |
|---|---|---|
| `--radius-sm` | 8px | Small controls |
| `--radius-md` | 16px | Inputs, selects, textareas |
| `--radius-lg` | 26px | Cards, booking panel |
| `--radius-arch` | `999px 999px 26px 26px` | Hero and doctor media |
| `--radius-full` | 999px | Buttons, chips, icon wells |

## Components

### Header

Translucent warm-black (`rgba(10, 8, 6, 0.82)`) with `backdrop-filter: blur`,
a bottom hairline, cream nav links, and a cream pill for "Book Consultation".
On scroll the background becomes more opaque. The bundled black line-art logo
is inverted to cream; uploaded custom logos are left untouched.

### Hero

Vertical gradient from `#120c08` into the canvas, with two soft champagne /
clay radial glows. Do **not** use diagonal (`160deg`) gradients here — they
band visibly across the wide viewport. Media uses the arch radius and a
hairline ring.

The decorative `::after` glow circle must fade fully to transparent *inside*
its own circular clip (e.g. a centred gradient with a 50% stop). If the colour
is still visible where the circle is clipped, the arc reads as a hard seam
against the canvas.

### Buttons

| Variant | Fill | Text | Border |
|---|---|---|---|
| `.btn--primary` | `--color-ink` cream | `--color-canvas` | none |
| `.btn--outline` | transparent | `--color-ink` | `--color-hairline-strong` |
| `.btn--light` | `--color-ink` cream | `--color-canvas` | none |
| `.btn--rose` (accent) | `--color-gold` | `--color-on-gold` | none |

Primary and light pills hover to champagne gold; outline pills invert to a
cream fill. All pills are `--radius-full`, with a quiet lift on hover. Focus is
a 3px gold halo.

### Cards

`--color-surface` or `--color-surface-2`, 1px hairline, 26px radius, generous
padding. Hover shifts the border to `--color-hairline-strong` and may raise the
card by a few pixels; avoid large glows.

### Treatment pages

The treatments landing page keeps its split hero with a three-image collage,
then lists every treatment as one flat, uncategorised sequence:

```
[text]   [image]   01
[image]  [text]    02
[text]   [image]   03
```

Each row carries an index number, title, short description, duration, sessions,
and downtime, plus a link to the treatment. The image targets the same page.
There is no category grouping and no index rail. Landing rows use 4:3 media on
desktop and when stacked, and 1:1 media between 861px and 1024px.

The single-treatment body is an editorial sequence of alternating rows:

```
[text]   [image]     hero
[image]  [text]      intro + how it works
[text]   [image]     who it is for + concerns
[image]  [text]      process + expected results
[       care band       ]  preparation & aftercare
```

Rows are two equal columns with a `clamp` gap, the media on a 4:5 ratio and a
26px radius. Media alternates sides with `order`, so
the DOM stays text-first for mobile and screen readers. On mobile every row
stacks text then image at 4:3.

The treatment's own featured image anchors the process row. Earlier rows use
hand-picked related imagery, then the site's editorial portraits (hero image +
large unused media-library images), never another treatment's featured image.
Preparation/aftercare breaks the alternation as a full-width band with two care
cards. The booking rail is sticky above 1024px and becomes a static two-column
panel below that; below 860px it stacks.

### Forms

Inputs, selects and textareas are `--color-surface` with
`--color-hairline-strong` borders and cream text. Placeholders are
`--color-muted`. Focus adds the gold halo. `color-scheme: dark` is set on
`:root` so native selects, pickers and scrollbars stay dark. Checkboxes and
radios use `accent-color: var(--color-gold)`.

### Booking widget

Rendered by the `ma-lumiere-clinic-manager` plugin. Its panel, step rail,
treatment cards and summary all use the same dark tokens; keep
`booking.css` in sync when palette tokens change.

### Footer

`--color-canvas-soft`, hairline top border, cream wordmark, muted link columns
and gold hover. Contact details use small outlined gold icon wells.

## Do's and don'ts

### Do

- Default every section to the warm black canvas and lift into espresso brown.
- Use cream for primary actions; keep gold for accents and small details.
- Use hairlines and surface shifts for separation.
- Use the arch radius on people/hero imagery and 26px on content cards.
- Keep body copy at `--color-stone` or brighter for readability.

### Don't

- Don't introduce blue-grey "dark mode" surfaces, pure-white cards or light
  section backgrounds.
- Don't use gold as a large button fill unless the action is genuinely
  secondary to the cream primary.
- Don't add heavy blurred shadows to images; use the 1px ring.
- Don't use diagonal gradients on full-width sections.
- Don't set body copy in Cormorant; it is display-only.
- Don't remove the legacy aliases without updating every template first.
