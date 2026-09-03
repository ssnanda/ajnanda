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
