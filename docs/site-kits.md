# AJNanda Font Pairings & Site Kits

Two small systems, same shape as `inc/color-schemes.php` — a curated list
of named presets plus a "quick-fill" control in the Customizer. Neither
introduces a new design system or a page-builder-style canvas; both just
set existing things (CSS custom properties, a theme_mod) that the theme
already reads.

## Font Pairings (`inc/font-pairings.php`)

AJNanda originally hardcoded its two fonts directly in `style.css`
(`font-family: 'Inter', ...` on `body`, `'Poppins', ...` on headings) —
there was no way to change them without editing CSS. That's now driven by
two CSS custom properties instead:

```css
:root {
    --font-body: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    --font-heading: 'Poppins', sans-serif;
}
body { font-family: var(--font-body, 'Inter', ...); }
h1, h2, h3, h4, h5, h6 { font-family: var(--font-heading, 'Poppins', sans-serif); }
```

The `'classic'` pairing's values are exactly these original fonts, so
every existing site renders byte-for-byte identically unless a different
pairing is deliberately chosen — same backward-compatibility guarantee
`color-schemes.php`'s `'blue'` preset already gives.

### Presets

`ajnanda_get_font_pairings()` returns 6: **Classic** (Poppins/Inter, the
default), **Modern Sans** (Manrope), **Elegant Serif** (Playfair
Display/Lora), **Bold Display** (Bebas Neue/Inter — loud, poster-style
headings), **Playful Rounded** (Baloo 2/Nunito — soft, kid/hobby-friendly),
**Developer Mono** (JetBrains Mono/Inter — code-editor headings, clean
body — developer portfolios). The 9 underlying font families are also
registered in `theme.json` (`settings.typography.fontFamilies`), so every
one of them is also selectable per-block in the native block editor
Typography panel, independent of the site-wide pairing.

### How it's applied

- `theme_font_pairing` is a real Customizer setting (`default: 'classic'`,
  sanitized against the known slugs), with its own control — a set of
  radio "cards" (font-preview text rendered in that pairing's actual
  heading font) — under a new **Appearance → Customize → Typography**
  section.
- `ajnanda_output_active_font_pairing_css()` prints the active pairing's
  `:root{--font-heading:...;--font-body:...;}` on `wp_head`, at **default
  priority (10)** — this has to run *after* WP core prints enqueued
  stylesheets (`wp_print_styles` fires on `wp_head` at priority 8), or the
  override gets silently overwritten by `style.css`'s own hardcoded
  fallback values. Same reasoning `ajnanda_customizer_css()`
  (`functions.php`, the color system) already follows.
- `header.php`'s Google Fonts `<link>` is built dynamically from
  `ajnanda_get_active_font_pairing_google_fonts_url()` instead of a
  hardcoded URL — `'classic'` resolves to the exact same URL that used to
  be hardcoded there.
- **Not** `esc_attr()`: the CSS var values are quoted font names ("Bebas
  Neue") printed inside a `<style>` element, not an HTML attribute —
  `esc_attr()` would HTML-entity-encode the quotes into invalid CSS. The
  values only ever come from the fixed array in
  `ajnanda_get_font_pairings()`, never user input, so this is safe. (This
  was a real bug caught during testing, not a hypothetical one — worth
  knowing before touching this file.)
- Editor gap: `enqueue_block_editor_assets` loads **every** registered
  pairing's Google Fonts (not just the active one) so a manual per-block
  font choice always renders, and `block_editor_settings_all` pushes the
  active pairing's CSS vars into the iframed editor/pattern previews —
  same two-hook pattern `color-schemes.php` already uses for color.

## Site Kits (`inc/site-kits.php`)

A **kit** is a color scheme slug + a font pairing slug, bundled under one
name — nothing more. `ajnanda_get_site_kits()` returns 11: Corporate Blue,
Elegant Gold, Modern Tech, Bold Startup, Minimal Slate, Dark Premium,
Neon Night, Bubblegum Pop, Little One, Family Warmth, Developer
Portfolio.

- **"Quick Kits"** — a control at the very top of the native Colors panel
  (above the color-only "Quick presets" swatches), each button setting all
  5 underlying values at once (`theme_primary_color`,
  `theme_primary_dark_color`, `theme_secondary_color`,
  `theme_accent_color`, `theme_font_pairing`) via
  `wp.customize(id).set()` — still requires Publish, same as every other
  quick-preset in this theme. No new setting is introduced; a kit is just
  a shortcut through two registries that already exist.
- `ajnanda_get_active_site_kit_slug()` mirrors
  `ajnanda_get_active_color_scheme_slug()` — returns a kit slug only if
  *both* the saved color scheme and the saved font pairing exactly match a
  registered kit, `'custom'` otherwise.
- **AJNanda → Site Kits** admin screen: a visual reference (swatch, font
  preview, mood description, "Active now" pill) with a live preview per
  kit, plus a reference table of the 5 font pairings alone underneath for
  when only the fonts should change. Read-only, like Color Schemes — kits
  are applied from the Customizer.
- **Preview**: `ajnanda_get_preview_url($pattern_slug, $color_scheme,
  $font_pairing)` (`inc/preview.php`) takes both overrides together. Color
  uses the existing body-class approach (`.ajnanda-scheme-{slug}`); font
  uses `add_filter('theme_mod_theme_font_pairing', fn() => $slug)` instead
  — since it's a single theme_mod rather than 4 separate settings,
  filtering it directly is simpler and automatically flows through to
  everywhere that reads it (the `<link>` tag, the CSS var output) with no
  separate preview-only CSS needed.

## Adding a new font pairing

1. Pick two Google Fonts (or one, for a single-family pairing like Modern
   Sans) and add an entry to `ajnanda_get_font_pairings()` in
   `inc/font-pairings.php` — `heading_stack`/`body_stack` (CSS values),
   `google_families` (the `family=...` query segment for the CSS2 API).
2. Register the underlying font family names in `theme.json`
   (`settings.typography.fontFamilies`) if they're not already there.
3. Verify: the new pairing shows up in Appearance → Customize →
   Typography and in AJNanda → Site Kits' font table, and its Preview link
   renders the fonts correctly.

## Pairing a Starter Site with a Site Kit

A starter site manifest (`inc/starter-sites/manifests/*.php`) can set an
optional `'site_kit'` key naming a slug from `ajnanda_get_site_kits()`.
When set:

- **Preview defaults to it.** `ajnanda_get_starter_preview_url()` and
  every "Preview" link/thumbnail on the Starter Sites admin screen use
  that kit's color scheme + font pairing automatically, instead of the
  site's real saved colors (which is usually just the default blue until
  someone applies a kit) — otherwise every starter previews identically
  regardless of what look it was actually designed around.
- **Import stays opt-in.** The Import form shows an unchecked "Also apply
  the '{Kit}' Site Kit site-wide" checkbox — checking it calls
  `ajnanda_apply_site_kit()` as part of the same import
  (`AJNanda_Starter_Importer::import()`'s `apply_kit` arg). This is the
  one thing on this screen that changes site-wide settings rather than
  just the starter's own pages, so unlike everything else here it is
  never on by default. No equivalent CLI flag exists yet — run `wp
  ajnanda site-kit set <slug>` as a separate command instead.

`ajnanda_apply_site_kit( $kit_slug )` (`inc/site-kits.php`) is the single
implementation that actually writes the 4 color theme_mods + the font
pairing theme_mod — used by `wp ajnanda site-kit set`, the Starter Sites
import checkbox, and nowhere else. The "Quick Kits" Customizer control
deliberately does not use it — that one only fills in the Customizer's
own controls client-side, still requiring Publish, like every other
quick-preset in this theme.

## Adding a new site kit

1. Pick an existing color scheme slug (`inc/color-schemes.php`) and font
   pairing slug (`inc/font-pairings.php`) — add new ones first if neither
   fits.
2. Add an entry to `ajnanda_get_site_kits()` in `inc/site-kits.php`.
3. Verify: the kit appears in AJNanda → Site Kits and in the Customizer's
   "Quick Kits" control, and its Preview link shows both overrides
   applied.

## WP-CLI

```
wp ajnanda font-pairing list [--format=table|json]
wp ajnanda font-pairing set <slug>
wp ajnanda site-kit list [--format=table|json]
wp ajnanda site-kit set <slug>
```

`site-kit set` does the same thing the "Quick Kits" Customizer control
does — sets all 5 underlying values (4 colors + font pairing) in one call
— which also makes it the fastest way to apply a kit to a starter site
right before importing it (`wp ajnanda site-kit set little-one && wp
ajnanda starter import baby-announcement --status=publish`).
