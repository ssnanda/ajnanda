# AJNanda Capabilities

A high-level architectural map of what AJNanda (the theme) currently does,
where each system lives, and which detailed doc to read before changing
it. This is not a reference manual — implementation detail here is
proportional to importance. Read this first, then the subsystem doc named
in each section.

AJNanda is a **classic WordPress theme** (`header.php`/`footer.php`/PHP
template files — no `templates/` or `parts/` directory, not a block/FSE
theme) that layers a Gutenberg-first, block-pattern-driven site-building
system on top, plus an extensive Customizer-based visual "builder" for
header/footer/colors that predates that system.

## High-level architecture

```
                        AJNanda Design System
              (theme.json presets + style.css tokens/classes)
                                  │
                 ┌────────────────┼────────────────┐
                 │                │                 │
        AJNanda Blocks    Section Patterns   Core block
        (blocks/ajnanda-  (patterns/*.php)   enhancements
         blocks/)                │           (js/editor-controls.js)
                                  ▼
                          AJNanda Page Designs
                          (patterns/page-*.php)
                                  │
                                  ▼
                          AJNanda Starter Sites
                       (inc/starter-sites/manifests/)
                                  │
                                  ▼
              Real WP_Post pages + nav menu + homepage config

   ── separately, alongside all of the above ──

  Header / Footer Builder  ◄──┐
  (Customizer-driven,          │
   functions.php)              ├── Customizer (functions.php,
  Colors (native pickers +     │   inc/color-schemes.php, inc/seo.php)
   AJNanda presets)         ◄──┘
  SEO (inc/seo.php)
  Theme updater (inc/github-theme-updater.php)
  AJNanda admin menu (inc/admin/) ── surfaces the site-builder system
  WP-CLI (inc/cli/) ── scripts the site-builder system

  AJCore (separate plugin) ── provides forms; a few patterns embed its
                               [ajforms] shortcode. No other coupling.
```

The site-builder pipeline (Patterns → Page Designs → Starter Sites → real
WP content) is the part of AJNanda built specifically to let future sites
be assembled through wp-admin/Gutenberg without custom code. Everything
else in the diagram (header/footer builder, Colors, SEO, blocks, core
block enhancements) is the pre-existing design/presentation system that
the site-builder patterns are built *from* — the site-builder doesn't
replace any of it.

---

## Patterns, Page Designs, and Starter Sites

These three are the site-building system and are intentionally kept
separate. Full detail lives in their own docs — this is the short version.

### Patterns

Reusable page **sections** (Hero, Services, CTA, FAQ, Testimonials, etc.) —
one WordPress block pattern per file, auto-registered by WordPress core
from `/patterns/*.php`. Inserted via the normal block inserter's Patterns
tab. Built entirely from core Gutenberg blocks plus AJNanda's existing
design system (theme.json presets, `builder-*` classes, the
`is-style-ajnanda-*` block styles) — no custom blocks, no JS, inside
pattern content.

- **Main implementation**: `patterns/*.php` (excluding `page-*.php`),
  `inc/patterns.php` (category registration only — pattern *content* needs
  no registration code, WordPress auto-discovers the files)
- **Detailed docs**: `docs/patterns.md`

### Page Designs

Complete, ready-to-use **pages** (Home, About, Services, Contact, etc.),
each composed by listing which Section Patterns to stack — not by
duplicating their markup. Registered with `Block Types: core/post-content`
so they appear automatically in the native "Choose a pattern" modal on
Pages → Add New.

- **Main implementation**: `patterns/page-*.php`, `inc/page-designs.php`
  (`ajnanda_compose_page_content()`, `ajnanda_get_page_designs()`,
  `ajnanda_insert_page_design()`)
- **Related systems**: composes Section Patterns by slug; used by Starter
  Sites and by the Page Library admin screen
- **Detailed docs**: `docs/page-designs.md`

### Starter Sites

Imports a coordinated set of Page Designs plus a primary nav menu in one
action — the "activate theme → pick a starter → launch" path. A safe,
idempotent import engine: re-running never duplicates pages, never
overwrites content it doesn't own, and only touches the homepage/menu
when explicitly asked.

- **Main implementation**:
  `inc/starter-sites/class-ajnanda-starter-sites.php` (manifest registry),
  `inc/starter-sites/class-ajnanda-starter-importer.php` (import engine),
  `inc/starter-sites/manifests/*.php` (12 starter sites today: corporate,
  technology, professional-services, product-reseller,
  property-management, insurance-financial, minimal-business,
  music-artist, personal-creative, baby-announcement, family-blog,
  developer-portfolio)
- **Related systems**: each manifest page references a Page Design slug;
  the importer calls the same `ajnanda_get_pattern_content()` /
  `wp_insert_post()` path as manual insertion
- **Current limitations**: a starter site only creates pages + a primary
  menu + optionally sets the homepage/posts page — it does not itself
  apply a Color Scheme/Font Pairing/Site Kit or any other Customizer
  setting, even the 4 starters (see `docs/starter-sites.md`) written to
  pair with a specific Site Kit; applying that kit remains a separate,
  manual step (Customizer or `wp ajnanda site-kit set`) before or after
  import.
- **Detailed docs**: `docs/starter-sites.md`

### How they're surfaced

- **AJNanda** admin menu (top-level, `inc/admin/class-ajnanda-admin.php`):
  Overview (library counts plus a "Your site" status block — current color
  scheme, starter site imported, pages built, primary menu set up),
  Starter Sites (preview/import UI, with inline per-page "already
  imported" badges shown by default), Page Library (browse with a live
  text filter + "Add as New Page"), Patterns (read-only reference with the
  same live filter), Color Schemes (visual reference), Site Kits (visual
  reference of color+font bundles, plus font pairings alone), Theme
  Settings (links out to the Customizer — including a direct Colors
  card — and the existing theme updater screen).
- **Native "Choose a pattern" modal** (Pages → Add New): shows Page
  Designs automatically — no custom UI, this is core WordPress behavior
  triggered by the `Block Types: core/post-content` pattern header.
- **WP-CLI** (`inc/cli/class-ajnanda-cli.php`): `wp ajnanda pattern|page-design|starter|color-scheme|font-pairing|site-kit ...` — thin wrappers around the same PHP functions/classes the admin screens use.

### Non-destructive preview

`inc/preview.php` renders any registered pattern (a Section Pattern, a
Page Design, or a Starter Site page — the latter two are just pattern
slugs) as a real front-end page — real header/footer, real CSS — without
writing anything to the database. Optionally previews with any Color
Scheme and/or Font Pairing applied (i.e. any Site Kit). Reached via
"Preview" links on the Patterns, Starter Sites, Page Library, Color
Schemes, and Site Kits admin screens, all of which open in an in-page
modal by default (`inc/admin/assets/admin.js`, with an "Open in new tab"
fallback) rather than navigating away; built from an in-memory `WP_Post`
that's never `wp_insert_post()`-ed. Detail in `docs/development.md`
("Preview") and `docs/site-kits.md`.

---

## AJNanda Custom Blocks

**61 distinct blocks**, all under one editor category ("AJNanda Blocks",
registered via `block_categories_all` in `loader.php`). Two registration
paths that cooperate rather than conflict:

- **`blocks/ajnanda-blocks/loader.php`** — PHP, `register_block_type()`
  with a `render_callback`, for the 12 blocks that are genuinely
  server-rendered ("Dynamic" below).
- **`blocks/ajnanda-blocks/index.js`** — client-side `registerBlockType()`
  for all 61 (the 12 dynamic ones register a matching editor-side
  definition using `ServerSideRender` for preview and a `save()` that
  returns `null`; the other 49 are purely static blocks whose `save()`
  bakes HTML straight into `post_content`, no PHP involved). Several
  blocks share a common JS factory function (e.g. `registerContainerBlock`,
  `simpleCardBlock`) rather than one bespoke call each — that's why the
  registration code doesn't read as one block per call.

No `block.json` files exist for any of these — attributes/behavior are
defined inline in the two files above.

| Slug | Title | Type | Purpose |
|---|---|---|---|
| `ajnanda/svg` | AJ SVG | Dynamic | Sanitized inline SVG (server-filtered via `wp_kses`) |
| `ajnanda/posts` | AJ Posts | Dynamic | List of recent posts (count/order/columns/excerpt/image) |
| `ajnanda/post-grid` | AJ Post Grid | Dynamic | Grid layout of the same post query |
| `ajnanda/post-carousel` | AJ Post Carousel Placeholder | Dynamic | Post carousel — title says "Placeholder"; no carousel JS wires up to it |
| `ajnanda/post-timeline` | AJ Post Timeline | Dynamic | Chronological post list |
| `ajnanda/search` | AJ Search | Dynamic | Search form (inline/stacked) |
| `ajnanda/nav-menu` | AJ Menu | Dynamic | Renders `wp_nav_menu()` for a chosen location — not used by header/footer chrome (those call `wp_nav_menu()`/the builder directly); available for use inside page content |
| `ajnanda/table-of-contents` | AJ Table of Contents | Dynamic | TOC built from headings; pairs with a filter that injects heading anchors. `collapsible` attribute has no JS behind it |
| `ajnanda/taxonomy-list` | AJ Taxonomy List | Dynamic | Term list/pills for a taxonomy |
| `ajnanda/login-placeholder` | AJ Login Placeholder | Dynamic | Login/logout link reflecting current user |
| `ajnanda/slide` | AJ Slide | Dynamic (hybrid) | One slide; must be a child of `ajnanda/slider` |
| `ajnanda/slider` | AJ Slider | Dynamic (hybrid) | Swiper.js carousel — genuinely wired up (`frontend.js` `initSliders()`, Swiper assets conditionally enqueued via `has_block()`) |
| `ajnanda/heading` | AJ Heading | Static | Styleable heading |
| `ajnanda/text-editor` | AJ Paragraph/Text Editor | Static | Styleable rich-text paragraph |
| `ajnanda/image` | AJ Image | Static | Styleable image + media picker |
| `ajnanda/button` | AJ Button | Static | Single styleable link/button |
| `ajnanda/divider` | AJ Divider | Static | Horizontal rule, optional label |
| `ajnanda/spacer` | AJ Spacer | Static | Adjustable empty vertical space |
| `ajnanda/icon` | AJ Icon | Static | Single text/character icon + label |
| `ajnanda/youtube` | AJ YouTube | Static | Iframe embed for a YouTube URL |
| `ajnanda/video` | AJ Video | Static | Generic video iframe embed |
| `ajnanda/google-maps` | AJ Google Maps Embed | Static | Iframe embed for a Maps URL |
| `ajnanda/div-block` | AJ Div Block | Static | Plain InnerBlocks wrapper |
| `ajnanda/flexbox` | AJ Flexbox | Static | Flex row/column container |
| `ajnanda/container` | AJ Container | Static | The most elaborate layout block — width/layout-mode/columns/gap plus a first-use layout chooser |
| `ajnanda/grid` | AJ Grid | Static | CSS grid container |
| `ajnanda/form` | AJ Form | Static | Static form mockup — its own description says "Static form layout"; no submission handling exists anywhere |
| `ajnanda/tabs` | AJ Tabs | Static | "Tabbed content placeholder" — no tab-switching CSS/JS exists |
| `ajnanda/accordion` | AJ Accordion | Static | `core/details`-based expand/collapse container (works, native HTML). Its own `collapseOtherItems`/`expandFirstItem` attributes aren't wired to any runtime code |
| `ajnanda/image-box` | AJ Image Box | Static | Image + heading + text card |
| `ajnanda/icon-box` | AJ Icon Box | Static | Icon + heading + text card |
| `ajnanda/basic-gallery` | AJ Basic Gallery | Static | Gallery wrapper — **functionally identical** to `ajnanda/image-gallery` below (same factory, same description) |
| `ajnanda/image-gallery` | AJ Image Gallery | Static | Same implementation as `ajnanda/basic-gallery` |
| `ajnanda/info-box` | AJ Info Box | Static | Icon/heading/text card |
| `ajnanda/call-to-action` | AJ Call To Action | Static | Heading + text + button CTA card |
| `ajnanda/marketing-button` | AJ Marketing Button | Static | Styled buttons wrapper with icon options |
| `ajnanda/content-timeline` | AJ Content Timeline | Static | Timeline item card |
| `ajnanda/how-to` | AJ How To | Static | Numbered "How To" card; its `showSchema` toggle produces no schema.org output |
| `ajnanda/modal` | AJ Modal Placeholder | Static | No modal open/close JS exists — static box only |
| `ajnanda/lottie-animation` | AJ Lottie Animation Placeholder | Static | No Lottie player is enqueued anywhere — attributes captured, nothing renders |
| `ajnanda/team` | AJ Team | Static | Team-member card (photo/name/bio) |
| `ajnanda/testimonials` | AJ Testimonials | Static | Quote-based testimonial card; its "carousel" layout option is explicitly labeled "Carousel placeholder" |
| `ajnanda/review` | AJ Review | Static | Star-rating + quote card; `enableSchema` toggle produces no schema.org output |
| `ajnanda/price-list` | AJ Price List | Static | Simple price list card |
| `ajnanda/social-share` | AJ Social Share | Static | Share-button row; its free-text `networks` field doesn't appear to drive the actual static buttons |
| `ajnanda/separator` | AJ Separator | Static | Horizontal rule with thickness/width controls |
| `ajnanda/blockquote` | AJ Blockquote | Static | Editable rich-text blockquote |
| `ajnanda/inline-notice` | AJ Inline Notice | Static | Editable rich-text notice box |
| `ajnanda/buttons` | AJ Buttons | Static, **hidden from inserter** | Legacy multi-button wrapper — description says "use the native AJ Buttons variation instead" (see Core Block Enhancements); kept only for old content |
| `ajnanda/faq` | AJ FAQ | Static | `core/details`-based FAQ accordion — genuinely functional (`frontend.js` `initFaq()` wires collapse-others/expand-first/disable-toggle). Its `enableSchema` toggle, despite the block description promising "FAQ schema," produces no JSON-LD |
| `ajnanda/input`, `ajnanda/label`, `ajnanda/text-area`, `ajnanda/checkbox`, `ajnanda/submit-button` | AJ Input/Label/Text Area/Checkbox/Submit Button | Static | Static form-field mockups used inside `ajnanda/form` — no real submission handling |
| `ajnanda/icon-list` | AJ Icon List | Static | Icon-marked list wrapper; icon inheritance to children implemented in `frontend.js` |
| `ajnanda/icon-list-item` | AJ List Item | Static | One list item, restricted to `ajnanda/icon-list` parent |
| `ajnanda/counter` | AJ Counter | Static | Renders a static number — no count-up animation despite the name |
| `ajnanda/progress-bar` | AJ Progress Bar | Static | Static progress bar at its final width — no scroll animation |
| `ajnanda/countdown` | AJ Countdown | Static | Displays a raw target-date string — **no countdown-ticking JS exists** |
| `ajnanda/star-ratings` | AJ Star Ratings | Static | Static 1–5 unicode-star rating + label |

**Relationship to Section Patterns**: Section Patterns deliberately use
only core WordPress blocks (group, columns, heading, paragraph, buttons,
list, quote, details, query) — none of these `ajnanda/*` custom blocks —
keeping pattern content portable and independent of the custom block
library. The custom blocks are available for hand-building pages outside
the pattern system.

**Known gaps** (worth knowing before touching this block library):
`ajnanda/basic-gallery` and `ajnanda/image-gallery` are duplicates of each
other; several "Enable schema" toggles (`ajnanda/faq`, `ajnanda/how-to`,
`ajnanda/review`) don't actually emit any schema.org markup; several
blocks are explicitly placeholders with no runtime behavior
(`ajnanda/modal`, `ajnanda/lottie-animation`, `ajnanda/post-carousel`,
the "carousel" option on `ajnanda/testimonials`, `ajnanda/tabs`,
`ajnanda/counter`, `ajnanda/countdown`); `ajnanda/buttons` is
deprecated/hidden in favor of the `core/buttons` "AJ Buttons" variation
(below). None of this blocks normal use of the working majority of the
library — just don't assume a control does something because it exists
in the UI.

## Core Block Enhancements

Registered client-side in `js/editor-controls.js`, loaded via
`enqueue_block_editor_assets` (`ajnanda_block_editor_assets()` in
`functions.php`):

- **`core/buttons` / `core/button`** — extended with custom attributes
  (`ajnBtnScheme`, per-button colors, responsive desktop/tablet/mobile
  layout and width) added client-side and rendered server-side by a
  `render_block` filter in `blocks/ajnanda-blocks/loader.php`
  (`ajnanda_render_core_buttons_block()`), which injects CSS custom
  properties/classes at render time. Exposed in the inserter as the "AJ
  Buttons" block variation — the modern replacement for the legacy,
  now-hidden `ajnanda/buttons` custom block above.
- **`core/group`** — an "AJNanda Hero" block variation (pre-filled
  `builder-hero-section` group) and an "AJNanda Hero (Image Background)"
  variation on **`core/cover`**.
- **Block styles** (`registerBlockStyle`) — the `is-style-ajnanda-*`
  family used throughout Section Patterns: card variants (`ajnanda-card`,
  `-elevated`, `-bordered`, `-soft`, `-linked`) on group/column, icon/eyebrow
  styles on paragraph/heading, checklist styles on list, equal-height on
  columns. Registered here client-side, not duplicated server-side —
  matching CSS lives in `style.css`.

**Main implementation**: `js/editor-controls.js`,
`blocks/ajnanda-blocks/loader.php` (the buttons render filter).

---

## Design System

Design tokens come from **two places**, not one — important when adding
new patterns or CSS:

1. **`theme.json`** (editor-facing presets/settings only — this is a
   classic theme, so `theme.json` does not drive global styles the way it
   would in a block theme): 7-color palette, 6 font-size presets
   (`small`…`huge`, fluid), 6 spacing-size presets (`xs`…`2-xl`), 12
   gradient presets (see Colors below), layout content/wide widths
   (950px/1280px).
2. **`style.css`** `:root` custom properties — the actual visual tokens
   most components style from: `--primary`, `--primary-dark`,
   `--secondary`, `--accent`, `--gray-50`…`--gray-900`, `--white`, plus
   header/footer-specific `--ajn-header-*`/`--ajn-footer-*` variables and
   legacy Astra-compatibility variables (`--ast-global-color-*`).

Structural/component classes also live in `style.css`: `builder-section` /
`builder-section-soft`, `builder-hero-section` (+ `hero-width-*` /
`hero-height-*` / `hero-text-*` / `hero-tone-dark` modifiers),
`builder-split`, `builder-cta-panel`, `section-tone-dark`, and the
`is-style-ajnanda-*` block styles (registered in `js/editor-controls.js`,
styled in `style.css`).

### Colors

- **4 real brand-color Customizer controls** — Primary, Primary Hover,
  Secondary, Accent (`theme_primary_color` / `theme_primary_dark_color` /
  `theme_secondary_color` / `theme_accent_color`, native
  `WP_Customize_Color_Control`s under the built-in **Colors** panel,
  `functions.php`). Output as `:root` custom properties by
  `ajnanda_customizer_css()` on `wp_head`.
- **`inc/color-schemes.php`** adds three things on top of that existing
  system (does not duplicate it): (1) pushes the same saved colors into
  the block editor's iframe/pattern previews via
  `enqueue_block_editor_assets` + `block_editor_settings_all` — the
  original system only reached the frontend; (2) 24 one-click preset
  swatches in the Colors panel that fill in the 4 real settings via the
  Customizer JS API; (3) a per-page color-scheme override used by the Page
  Library screen (wraps a page's content in an `.ajnanda-scheme-{slug}`
  group block).
- **`inc/dark-surface-mode.php`** is a second, independent toggle in the
  same Colors panel — a boolean Customizer checkbox
  (`ajnanda_dark_surface_mode`) that redefines the *neutral* ramp
  (`--white`, `--gray-50`…`--gray-900`) to a mirrored-lightness dark ramp,
  leaving `--primary`/`--secondary`/`--accent` untouched. Where the color
  scheme system only ever changes buttons/hero gradients/links, this
  changes page backgrounds, card surfaces, and text — a genuinely dark UI
  that any Color Scheme can be combined with. Same `:root{...}`-on-`wp_head`
  mechanism and editor-iframe-gap pattern as the color scheme system;
  `docs/site-kits.md` has the mirrored-lightness rationale.
- **Gradients**: 12 named presets in `theme.json`
  (`settings.color.gradients`), built from `var(--primary)` etc. so they
  automatically follow whichever colors are active. Appear in the native
  Gradient Picker on any block with gradient support. Two Section Patterns
  (`ajnanda/section-hero-gradient`, `ajnanda/section-cta-gradient`) use one.

**Detailed docs**: the Colors/gradients system is documented in
`docs/patterns.md` (under "Color schemes") since it was built to serve the
pattern library; there's no separate colors-only doc.

### Typography

Site-wide heading/body fonts are driven by two CSS custom properties,
`--font-heading`/`--font-body` (`style.css`), which `header.php`'s Google
Fonts `<link>` and a `wp_head`-hooked `<style>` override both follow —
**not** hardcoded anymore. `inc/font-pairings.php` (mirrors
`inc/color-schemes.php`'s shape exactly) adds a real `theme_font_pairing`
Customizer setting under a new **Typography** section, 5 named presets
(Classic — the original Inter/Poppins default, Modern Sans, Elegant
Serif, Bold Display, Playful Rounded), and the same editor-iframe-gap
closing the color system already had. The 8 underlying font families are
also registered in `theme.json` (`settings.typography.fontFamilies`), so
each is independently selectable per-block in the native block editor
Typography panel. `inc/site-kits.php` bundles a color scheme + a font
pairing under one name (10 presets, e.g. "Neon Night," "Bubblegum Pop") as
a "Quick Kits" Customizer control. **Detailed docs**: `docs/site-kits.md`.

Font-size presets come from `theme.json`. Per-element typography
(header/footer nav font, post meta) is separately controlled via the
Customizer header/footer builder settings below, output as
`--ajn-header-font-*`/`--ajn-footer-font-*` CSS variables.

---

## Header and Footer

The **header** supports two rendering modes chosen by
`ajnanda_get_header_layout()` (`functions.php`), which returns `'builder'`
automatically if any header-builder theme_mod is set, otherwise a plain
static layout. The **footer** has only one mode — it is always
builder-driven, there is no static-footer branch.

- **`header.php`** — a thin dispatcher (not static markup): outputs
  `<head>` boilerplate + Google Fonts links, then branches. Builder mode
  wraps `ajnanda_render_builder_layout('header')`. Static mode renders
  `ajnanda_render_builder_site_identity()` for branding plus a direct
  `wp_nav_menu(['theme_location' => 'primary', ...])` call. Both modes
  render the same mobile-menu-toggle button; a Customizer live-preview
  helper (`ajnanda_render_header_builder_preview()`) runs after.
- **`footer.php`** — 3 lines: `ajnanda_render_site_footer()` (always
  builder-driven — `<footer class="site-footer footer-layout-builder">`
  wrapping `ajnanda_render_builder_layout('footer')`), a Customizer
  live-preview helper, then `wp_footer()`.
- **Shared builder engine** (`functions.php`), used by both header and
  footer: `ajnanda_render_builder_layout($builder, ...)` loops configured
  rows/columns and reads each cell's assigned element from a `get_theme_mod()`
  lookup; `ajnanda_render_builder_element($builder, $element)` is a
  `switch` over element types (site-logo, primary-menu, footer-menu,
  search, buttons, copyright, dividers, HTML blocks, social, WooCommerce
  cart, widget areas 1–4) that renders one cell. Row/column/width/element
  values are all `ajn_{builder}_builder_...`-prefixed theme_mods.
- **Footer widget areas**: `footer-builder-1`..`-4` sidebars
  (`register_sidebar()`, `functions.php`), rendered via the builder's
  `widget-N` case.
- **`ajnanda/nav-menu` custom block exists but isn't used here** — header
  and footer render menus via `wp_nav_menu()`/the builder's own menu case
  directly, not that block. The block is available for use inside page
  content instead.
- **Mobile menu**: `#mobile-menu-toggle` click handler in `js/main.js`
  (toggle `.active`/`aria-expanded`, body-scroll lock, submenu tap-expand,
  outside-click close). Style (slide/overlay) controlled by the
  `ajn_mobile_menu_style` theme_mod, applied as a `mobile-nav-style-overlay`
  class in `header.php`.
- **Optional floater-panel menus**: `ajnanda_get_menu_toggles()`
  (`functions.php`) conditionally registers extra nav menu locations
  (`office_shortcuts`, `store_shortcuts`) when enabled.
- **Customizer sections**: `ajnanda_header`, `ajnanda_hero_defaults`,
  `ajnanda_footer` — see the Colors/Customizer subsection above for the
  full section list and custom control classes.

**Relationship to the site-builder system**: `page.php` sniffs
`post_content` for known section marker classes — including
`builder-section`, the exact class every AJNanda Section Pattern uses —
to decide whether to render full-width "builder canvas" style or the
normal boxed `.container` layout. This is why pages built from AJNanda
patterns/page designs render edge-to-edge automatically: they trip this
existing detection logic, they don't need their own template.

No dedicated header/footer markdown doc exists — this section plus reading
`functions.php`'s builder-render functions and `header.php`/`footer.php`
directly is the current documentation.

### Full Customizer section/control inventory

| Section ID | Registered in | Controls |
|---|---|---|
| `colors` (core section, extended) | `functions.php` | WordPress's built-in Colors section, extended with AJNanda's 4 real brand-color pickers and the 20 preset swatches — see Design System → Colors above |
| `ajnanda_header` | `functions.php` | Header background/text/link/submenu colors, header font, header color-scheme picker, header builder (rows/cells/widths), nav CTA styling |
| `ajnanda_hero_defaults` | `functions.php` | Default hero colors and responsive min-height/padding for editable hero blocks |
| `ajnanda_footer` | `functions.php` | Footer background/text/link colors, footer font, footer color-scheme picker, footer builder button/HTML/social settings |
| `ajnanda_post_meta` | `functions.php` | Show/hide date, author, read-time on the blog listing |
| `ajnanda_seo` | `inc/seo.php` | Default meta description/social image, Twitter handle, business phone/address (LocalBusiness schema), schema/AI-crawler/`llms.txt` toggles, read-only sitemap-URL note |
| `ajnanda_seo_insights` | `inc/seo.php` | Read-only Google Site Kit suggestions panel (soft-dependent — only populates if Site Kit is active) |

Custom `WP_Customize_Control` subclasses (all still actively used, not
dead code — the `NCLLC_Pro_*` naming is legacy, see Legacy notes below):
`NCLLC_Pro_Header_Font_Control`, `NCLLC_Pro_Header_Responsive_Value_Control`,
`NCLLC_Pro_Header_Color_Schemes_Control`, `NCLLC_Pro_Footer_Font_Control`,
`NCLLC_Pro_Footer_Color_Schemes_Control` (all `functions.php`),
`AJNanda_Color_Preset_Control` (`inc/color-schemes.php`),
`AJNanda_SEO_Insights_Control` (`inc/seo.php`).

---

## Admin / Development Tools

| Tool | What it does | Where |
|---|---|---|
| **AJNanda admin menu** | Top-level wp-admin menu surfacing the site-builder system: Overview, Starter Sites, Page Library, Patterns, Color Schemes, Site Kits, Theme Settings | `inc/admin/class-ajnanda-admin.php`, `inc/admin/views/*.php` |
| **Non-destructive preview** | Renders any pattern/page design (optionally in any color scheme and/or font pairing) as a real page — no database writes | `inc/preview.php` |
| **GitHub theme updater** | Self-hosted update mechanism — polls the `ssnanda/ajnanda` GitHub repo's latest Release, compares versions, hooks WordPress's native theme-update machinery plus a direct admin-post "update now" action. Screen lives at **Appearance → Update AJNanda** (a submenu under `themes.php`, not the new top-level AJNanda menu), and its sidebar link is rewritten to perform the update directly | `inc/github-theme-updater.php`, `inc/theme-details-updater-button.php` (adds a matching button to the native theme-details modal) |
| **Duplicate Post/Page** | "Duplicate" row action on Posts and Pages (only those two post types). Copies content/title(+" Copy")/excerpt/taxonomies/meta (except `_wp_old_slug`) into a new `draft`, then redirects to edit it | `inc/duplicate-content.php` |
| **WP-CLI** | `wp ajnanda pattern/page-design/starter/color-scheme/font-pairing/site-kit ...` — scriptable access to the site-builder system, same code paths as the admin UI | `inc/cli/class-ajnanda-cli.php` (only loaded when `WP_CLI` is defined) |
| **Search & AI** | Nine-tab theme-native discovery workflow: canonical Site Profile, central Content Access Policy, SEO/plugin capability ownership, schema, crawler-category robots policy, curated `/llms.txt`, WordPress sitemap filtering, objective readiness checks, cached Site Kit insights, and a bounded WordPress-local Crawler Log with reported-vs-verified identity, privacy modes, retention, filters, pagination, and future external-source support | `inc/search-ai/`, `inc/seo.php`, `docs/search-ai.md` |

The AJNanda admin menu's **Theme Settings** submenu links to the
Customizer and to the GitHub updater's existing screen rather than
re-implementing either — that updater screen (with its "direct update"
sidebar behavior) is left completely untouched.

---

## AJCore Integration

AJCore is a separate plugin (forms, client portal, billing, reservations,
etc.) — AJNanda does not implement any of that. Confirmed by an exhaustive
grep for `ajforms`/`AJForms`/`ajcore`/`AJCore` across every `.php`/`.js`/
`.css`/`.json` file in the theme — the **only** points of integration:

- **Two Section Patterns embed AJCore's `[ajforms id="..."]` shortcode**
  via the core Shortcode block, with a placeholder form ID and an
  instruction to swap in a real one from AJ Core → Forms once created:
  `patterns/contact-form-information.php`, `patterns/newsletter-cta.php`.
  AJNanda has no form system of its own by design — this is the
  intentional boundary, not a gap. See `docs/patterns.md` ("AJCore
  forms").
- **`style.css`** has a small block of defensive CSS
  (`.ajforms-frontend-form`, `.ajforms-conversation-form`,
  `form[class*="ajforms-"]`) styling AJForms' own rendered output when it
  appears as the first element inside `.page-content-panel`, so an
  embedded form sits flush against a leading hero rather than with extra
  top spacing.

There is no PHP-level dependency check (no `function_exists('ajcore')` or
similar) anywhere — the integration is purely at the content/CSS level.
If you find any other AJCore-specific reference while working in this
codebase, treat it as worth double-checking against this list.

---

## Legacy / Compatibility Notes

- **NCLLC naming**: the theme was renamed from an earlier "NCLLC
  Pro"/"NCLLC" project. `@package NCLLC_Pro` doc-comment headers remain in
  most root PHP files and several `inc/` files (cosmetic only — text
  domain is `'ajnanda'` throughout, not `'ncllc'`). `js/main.js` still has
  the same header comment. Five Customizer control classes are still
  named `NCLLC_Pro_*` and are genuinely active (see the Customizer
  inventory above) — not dead code, just old naming. Two legacy PHP
  constants, `NCLLC_GOOGLE_PLACES_API_KEY` / `NCLLC_GOOGLE_PLACE_ID`
  (`functions.php`), are still the documented way a site owner enables
  Google Places data. `README.md` at the repo root is also still written
  for "NCLLC Professional Theme" / `ncllc-pro` and describes older
  marketing copy, not the current site-builder capabilities — treat it as
  stale, not authoritative.
- **`js/main.js` dead code removed (2026-08-16)**: a leftover
  `$('#contact-form').on('submit', ...)` handler from the original
  NCLLC/"University Place Office Suites" demo template — inert on every
  real site (no current template produces `#contact-form`), but it also
  called an undefined `animateOnScroll()` function from inside the
  page-wide debounced scroll handler below it, throwing a real
  `ReferenceError` on **every scroll, on every AJNanda site**, found while
  testing a client site's forms with a real browser. Both the dead handler
  and the undefined-function call are removed; the debounced scroll
  handler now only calls `revealSections()` (still separately bound
  directly too, so scroll-reveal behavior is unchanged) — see `docs/patterns.md`
  for the scroll-reveal animation classes this supports.
- **Multi-generation option/theme_mod migrations** (`functions.php`,
  `after_setup_theme`): `ncllc_left_panel_enabled` etc. → `ajnanda_left_panel_enabled`
  etc.; `upos_office_shortcuts`/`upos_store_shortcuts` nav-menu-location
  keys → `office_shortcuts`/`store_shortcuts`; and a three-generation
  option fallback chain `upos_menu_toggles` → `ncllc_menu_toggles` →
  `ajnanda_menu_toggles` inside `ajnanda_get_menu_toggles()`. All still
  present and active — evidence of at least two prior renames (UPOS →
  NCLLC → AJNanda).
- **Spectra/Ultimate Addons for Gutenberg migration**:
  `ajnanda_convert_spectra_markup_to_core()` (`functions.php`, hooked on
  `the_content` at priority 8) rewrites old Spectra-plugin block markup
  (`wp-block-uagb-*`) into core-block equivalents via `DOMDocument`, with
  a non-DOM fallback. Bails immediately if content has no Spectra markup
  or if Spectra/Ultimate Addons for Gutenberg is currently active. Still
  hooked and active; only matters for old content that hasn't been
  re-saved.
- **Legacy page templates**: `page-services-simple.php` ("Services -
  Simple") and `template-services.php` ("Services Page") are near-duplicate
  hard-coded HTML pages (identical hero/features/process markup, inline
  `style="..."` attributes, hardcoded copy for a North Carolina
  registered-agent business) — not editor-driven, inconsistent with the
  block-first approach everywhere else. Neither is registered via the
  `theme_page_templates` filter (only `page-builder.php`/"Builder Canvas"
  is); they still work as ordinary `Template Name:` templates but are not
  part of the current pattern/page-design system — don't extend them, use
  a Page Design instead.
- **Legacy Section Patterns**: the 14 patterns that predate the site-builder
  work (added starting at version 1.2.7; `patterns/legacy-*.php`,
  `ajnanda-pro/*` slugs) were
  migrated as-is into the new `/patterns` auto-registration mechanism with
  unchanged slugs, for backward compatibility — see `docs/patterns.md`.
- **Legacy/hidden custom block**: `ajnanda/buttons` (`blocks/ajnanda-blocks/index.js`)
  is hidden from the block inserter (`inserter: false`) and its own
  description says to use the "AJ Buttons" `core/buttons` variation
  instead — see Core Block Enhancements above.
- **Stray files**: `ajnanda-functions-hero-customizer-restored.zip` (repo
  root) and `inc/ajnanda-github-theme-updater.zip` are leftover backup
  artifacts not referenced by any `require`/`include` in the codebase.
  The `css/` directory at the repo root is empty/unused — all theme CSS
  lives in `style.css`.

---

## File / Directory Map

```
functions.php               Theme setup, Customizer (header/footer/hero/
                             post-meta), monolithic — ~5,700 lines
header.php                  Header entry point (builder or static layout)
footer.php                  Footer entry point (always builder-driven)
front-page.php, page.php,   Classic template hierarchy (no templates/parts/
single.php, index.php,      — this is not a block/FSE theme)
404.php, searchform.php
page-builder.php,           Active, editor-driven page templates (Template
page-contact.php            Name: headers)
page-services-simple.php,   Legacy, hard-coded HTML page templates — see
template-services.php       Legacy notes above; not part of the site-builder system
theme.json                  Editor-facing design tokens (colors, font
                             sizes, spacing, gradients) — classic theme,
                             so this does not drive global styles directly
style.css                   All theme CSS: :root tokens, builder-*
                             component classes, is-style-ajnanda-* styles,
                             color-scheme classes — single file, no build step

blocks/ajnanda-blocks/      AJNanda custom Gutenberg block library
  loader.php                 PHP: dynamic block registration + render callbacks
  index.js                   JS: client-registered blocks + editor UI
  frontend.js, style.css,    Frontend behavior / block styles
  editor.css

patterns/                   Section Patterns + Page Designs (one file each,
                             auto-registered by WP core) — see docs/patterns.md,
                             docs/page-designs.md

inc/
  patterns.php                Pattern category registration
  page-designs.php             Page Design composer/insert helpers
  color-schemes.php            Preset swatches + editor color-gap fix
  dark-surface-mode.php        Site-wide dark UI toggle (neutral-ramp override)
  font-pairings.php            Font pairing presets, Typography Customizer
                                control, --font-heading/--font-body CSS vars
  site-kits.php                Color scheme + font pairing bundles ("Quick Kits")
  preview.php                  Non-destructive live preview (any pattern,
                                any color scheme and/or font pairing, no DB writes)
  seo.php                      SEO meta box, schema, llms.txt
  duplicate-content.php        "Duplicate" row action
  github-theme-updater.php     Self-hosted theme updater
  theme-details-updater-button.php  Updater button in theme-details modal
  starter-sites/
    class-ajnanda-starter-sites.php     Manifest registry
    class-ajnanda-starter-importer.php  Import engine
    manifests/*.php                     One starter site per file
  admin/
    class-ajnanda-admin.php    Top-level AJNanda admin menu + handlers
    views/*.php                 Admin screen templates (incl. site-kits.php)
    assets/admin.css            Admin-only stylesheet
    assets/admin.js             Preview modal + live-filter behavior
  cli/
    class-ajnanda-cli.php       WP-CLI commands
  site-builder.php             Loader wiring inc/patterns.php,
                                page-designs.php, color-schemes.php,
                                font-pairings.php, site-kits.php,
                                starter-sites/, admin/, cli/ together

js/
  main.js                      Frontend behavior (mobile menu, etc.)
  editor-controls.js           Block-editor-side variations/styles/controls

css/                         Empty — unused

docs/                        Developer documentation (this file + subsystem docs)

bin/                         Release tooling (build-release.sh etc.)
```

---

*This document reflects a direct code read as of 2026-08-09 (branch
`feature/site-builder-system`). This repo bumps its patch version on
almost every release regardless of change size, so a specific version
number here goes stale immediately — a date is more durable. If behavior
looks different than described, verify against the code — this
document should be corrected, not the other way around.*
