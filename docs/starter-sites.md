# AJNanda Starter Sites

A Starter Site imports a coordinated set of pages (built from AJNanda Page
Designs) plus a primary navigation menu, in one action. It's the fastest
path from "activate AJNanda" to a launchable site:

```
Appearance-level: activate AJNanda → AJNanda → Starter Sites → pick one → Import → replace placeholder content → launch
```

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
- **Preview before you commit**: `AJNanda_Starter_Importer::preview($slug)` /
  `wp ajnanda starter preview <slug>` / the "Preview Import" button report
  create / already-imported / slug-conflict for every page without changing
  anything.

## Current starter sites

`corporate`, `technology`, `professional-services`, `product-reseller`,
`property-management`, `insurance-financial`, `minimal-business` — see
`wp ajnanda starter list` or the Starter Sites admin screen for each one's
page list. Slugs are stable identifiers (see docs/development.md's
Future Automation note) and are never renamed.

## Adding a new starter site

1. Decide the page list and which Page Design each page uses (add new page
   designs first if needed — see `docs/page-designs.md`).
2. Create `inc/starter-sites/manifests/{slug}.php` following the schema
   above.
3. Verify with `wp ajnanda starter list` and `wp ajnanda starter preview {slug}`.
4. Test-import into a disposable install before shipping.
