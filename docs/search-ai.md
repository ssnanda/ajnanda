# Search & AI

AJNanda's Search & AI module is a theme-native discovery layer under
**AJNanda → Search & AI**. Its canonical Site Profile, central Content Access
Policy, capability ownership service, schema/discovery outputs, readiness
checks, insights, and crawler observations share the services in
`inc/search-ai/`.

## Crawler Log architecture

Crawler Log is observational. It records plausible search/AI crawler requests
that reach WordPress/PHP; it is not visitor analytics, a firewall, or proof
that an absent crawler never visited. A CDN or edge cache can serve requests
without WordPress seeing them.

`AJNanda_Search_AI_Crawler_Logger` classifies only GET/HEAD requests with a
known registry token or a conservative crawler-like User-Agent. It skips
authenticated users, wp-admin, AJAX/REST traffic, and static assets. Paths are
normalized without query strings. Bodies, cookies, authorization headers, and
form data are never stored. Logging happens at shutdown and failures never
interrupt frontend rendering.

The event source is stored explicitly as `wordpress`. The storage and query
layer treats source as a first-class field so a future Cloudflare importer can
add observations without replacing the UI. No Cloudflare integration exists
yet.

## Database table

`{$wpdb->prefix}ajnanda_crawler_events` is created by a versioned, idempotent
`dbDelta()` migration. It contains:

- UTC timestamp, normalized request path, HTTP method/status, and source
- crawler/provider keys, category, reported identity, and reported User-Agent
- verification state, method, and reason
- privacy-mode-specific IP value

Indexes cover timestamp, provider+timestamp, category+timestamp,
verification+timestamp, source+timestamp, and a bounded path prefix. Admin
queries default to seven days, use prepared filters, paginate at 25 events,
and bound aggregate groups and pending verification batches.

## Reported identity and verification

Reported identity means only that the User-Agent matched a registry token. It
does not mean the requester was authentic. Verification states are Verified,
Reported only, Verification failed, Not verifiable, and Pending.

Googlebot and Bingbot support forward-confirmed reverse DNS: reverse-resolve
the source IP, require an exact allowed provider-domain suffix, forward-resolve
the hostname, then require the original IP in the result. Private/reserved IPs
fail before DNS. Results are cached (24 hours when verified, 6 hours when
failed), and a five-minute cron processes at most five pending events per run.
Providers without a documented DNS method remain Not verifiable; AJNanda does
not invent verification from a User-Agent or reverse DNS alone.

## IP privacy and retention

Crawler Log defaults to disabled, 90-day retention, and anonymized IP storage.
Settings offers:

- anonymized IP (default)
- one-way keyed hash
- full crawler IP, required for deferred provider DNS verification

Only crawler-classified requests enter the table. Daily cleanup deletes at
most 1,000 expired rows per run and scheduling is idempotent. Disabling logging
stops new observations and removes the verification schedule; cleanup remains
scheduled so retained data still expires.

## Admin and readiness

The Crawler Log tab provides period/provider/category/verification filters,
pagination, totals, latest observation, activity by crawler/category,
verification counts, top paths, and an escaped event-detail view. All access
requires `manage_options`; settings writes also require a nonce.

Readiness checks table health and retention only when logging is enabled. Zero
traffic and an intentional disabled state do not reduce Search & AI readiness.

## Suspicious bot activity

The Suspicious Bots tab analyzes the existing bounded Crawler Log data for conservative credential-file, configuration-file, and exploit-path probes. It reports behavioral evidence rather than claiming an unverified sender is a confirmed spam bot. Recognized crawler names used on suspicious paths remain reported identities unless separately verified. The dashboard provides high-level counts, recent evidence, and rule-based guidance; it never blocks requests automatically and recommends hosting or edge-layer controls when intervention is warranted.

## Actionable report export

Search & AI provides a JSON handoff report for offline review and code-assisted website improvements. It combines readiness issues, provider-backed Insights, public configuration, capability ownership, bounded crawler aggregates, and sanitized suspicious-request evidence. API credentials, stored IP values, raw User-Agent strings, and the general event-level crawler log are deliberately excluded. The `action_items` section is the concise starting point; the remaining sections provide supporting evidence and policy context.
## Semantic content and schema graph

AJNanda emits one connected, Site Profile-backed Schema.org graph when AJNanda owns the schema capability. The graph contains stable identity, WebSite, WebPage, and Article entities, then accepts provider-neutral semantic contributions from visible block content.

Native semantic blocks are the authoritative source. Enabled AJ FAQ and AJ How To blocks derive their structured data from their visible headings, Details answers, and list steps. AJ Team can opt into Person markup using its existing visible name, image, and biography. Contributors do not print independent JSON-LD scripts.

The contributor pipeline parses a post once, traverses nested and reusable blocks with cycle/depth protection, validates incomplete and placeholder content, deduplicates equivalent nodes, and merges relationships into the main graph. Content excluded from schema relationships is never traversed. When another recognized SEO provider owns schema, the existing capability delegation suppresses AJNanda's graph; provider adapters are intentionally separate future work.

Legacy FAQ compatibility is limited to visible WordPress Details blocks. Generic question-shaped headings are not inferred as FAQ schema.

## Page-level business entities

Pages may explicitly declare their primary meaning through `_ajnanda_primary_entity_type`. Missing metadata remains a General page; AJNanda never infers intent from a title, slug, page design, starter site, pattern, block, or content. Supported roles are General page, Service, Product, and Primary business location.

Service and Product roles contribute provider-neutral primary nodes to the existing connected graph. Their name, URL, description, and featured image reuse the page's existing sources. Service may reference the Site Profile identity as provider and reuse configured service areas. Product intentionally omits offers, prices, availability, brand, manufacturer, seller, and identifiers until explicit structured sources exist.

Primary business location points the WebPage at the existing canonical Site Profile identity and never creates a second inferred Place or LocalBusiness. It requires Physical location mode and a complete structured address. Malformed or invalid role configurations fall back to WebPage and appear in readiness diagnostics.

## Geographic and service-area semantics

AJNanda stores reusable service-area records in the theme-native `search_ai_service_area_records` theme mod and the business defaults in `search_ai_profile_default_service_area_ids`. Records have stable IDs and an explicit geographic type: Country, State/province, County/administrative area, City, Postal/ZIP code, or Custom named region. Existing `search_ai_profile_service_areas` strings are preserved as deterministic imported Custom records and remain valid Text values until an administrator classifies them.

The Site Profile identity uses the business-default records for `areaServed`. Formal records contribute stable geographic nodes to the connected graph; custom areas and postal areas lacking sufficient country context safely remain Text. Physical address and service coverage remain independent concepts.

Service pages inherit the business defaults unless `_ajnanda_service_area_mode` is explicitly set to `override`; selected record IDs are stored in `_ajnanda_service_area_ids`. An intentionally empty override omits `areaServed` and is surfaced as an editor/readiness warning. Product and General pages do not receive service-area semantics. No geography is inferred from page titles, content, customer addresses, or free-form prose.

## Discovery eligibility rules

`AJNanda_Search_AI_Discovery_Files::eligible_for_discovery($subject, $channel)` is the
single gate every generated AI discovery output shares (`llms.txt` entries, Important
Pages, foundational pages, and the connected schema graph). A URL qualifies only when
**all** of the following hold:

- the object exists and is a real published post/page (`publish`, not draft, pending,
  private, trashed, or deleted)
- the post type is publicly viewable
- the central Content Access policy reports it publicly accessible
- it is search indexable — not `noindex` through the legacy per-post meta and not
  `noindex` as a Content Access exclusion effect
- it is not excluded through Content Access
- Content Access still advertises it for the requested channel (`llms_txt`,
  `schema_relationships`, `sitemap`, …)
- its permalink is canonical — it round-trips through `url_to_postid()` (the static
  front page and posts page are exempt because their URLs resolve to an archive query)

WordPress still knowing that a page once existed is never sufficient. The
`ajnanda_search_ai_discovery_eligibility` filter can add reasons.

## Content Access and llms.txt

`llms.txt` never enumerates the page tree. It advertises the Site Profile, the resolved
Important Pages, the eligible foundational pages, and a bounded list of recent articles —
each item re-checked through `eligible_for_discovery()` at render time. A page excluded
in Content Access (or given the "Exclude from llms.txt" effect, or set to `noindex`) is
withheld from `llms.txt` immediately, with no manual URL editing.

## Important Page validation

`AJNanda_Search_AI_Important_Pages::resolve()` splits the administrator's stored selection
into `valid` and `invalid` buckets. The stored theme mod is preserved as intent: saving
the Discovery Files form keeps any ID that still has a backing page object, even an
ineligible one, and only discards IDs with no page at all. Invalid selections are shown
in the admin with a "Not discoverable" badge and the specific reason, and are withheld
from `discovery_ids()` so they never reach public output. `foundational_ids()` applies the
same gate to the homepage and posts page.

## Stale discovery detection

`AJNanda_Search_AI_Stale_References::scan()` powers the **Stale AI references** check on
Search & AI → Overview (healthy state: `0`) and a scored readiness check of the same name.
It inspects the URLs AJNanda actively promotes and reports any that are no longer
appropriate discovery targets:

- Important Page selections that fail `eligible_for_discovery()` (unpublished, trashed,
  deleted, `noindex`, excluded, non-canonical)
- the homepage or posts page when either is ineligible
- custom schema identity links that point at internal URLs which no longer resolve
  (external authoritative links are never flagged)
- AJNanda Content Access exclusions that reference deleted content

Each finding carries the offending URL/label, the source system, and a plain-language
reason. Findings also appear in the exported handoff report under
`action_items.stale_ai_references` and `discovery.stale_references`.

## Live roadmap

The canonical [Search & AI Roadmap](search-ai-roadmap.md) lives in GitHub. The WordPress Roadmap tab is a cached, sanitized viewer of that document and does not maintain a separate feature registry. When a deferred capability becomes worth tracking, update the Markdown roadmap. When it ships, move it from **What's Next** to **Already in AJNanda** and revise its description when necessary.
