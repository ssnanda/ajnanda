# AJNanda Page Designs

A **Page Design** is a complete, ready-to-use page — Home, About, Services,
Contact, etc. — built by stacking AJNanda section patterns. Unlike a section
pattern, a page design registers with `Block Types: core/post-content` and
`Post Types: page`, which is what makes WordPress core show it automatically
in the native **"Choose a pattern"** modal on **Pages → Add New**. That
modal is the intended primary workflow — there is no separate,
theme-specific page-creation UI to learn.

```
Pages → Add New → "Choose a pattern" modal → pick a Page Design → Publish
```

(The AJNanda → Page Library admin screen offers the same designs with a
one-click "Add as New Page" action, for people who want to browse first.)

## Architecture: composition, not duplication

A page design file does not contain page-length block markup. It lists
which section patterns to stack, in order, and a small helper pulls each
section's markup from the pattern registry at read time:

```php
<?php
/**
 * Title: Home — Super Bold
 * Slug: ajnanda/page-home-super-bold
 * Categories: ajnanda-page-designs
 * Block Types: core/post-content
 * Post Types: page
 * Description: A dramatic, high-contrast homepage.
 *
 * @package AJNanda
 */
echo ajnanda_compose_page_content(array(
    'ajnanda/section-hero-super-bold',
    'ajnanda/section-stats-big-numbers',
    'ajnanda/section-services-card-grid',
    'ajnanda/section-testimonial-featured',
    'ajnanda/section-cta-super-bold',
));
```

`ajnanda_compose_page_content()` (in `inc/page-designs.php`) reads each
listed slug's already-registered `content` straight from
`WP_Block_Patterns_Registry` and concatenates it. The section pattern file
is the one canonical source of that markup — editing
`patterns/hero-super-bold.php` changes every page design that references
it, with nothing to keep in sync by hand. This works because WordPress
executes pattern files (`ob_start(); include $file; content =
ob_get_clean();`) rather than treating them as static text, so ordinary PHP
— including calling another function — is allowed in the file body.

## No dependency after insertion

Whichever way a page design is inserted — the native pattern modal, the
Page Library screen, or a Starter Site import — the result is a plain block
markup string written into `post_content`, exactly like any other pattern
insertion in WordPress core. There is no synced/reusable-block relationship
and no plugin dependency: once the page is saved, it is 100% ordinary,
independently editable Gutenberg content. Move, delete, duplicate, and edit
sections freely.

## Naming & categories

- Slug: `ajnanda/page-{type}-{variant}`, e.g. `ajnanda/page-services-professional`.
- Category: always `ajnanda-page-designs` ("AJNanda: Page Designs") — this
  is also the category the Page Library admin screen and
  `wp ajnanda page-design list` filter on.
- File: `patterns/page-{type}-{variant}.php`.

## Current page designs

Grouped by type (see `wp ajnanda page-design list` or the Page Library
admin screen for the live list): Home (6 variants, including a "Super
Bold" high-contrast design and a Technology/SaaS design), About (3),
Services (4), Products (3), Marketing/Landing (3), Company — Team,
Partners, Careers, FAQ, Contact, Locations (6), Content — Blog Landing,
Resource Center (2).

## Adding a new page design

1. Decide which existing section patterns to stack (add new section
   patterns first if needed — see `docs/patterns.md`).
2. Create `patterns/page-{name}.php` with the header shown above
   (`Categories: ajnanda-page-designs`, `Block Types: core/post-content`,
   `Post Types: page`) and an `echo ajnanda_compose_page_content([...])`
   call.
3. If it belongs in a Starter Site, add its slug to that starter's
   manifest (`docs/starter-sites.md`).
4. Verify with `wp ajnanda page-design list` and by checking Pages → Add
   New shows it in the pattern modal.
