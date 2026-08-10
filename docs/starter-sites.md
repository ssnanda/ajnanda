# AJNanda Starter Sites

A Starter Site imports a coordinated set of pages (built from AJNanda Page
Designs) plus a primary navigation menu, in one action. It's the fastest
path from "activate AJNanda" to a launchable site:

```
Appearance-level: activate AJNanda → AJNanda → Starter Sites → pick one → Import → replace placeholder content → launch
```

The admin screen shows one starter at a time, chosen from a dropdown,
instead of every starter's full detail stacked on one long page. Each
page in the selected starter is a card with a live thumbnail — a
scaled-down iframe of the same non-destructive preview URL "Preview"
already opens, not a separate screenshot system — that only loads once
that starter's panel is actually selected.

## Manifest schema

Each starter site is one file in `inc/starter-sites/manifests/*.php`,
returning a plain array — no classes to extend, no registration call to
remember:

```php
<?php
return array(
    'slug'        => 'corporate',                 // stable identifier — never rename, only add new ones
    'label'       => __('Corporate / Business', 'ajnanda'),
    'description' => __('...', 'ajnanda'),
    'pages'       => array(
        array(
            'key'         => 'home',               // stable per-manifest identifier, used for tracking/idempotency
            'title'       => __('Home', 'ajnanda'),
            'slug'        => 'home',               // desired page URL slug
            'page_design' => 'ajnanda/page-home-corporate',
            'menu_order'  => 1,
        ),
        // ...more pages...
    ),
    'menu' => array(
        'label' => __('Primary', 'ajnanda'),
        'pages' => array('home', 'about', 'services', 'blog', 'contact'), // menu item order, by page key
    ),
    'home_page_key'  => 'home',   // page key to set as the site front page, if the importer is asked to
    'posts_page_key' => 'blog',   // page key to set as the Posts page, or '' if none
);
```

`inc/starter-sites/class-ajnanda-starter-sites.php` (`AJNanda_Starter_Sites`)
auto-loads every manifest file in that directory — adding a starter site
never requires touching any other file.

## Importing

`AJNanda_Starter_Importer` (`inc/starter-sites/class-ajnanda-starter-importer.php`)
is the single import engine used by:

- **AJNanda → Starter Sites** admin screen (preview + import UI, with
  per-page checkboxes, draft/publish choice, and homepage/menu options)
- `wp ajnanda starter import <slug>` (see `docs/development.md`)

### Safety guarantees

- **Idempotent**: every created page is tagged with post meta
  (`_ajnanda_starter_site`, `_ajnanda_page_design`, `_ajnanda_starter_page_key`).
  Re-running an import finds pages it already created and skips them —
  it never creates duplicates.
- **Never overwrites unrelated content**: if a manifest page's intended URL
  slug is already used by a page AJNanda didn't create, the new page gets a
  suffixed slug instead of taking over that URL.
- **Menu is opt-in-safe**: the primary navigation menu is only built if that
  location is currently empty. Pass `--overwrite-menu` /
  check "Replace an existing primary menu" to force it.
- **Homepage is opt-in only**: `show_on_front`/`page_on_front`/
  `page_for_posts` are only touched if explicitly requested, and even then
  only if the current front page is unset or was itself created by an
  AJNanda import — a site owner's deliberately configured homepage is never
  silently replaced.
- **Selective import**: pass specific page keys (`--pages=home,about,contact`
  on the CLI, or checkboxes in the admin screen) instead of the whole
  starter site.
- **Preview the import plan**: `AJNanda_Starter_Importer::preview($slug)` /
  `wp ajnanda starter preview <slug>` / the "Preview Import" button report
  create / already-imported / slug-conflict for every page without changing
  anything. This is a *status* preview — what will happen — not what the
  pages will look like; see "Visual preview" below for that.

## Visual preview

Separate from the import-plan preview above: every page listed under a
starter site on the Starter Sites admin screen has its own "Preview"
link, which opens a real, fully rendered page — actual header, footer,
and CSS — in an in-page modal (with a fallback link to open it in a new
tab). Nothing is saved; it's the same non-destructive preview engine
(`inc/preview.php`) used by the Page Library, Patterns, and Color Schemes
screens, since a starter site's page is just a reference to a Page Design
slug. Use it to see what a page will actually look like *before* deciding
whether to import it — the status preview above only tells you whether it
would be created, not what it contains. See `docs/development.md`
("Preview") for how the engine works.

Each page in the list also shows an inline "Already imported" / "Not
imported yet" / "URL conflict" badge, computed the same way as the status
preview above but shown by default — no need to click "Preview Import"
just to check whether a starter site (or one of its pages) has already
been imported.

**Preview Whole Site**: the per-page Preview links above are independent —
each renders one page with no way to get to the others. "Preview Whole
Site" (`ajnanda_get_starter_preview_url()`, `inc/preview.php`) instead
opens the starter's home page with a connected click-through nav bar
added to the sticky preview banner, listing every other page in that
starter (current page highlighted). Click through Home → Music → Shows →
About like a real visitor would — still nothing saved, still no real nav
menu or real pages involved; each click is just another
`ajnanda_get_preview_url()` call carrying the same page list forward. Any
color scheme/font pairing override on the starting page is carried to
every page you click through to, so you can preview a whole starter site
in a specific Site Kit before deciding to import it or apply that kit.

## Current starter sites

`corporate`, `technology`, `professional-services`, `product-reseller`,
`property-management`, `insurance-financial`, `minimal-business`,
`music-artist`, `personal-creative`, `baby-announcement`, `family-blog`,
`developer-portfolio` — see `wp ajnanda starter list` or the Starter
Sites admin screen for each one's page list. Slugs are stable identifiers
(see docs/development.md's Future Automation note) and are never
renamed — **labels are not**: several of these are labeled with a real
person's name by request (e.g. `music-artist`'s label is "Aad - Music
Artist / DJ") rather than the fully generic label the slug's own name
suggests. Relabel a manifest's `'label'` value freely; never touch its
`'slug'`.

Five of these are also each written to pair with a specific Site Kit
(`docs/site-kits.md`): `music-artist` → "Neon Night", `personal-creative`
→ "Bubblegum Pop", `baby-announcement` → "Little One", `family-blog` →
"Family Warmth", `developer-portfolio` → "Developer Portfolio" — though
nothing enforces that; a starter site's pages just follow whatever color
scheme/font pairing happen to be active, same as any other page.
`family-blog` is a good example of composition at the starter-site level,
not just within a page design: it reuses the exact same 4 page designs
`personal-creative` uses (`page-home-personal`, `page-blog-landing`,
`page-gallery`, `page-about-story`) — the two starters differ in
curation/audience and default Site Kit, not markup. `developer-portfolio`
similarly reuses `page-about-story` and `page-contact` rather than
inventing developer-specific variants of either.

## Adding a new starter site

1. Decide the page list and which Page Design each page uses (add new page
   designs first if needed — see `docs/page-designs.md`).
2. Create `inc/starter-sites/manifests/{slug}.php` following the schema
   above.
3. Verify with `wp ajnanda starter list` and `wp ajnanda starter preview {slug}`.
4. Test-import into a disposable install before shipping.
