# AJNanda Site Builder — Developer Guide

This covers the pattern/page-design/starter-site system added in 1.3.0
("the site builder"). For everything else about the theme (Customizer,
header/footer builder, custom blocks, SEO, theme updater, AJCore
integration, legacy/compatibility notes), see `docs/AJNANDA-CAPABILITIES.md`
— the root `README.md`, `SETUP-GUIDE.md`, and `INSTALLATION.md` predate
that document and are not kept in sync with the current theme.

## Architecture

```
AJNanda Section Patterns   /patterns/*.php (auto-registered by WP core)
        │  referenced by slug
        ▼
AJNanda Page Designs       /patterns/page-*.php (also auto-registered;
        │  referenced by slug            Block Types: core/post-content)
        ▼
AJNanda Starter Sites      inc/starter-sites/manifests/*.php
        │  imported by AJNanda_Starter_Importer
        ▼
Real WP_Post pages + nav menu + homepage settings
```

Each layer only knows about slugs in the layer below it — nothing
duplicates another layer's markup. See `docs/patterns.md`,
`docs/page-designs.md`, and `docs/starter-sites.md` for each layer in
detail.

## File map

| Path | Purpose |
|---|---|
| `patterns/*.php` | Section patterns and page designs (one file each) |
| `inc/patterns.php` | Pattern category registration |
| `inc/page-designs.php` | `ajnanda_compose_page_content()`, `ajnanda_get_page_designs()`, `ajnanda_insert_page_design()` |
| `inc/starter-sites/class-ajnanda-starter-sites.php` | Manifest registry |
| `inc/starter-sites/class-ajnanda-starter-importer.php` | Import engine (preview/import) |
| `inc/starter-sites/manifests/*.php` | One starter site per file |
| `inc/admin/class-ajnanda-admin.php` | Top-level "AJNanda" admin menu + form handlers |
| `inc/admin/views/*.php` | Admin screen templates |
| `inc/color-schemes.php` | Preset registry, one-click preset swatches for the native Colors panel, editor/iframe CSS injection (closes the gap in the theme's existing `wp_head`-only color output), per-page wrap helper |
| `inc/preview.php` | Non-destructive live preview (any pattern, optionally any color scheme) — see "Preview" below |
| `inc/cli/class-ajnanda-cli.php` | WP-CLI commands (loaded only when `WP_CLI` is defined) |
| `inc/site-builder.php` | Loader that wires the above together, required once from `functions.php` |

## Admin UI

**AJNanda** top-level admin menu (`dashicons-layout`), alongside Posts,
Pages, Appearance:

- **Overview** — counts and quick links.
- **Starter Sites** — preview/import UI described in `docs/starter-sites.md`;
  each page also links to a full visual preview (see "Preview" below).
- **Page Library** — browse Page Designs, "Add as New Page", and preview
  any design in any color scheme before inserting it.
- **Patterns** — read-only reference of AJNanda's own section patterns
  (core-bundled patterns are filtered out).
- **Color Schemes** — visual reference of the 20 presets in
  `ajnanda_get_color_schemes()`, each with a live preview link. Read-only —
  to actually apply a scheme, use the Customizer (site-wide) or the Page
  Library picker (single page).
- **Theme Settings** — links to the existing Customizer and the existing
  Appearance → Update AJNanda screen (`inc/github-theme-updater.php`),
  which are intentionally **not** re-implemented or re-parented here so
  their existing behavior (including the Customizer's header/footer
  builder and the updater's "direct update" sidebar link) is left
  untouched.

Admin CSS/JS (`inc/admin/assets/admin.css`) is only enqueued on AJNanda's
own screens (checked via `$_GET['page']` starting with `ajnanda`) — nothing
loads on other wp-admin screens or the front end.

## Preview

`inc/preview.php` renders any registered pattern — a Section Pattern, a
Page Design, or (since a Starter Site page is just a page_design
reference) any Starter Site page — as a real front-end page, optionally
in any Color Scheme, without creating anything in the database.

`ajnanda_get_preview_url( $pattern_slug, $color_scheme = '' )` builds a
nonce-protected link (`admin-post.php?action=ajnanda_preview&slug=...`,
capability `edit_theme_options`). The handler builds an in-memory
`WP_Post` (ID `0`, never `wp_insert_post()`-ed), points `$wp_query` /
`$wp_the_query` at it, and includes the theme's own `page.php` — the same
builder-canvas detection and template a real page gets — rather than
reimplementing a simplified renderer. A color scheme override, if any, is
applied as an `ajnanda-scheme-{slug}` body class, reusing the exact CSS
`style.css` already defines for the Page Library's per-page override — no
new styling is generated for preview. A sticky banner (injected via the
`wp_body_open` hook) makes clear nothing is saved.

Two WordPress internals needed explicit handling to get a clean preview
under `wp-admin/admin-post.php` (which never calls `set_current_screen()`
or runs the normal main-query bootstrap the way a real page load does):
the admin toolbar's edit-link builder assumes `get_current_screen()` is
non-null, and `WP_Query::the_post()` reads `query_vars['update_post_term_cache']`/
`['update_post_meta_cache']`, which are normally defaulted inside
`WP_Query::get_posts()` — never called here since the "query" is a single
hand-built post, not a real query. Both are set explicitly in the handler.

## WP-CLI

```
wp ajnanda pattern list [--category=<slug>] [--format=table|json]
wp ajnanda page-design list [--format=table|json]
wp ajnanda page-design insert <slug> --title=<title> [--status=draft|publish]
wp ajnanda starter list [--format=table|json]
wp ajnanda starter preview <slug>
wp ajnanda starter import <slug> [--pages=<comma-list>|all] [--status=draft|publish] [--set-homepage] [--overwrite-menu] [--no-menu]
wp ajnanda color-scheme list [--format=table|json]
wp ajnanda color-scheme set <slug>
```

Every command calls the same registry/importer functions/classes the admin
UI uses — there is one implementation per behavior.

## Future automation

This system is designed so an external tool (a script, or an AI coding
agent) can drive it without reading theme source first:

- Starter site slugs, page design slugs, and section pattern slugs are all
  treated as **stable public identifiers** — once shipped, a slug is never
  renamed, only added to. Automation built against a slug today keeps
  working after theme updates.
- Every mutating operation (`AJNanda_Starter_Importer::import()`,
  `ajnanda_insert_page_design()`) is reachable both as PHP and as a CLI
  command, so it's scriptable without needing to be logged into wp-admin.
- Example of the intended end-to-end workflow:
  `wp ajnanda starter list` → `wp ajnanda starter preview technology` →
  `wp ajnanda starter import technology --status=publish --set-homepage`.

## Adding things — quick index

- **A pattern** → `docs/patterns.md`
- **A page design** → `docs/page-designs.md`
- **A starter site** → `docs/starter-sites.md`

In all three cases: add one new file, reuse existing categories/classes/
slugs where they fit, and nothing else needs to change — there's no
central registration list to update by hand.

### A `WP_Block_Patterns_Registry` gotcha to know before touching any of this

`WP_Block_Patterns_Registry::get_all_registered()` returns its result
through `array_values()` — **the array key is never the slug**, even
though iterating `as $slug => $pattern` looks correct and won't error.
The real slug is `$pattern['name']`. This bit every place in the codebase
that listed patterns (`ajnanda_get_page_designs()`, the Patterns admin
screen, `wp ajnanda pattern list`) until it was caught by actually
clicking through the admin UI, not just reading the code — CLI/table
output showed plausible-looking sequential numbers instead of slugs.
Single-slug lookups (`is_registered( $slug )`, `get_registered( $slug )`)
are unaffected; only code that iterates *all* registered patterns needs
this in mind.

## Testing checklist

Before shipping a change to this system:

1. `php -l` every changed/added PHP file.
2. Load `wp-admin` with `WP_DEBUG`/`WP_DEBUG_LOG` on; confirm `debug.log`
   stays clean through: theme activation, opening each AJNanda admin
   screen, previewing and importing a starter site, inserting a page
   design, re-running an import (idempotency check).
3. Open an imported page in the block editor and confirm there are no
   "invalid content" / block-validation warnings.
4. Check `wp option get show_on_front` / `page_on_front` / `page_for_posts`
   and the primary menu only changed when explicitly requested.
5. Spot-check a "Super Bold" hero/CTA and a card grid/columns pattern at
   desktop, tablet, and mobile widths.
6. Deactivate and reactivate the theme once, with AJCore both active and
   inactive, and confirm no fatals either way.
