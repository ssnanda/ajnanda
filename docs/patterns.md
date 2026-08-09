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
- **Legacy patterns**: the 14 patterns that existed before 1.3.0 keep their
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

## AJCore forms

AJNanda has no form system of its own. Where a pattern needs a form
("Contact — Form + Information", "Newsletter CTA"), it embeds AJCore's
`[ajforms id="..."]` shortcode via the core Shortcode block, with a
placeholder ID and an inline note telling the site owner to swap in a real
form ID from **AJ Core → Forms**. AJCore forms are plain DB rows with a
per-site numeric ID — there's no way to hard-code a working one into a
theme-distributed pattern, so this placeholder approach is intentional, not
a TODO.
