# AJNanda Documentation Standard

One rule: **a meaningful feature change updates its documentation in the
same change that makes it.** Not a follow-up task, not "later" — the same
commit/PR.

## Which file to update

| You changed... | Update |
|---|---|
| A section pattern (`patterns/*.php`, not `page-*`) | `docs/patterns.md` |
| A page design (`patterns/page-*.php`) | `docs/page-designs.md` |
| A starter site manifest or the importer | `docs/starter-sites.md` |
| The overall site-builder architecture, WP-CLI, or how the pattern/page-design/starter-site layers connect | `docs/development.md` |
| Anything that changes the answer to "what does AJNanda do and where does it live" for a major system | `docs/AJNANDA-CAPABILITIES.md` |
| A genuinely new major subsystem with nowhere else to document it | Create a new file in `docs/`, then add it to `docs/README.md` |

Use the existing subsystem document whenever one applies. Most changes fit
one of the rows above — reach for a new file only when none does.

## Don't hardcode a specific version number

AJNanda's release process bumps the patch version on almost every release
regardless of change size (`bin/build-release.sh --default` is the normal
flow), so a doc that says "as of version 1.2.12" or "added in 1.3.0" goes
stale within days — both happened in this doc set already. If a doc needs
to reference "when," use a date or describe the change structurally
("added starting at 1.2.7," "the site-builder work") rather than pinning
to whatever the current version happens to be.

## What doesn't need documentation

Trivial implementation details: renaming a private helper, reformatting,
fixing a typo in generated markup, adjusting a single CSS value. Use
judgment — the test is whether a developer (or agent) reading the docs
without reading the diff would come away with a wrong understanding of the
system.

## Before implementing a meaningful feature

This applies to any developer or AI agent working on AJNanda:

1. Read `docs/AJNANDA-CAPABILITIES.md` for the high-level map.
2. Read the relevant subsystem doc (`patterns.md`, `page-designs.md`,
   `starter-sites.md`, `development.md`, or another file listed in
   `docs/README.md`).
3. Search the existing code for something that already does this or close
   to it.
4. If it already exists, extend it — don't build a parallel
   implementation. AJNanda has been extended by mistakenly duplicating an
   already-existing system more than once; check first.
5. Prefer WordPress-native functionality (core blocks, core APIs, the
   Customizer) over a custom mechanism.
6. Keep AJNanda (theme/presentation/site-building) and AJCore (plugin
   functionality — forms, portal, billing) responsibilities separated;
   don't pull AJCore-owned logic into the theme or vice versa.
7. Update the documentation identified in step 1-2 as part of the same
   change.
