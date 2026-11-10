# Single-post blocks

Classic theme: `header.php`/`footer.php` are the builder-driven document shell,
so there is no `templates/single.html` and `block-templates` support is NOT
enabled (it would bypass the shell on every site).

## Layout
- `single.php` stays the default. Opt-in full block layout: Customizer >
  Template Slots > Single post > `Single Post — Hero + Reading Column`
  (`patterns/template-single-post.php`).
- In-editor hero: insert a core **Post Featured Image** block in the post.
  `inc/single-post-blocks.php` supplies post context on the front end, and
  `single.php` skips its built-in hero when that block exists.

## Widths
`theme.json` is the source: `settings.custom.readingWidth` (`--wp--custom--reading-width`,
post column) and `settings.layout.wideSize`. `style.css` single-post rules and
`css/editor-post.css` read the variable (800px fallback).

## Styles
`css/post-blocks.css` (front, single posts; and post editor) + `css/editor-post.css` (post editor only,
injected via `block_editor_settings_all`). Block styles (Styles panel), registered in
`inc/single-post-blocks.php`: group *Callout* / *Dark CTA band*, column *Info card*,
details *FAQ*. Tables scroll horizontally; right-aligned cells don't wrap.
Base looks for table, details, buttons, captions apply under `.ajn-prose` / editor only.

## Patterns
Category **Blog** (`ajnanda-blog`): `patterns/blog-{callout,info-cards,cta-band,faq,image-caption}.php`.
