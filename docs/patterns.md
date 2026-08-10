# AJNanda Patterns

AJNanda ships a library of Gutenberg block patterns — reusable page sections
(Hero, Services, CTA, FAQ, etc.) that insert as ordinary blocks. Nothing here
depends on a page builder; everything is core WordPress patterns plus the
theme's existing design system (`theme.json` presets, `style.css` custom
properties, and the `builder-*` / `is-style-ajnanda-*` classes already used
throughout the theme).

## How they work

Every pattern lives as one file in `/patterns`, using WordPress core's
built-in pattern auto-registration (available since WP 6.0, for classic and
block themes alike): any `.php` file placed in a theme's `/patterns`
directory with a valid header comment is registered automatically on
`init` — nothing in `functions.php` needs to change when a pattern is
added, removed, or edited.

```php
<?php
/**
 * Title: Hero — Dark
 * Slug: ajnanda/section-hero-dark
 * Categories: ajnanda-hero
 * Keywords: hero, dark, banner
 * Description: A full-width dark hero using the theme's dark tone modifier.
 *
 * @package AJNanda
 */
?>
<!-- wp:group {...} -->
...
<!-- /wp:group -->
```

The file's body (executed as PHP, output captured and used as the pattern's
`content`) is the block markup that gets inserted. Because the file is
actually `include`d, PHP is allowed there too — that's what the Page Design
composer helper relies on (see `docs/page-designs.md`).

## Naming conventions

- **Slug prefix**: new section patterns use `ajnanda/section-{category}-{name}`,
  e.g. `ajnanda/section-hero-super-bold`, `ajnanda/section-cta-dark`.
- **Legacy patterns**: the 14 patterns that existed before the site-builder
  work (started at version 1.2.7) keep their
  original `ajnanda-pro/*` slugs unchanged (`patterns/legacy-*.php`) — slugs
  are treated as stable public identifiers (see "Future automation" below),
  so they are never renamed, only added to.
- **Page designs**: `ajnanda/page-{page-type}-{variant}`, e.g.
  `ajnanda/page-home-super-bold`. See `docs/page-designs.md`.
- **File naming**: the PHP filename doesn't need to match the slug, but by
  convention it does, minus the `ajnanda/section-`/`ajnanda/page-` prefix
  (`patterns/hero-dark.php` → `ajnanda/section-hero-dark`).

## Categories

Registered in `inc/patterns.php` (`register_block_pattern_category()`),
because block-pattern *categories* — unlike pattern content — have no
directory-based auto-registration and must be registered in PHP:

| Category slug | Label |
|---|---|
| `ajnanda-hero` | AJNanda: Hero |
| `ajnanda-services` | AJNanda: Services |
| `ajnanda-content` | AJNanda: Content |
| `ajnanda-social-proof` | AJNanda: Social Proof |
| `ajnanda-data` | AJNanda: Stats & Data |
| `ajnanda-cta` | AJNanda: Calls to Action |
| `ajnanda-faq` | AJNanda: FAQ |
| `ajnanda-team` | AJNanda: Team |
| `ajnanda-contact` | AJNanda: Contact |
| `ajnanda-footer` | AJNanda: Footer & Auxiliary |
| `ajnanda-page-designs` | AJNanda: Page Designs (see docs/page-designs.md) |
| `ajnanda-builder` | AJNanda Sections (Legacy) — the original 14 patterns |

## Design system reuse

Patterns are built entirely from:

- **theme.json presets**: font sizes (`small` … `huge`), spacing sizes
  (`xs` … `2-xl`), the 7-color palette — applied as `fontSize`/`className`
  block attributes, never inline hex values.
- **Existing structural classes** already defined in `style.css`:
  `builder-section` / `builder-section-soft`, `builder-hero-section` (+
  `hero-width-*` / `hero-height-*` / `hero-text-*` modifiers),
  `builder-split`, `builder-cta-panel`.
- **Existing native block styles**, registered client-side in
  `js/editor-controls.js` (`registerAjnandaBlockStyles()`) —
  `is-style-ajnanda-card` / `-card-elevated` / `-card-bordered` /
  `-card-soft` / `-card-linked`, `-eyebrow`, `-icon-tile` /
  `-icon-tile-round`, `-checklist` / `-checklist-inline`,
  `-equal-height`. **Do not re-register these server-side** — they're
  already real, togglable Gutenberg block styles; patterns just reuse the
  classNames.
- **The new dark "tone" modifier** (`section-tone-dark` / `hero-tone-dark`,
  added in `style.css`), following the same plain-className-modifier
  convention as `hero-width-*` rather than introducing a second styling
  system.
- **WordPress core blocks only** inside pattern content: group, columns,
  heading, paragraph, buttons, list, quote, details (native accordion,
  WP 6.4+), query/post-template (native post loop), shortcode. No custom
  JS, no third-party blocks.

## Image placeholders

Patterns never reference a real attachment ID (which would render broken on
a fresh install). Where a pattern needs an image, it uses a styled
`is-style-ajnanda-card-soft` placeholder group with instructional text
("Add an image here") — replace it with a real Image block after inserting.

## Adding a new pattern

1. Create `patterns/{name}.php` with a header comment (`Title`, `Slug`,
   `Categories`, `Keywords`, `Description`) and block markup after it.
2. Reuse an existing category from the table above, or add a new one in
   `inc/patterns.php`.
3. Reuse existing `builder-*`/`is-style-ajnanda-*` classes and theme.json
   presets before reaching for anything new.
4. `wp ajnanda pattern list` (see `docs/development.md`) to confirm it
   registered.

## Color schemes

AJNanda already has a real, native color system: 4 `WP_Customize_Color_Control`
pickers — Primary Color, Primary Hover Color, Secondary Color, Accent Color
(`theme_primary_color` / `theme_primary_dark_color` / `theme_secondary_color`
/ `theme_accent_color`) — registered under the built-in
**Appearance → Customize → Colors** panel, output as `:root` CSS custom
properties by `ajnanda_customizer_css()` on `wp_head`. That system already
gives unlimited custom colors natively; `inc/color-schemes.php` does **not**
duplicate it with a second setting. It adds three things on top:

1. **Closes the editor gap**: `ajnanda_customizer_css()` only hooks
   `wp_head`, so a site's real brand colors never reached the block
   editor's iframe — confirmed against a real AJNanda site whose actual
   brand color was correctly live on the frontend while "Choose a pattern"
   still previewed default blue, because that modal's previews render
   inside the iframed editor. `enqueue_block_editor_assets` and the
   `block_editor_settings_all` filter now push the same saved colors into
   both.
2. **One-click preset swatches** (20 named presets — see
   `ajnanda_get_color_schemes()` in `inc/color-schemes.php` for the full
   list) added to the top of the native Colors panel — clicking one just
   fills in the 4 existing color pickers via the Customizer JS API
   (`wp.customize(id).set()`). It does not introduce a new stored setting;
   `wp ajnanda color-scheme list` reports `custom` whenever the saved
   colors don't exactly match a preset (e.g. hand-picked in the color
   pickers), which is expected and fine.
3. **Per-page override**: the Page Library admin screen's "Color scheme"
   picker defaults to the site's current colors (so new pages match by
   default) and, when a different preset is deliberately chosen, wraps
   that one page's content in a single
   `<!-- wp:group className="ajnanda-scheme-{slug}" -->` — an ordinary,
   fully editable group block — with an inline warning that the page will
   look different from the rest of the site.

Gradients: theme.json registers 12 AJNanda gradient presets (Primary↔Dark,
Primary↔Secondary, Primary→Accent, Secondary→Accent, Brand Spectrum,
Dark↔Dim, two radial glows, and a light Soft Fade) built from e.g.
`linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%)` —
because they reference the live CSS variables rather than fixed hex
values, they automatically follow whatever colors are currently active
(preset or fully custom), with zero extra plumbing. They appear in the
native Gradient Picker on any block with gradient support (Cover, Buttons,
Group background, etc.). Two section patterns already use one — **Hero —
Gradient** and **CTA — Gradient** — both pair the gradient background with
the existing `hero-tone-dark`/`section-tone-dark` class for readable white
text/buttons rather than hand-coloring individual blocks (core/button
skip-serializes color the same way it skip-serializes font-size, so its
color is always driven by a className/context, never a backgroundColor/
textColor attribute, in every AJNanda pattern).

CLI: `wp ajnanda color-scheme list` / `wp ajnanda color-scheme set <slug>`
(sets the 4 real color settings from a preset); `wp ajnanda page-design
insert ... --color-scheme=<slug>` for a single page.

**Preview before applying**: AJNanda → Color Schemes shows every preset
with a "Preview" link that renders a real page with that scheme applied —
no setting is changed, nothing is saved (see `docs/development.md`,
"Preview"). The Page Library screen's color picker has the same live
preview link, tied to whichever scheme is currently selected there. Every
Preview link across the AJNanda admin screens (Patterns, Page Library,
Starter Sites, Color Schemes) opens in an in-page modal by default rather
than a new tab, with a fallback link to open it in a new tab anyway.

## AJCore forms

AJNanda has no form system of its own. Where a pattern needs a form
("Contact — Form + Information", "Newsletter CTA"), it embeds AJCore's
`[ajforms id="..."]` shortcode via the core Shortcode block, with a
placeholder ID and an inline note telling the site owner to swap in a real
form ID from **AJ Core → Forms**. AJCore forms are plain DB rows with a
per-site numeric ID — there's no way to hard-code a working one into a
theme-distributed pattern, so this placeholder approach is intentional, not
a TODO.
