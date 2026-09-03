# AJNanda Documentation Index

| File | What it documents | Read/update it when... |
|---|---|---|
| [`AJNANDA-CAPABILITIES.md`](AJNANDA-CAPABILITIES.md) | High-level architectural map of everything AJNanda currently does — theme setup, the site-builder system, custom blocks, core block enhancements, design system, header/footer, admin tools, AJCore integration, legacy notes | You're new to the codebase, or you're about to add a capability and need to check whether something similar already exists |
| [`patterns.md`](patterns.md) | Section Patterns — reusable page sections, how they're registered, naming conventions, categories, colors/gradients | You're adding, changing, or removing a Section Pattern |
| [`page-designs.md`](page-designs.md) | Page Designs — complete pages composed from Section Patterns | You're adding or changing a Page Design |
| [`starter-sites.md`](starter-sites.md) | Starter Sites — manifest schema, the import engine, safety guarantees | You're adding a Starter Site or changing importer behavior |
| [`site-kits.md`](site-kits.md) | Font Pairings and Site Kits — the typography equivalent of Color Schemes, and the color+font bundles built on top of both | You're adding a font pairing or a site kit, or changing how either is applied/previewed |
| [`development.md`](development.md) | Overall site-builder architecture, file map, admin UI, WP-CLI, testing checklist | You're changing how the Patterns → Page Designs → Starter Sites layers connect, or the admin/CLI interfaces to them |
| [`search-ai.md`](search-ai.md) | Search & AI architecture, crawler-event storage, verification, privacy, retention, and visibility limits | You're changing Search & AI discovery or Crawler Log behavior |
| [`documentation-standard.md`](documentation-standard.md) | The rule for keeping these docs in sync with code changes | You're not sure which doc a change should update |

## Where to start

New to AJNanda, or an AI agent about to make a change? Read
`AJNANDA-CAPABILITIES.md` first, then the specific subsystem doc for
whatever you're touching, then search the actual code. See
`documentation-standard.md` for the full checklist.

## Outside `docs/`

`README.md`, `SETUP-GUIDE.md`, and `INSTALLATION.md` at the theme root
predate this documentation set and describe an earlier version of the
theme (older "NCLLC" branding and marketing copy) — they are not kept in
sync with the current site-builder system. Prefer the files in this
directory.
