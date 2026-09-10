# Reviews & Testimonials

## Blocks and ownership

**Google Reviews** (`ajnanda/google-reviews`) and **Manual Testimonials** (`ajnanda/manual-testimonials`) are dynamic native Gutenberg blocks in the AJNanda Blocks category. They save attributes, not review content, into the page. The editor uses WordPress's authenticated server-side renderer to preview real featured data.

AJ Core owns OAuth, credentials, Google location selection, synchronization, expiry, selection, testimonials, and administration. Set up **AJ Core → Reviews & Testimonials** first; see `ajcore/docs/reviews-testimonials.md` for Google Cloud prerequisites. The theme consumes only AJ Core's documented `ajcore_get_*` PHP functions. It does not query AJ Core's options, tables, or testimonial metadata.

The existing static `ajnanda/testimonials` block remains available for existing content. It is not the new AJ Core-backed Manual Testimonials block, and its legacy carousel placeholder remains a placeholder. Use the new block for managed collections. No combined visual block was added: separate blocks retain a clear distinction between Google-synchronized data and manual testimonials.

## Controls and layouts

Both blocks offer heading, supporting text, grid/list/carousel/featured-single layouts, record limit (1–50), business or newest-publication ordering, configurable default ordering from AJ Core, grid columns (1–4), reviewer images, dates, review text, and preview-line truncation (0 means full text). Featured-single renders one record. Grid reduces to two columns at medium widths and one on small screens; list and featured layouts use one column.

Google-specific controls include overall Google rating, Google's full review count, View on Google and Write a Review buttons, and custom labels. The overall rating/count come directly from Google's snapshot; they are not calculated from the business-selected cards. Buttons appear only when the provider supplies the corresponding URI. Individual ratings and supplied attribution/source/reporting links cannot be hidden through block controls.

Manual Testimonials offers rating and source visibility controls. Its image comes from the testimonial's Media Library attachment. A record must be published and explicitly featured in AJ Core to appear. Name and text cannot be empty. Private notes never leave AJ Core's public DTO.

Carousel controls include pagination visibility, autoplay (off by default), and interval (3–30 seconds). Previous and Next are always provided when more than one slide exists. Automatic rotation is disabled in the editor preview. Native block color/background, font-size, spacing/margins/padding, and wide/full alignment supports integrate with WordPress global styles and AJNanda's existing tokens.

Truncation never changes stored or rendered original review text. A native `details` element provides a clipped visual preview and **Read full text / Collapse text** interaction; the expanded text is the original escaped string. Google-supplied translation text, if available, has a separate disclosure and does not replace the original.

## Patterns

All patterns are in `ajnanda-social-proof` and insert editable dynamic blocks:

| Pattern slug | Starting layout |
|---|---|
| `ajnanda/reviews-google-section` | Google review grid |
| `ajnanda/reviews-manual-section` | Manual testimonial grid |
| `ajnanda/reviews-featured-summary` | One featured Google review with rating/count summary |
| `ajnanda/reviews-carousel` | Google carousel, autoplay off |
| `ajnanda/reviews-call-to-action` | Featured Google review with View/Write calls to action |

No site identity, colors, real reviews, identifiers, or credentials are embedded in patterns. The theme's pre-existing quote/card patterns are retained.

## Empty, disconnected and expired states

Without AJ Core the frontend outputs nothing; the editor explains the dependency. With AJ Core active, editor-only states explain a disconnected Google account, no synchronized reviews, expired data, no featured reviews, or no published featured manual testimonials. Safe stale-data information appears in the editor; technical error summaries stay in administrator-only AJ Core screens.

By default an empty frontend collection outputs nothing. An administrator may configure a plain-text fallback in AJ Core's Display Settings. A stale but valid Google snapshot displays its original as-of date. Expiry is enforced by AJ Core on every public read, including the rating summary. `view.js` also removes an already-open Google collection at its expiry and rechecks when a suspended page becomes visible.

## Attribution and indexing

Each Google card and its rating summary retain visible **Google Maps** attribution, exact reviewer name where supplied, and source/report links when supplied. Compact attribution uses permitted text treatment: `translate="no"`, no wrapping/localization, normal 400-weight sans-serif, accessible foreground/background, and no font download. The only fixed colors in the component stylesheet are Google attribution colors, not a site palette. Other styles inherit theme tokens.

The collection explicitly says **Featured Google reviews selected by the business** and describes the selected ordering. Manual cards are labeled as manually managed testimonials and receive no Google branding. There is no claim of Google partnership, and neither collection emits Review/AggregateRating structured data.

The current official Business Profile review response lacks individual public review URLs, profile attribution URLs, reporting URLs, separate language/translation fields, and relative-time strings. Those optional fields stay empty unless a compliant provider supplies them. When an individual review link is absent, the card accurately labels the supplied location link **View business on Google Maps**. Review IDs and reply URLs are not converted into invented public review URLs. This is a known API limitation, not a hidden fallback claiming to link to the individual review.

Google blocks return no content in feeds, search results, ordinary post REST responses, or WP-CLI-driven rendering. Only authenticated core block-renderer preview requests can render content through REST. Dynamic attributes contain no review content, so AJNanda's existing raw-`post_content` discovery-file generator cannot copy Google text into `llms.txt`/`llms-full.txt`. No Google post type, media copy, schema, or sitemap entry is created. Third-party exporters/indexers must respect the same exclusion; `data-nosnippet` is supplementary and does not promise external search engines will never index public pages.

The theme sets `DONOTCACHEPAGE` and no-store headers when detecting Google blocks in queried post content, including nested/synced patterns; rendering repeats this protection. **Exclude these pages from page/CDN/static caches in hosting configuration.** Cache responses served before WordPress or Google blocks injected late by other plugins can bypass early header detection. Server-side TTL is not enough to prevent a separately cached copy outliving Google's retention limit.

Google's Business Profile storage/performance/no-manipulation policies require a production use-case review; attribution alone does not establish approval for a public curated collection. See [Business Profile policies](https://developers.google.com/my-business/content/policies), [review resource](https://developers.google.com/my-business/reference/rest/v4/accounts.locations.reviews), and [Maps attribution guidance](https://developers.google.com/maps/documentation/places/web-service/policies). The provider is GBP, not Places. Recheck policies and API behavior periodically.

## Shared carousel and compatibility

`blocks/ajnanda-blocks/carousel.php`, `carousel.js`, and `carousel.css` implement a reusable progressively enhanced carousel. Both new review blocks and the existing dynamic `ajnanda/slider` use it. The old Swiper loader/initializer and its conditional CDN request were removed; no new third-party library is needed.

Existing slider content and saved attributes remain valid. Loop, autoplay/delay, pagination, slide/fade effects, and animation speed feed the shared component. Fade now fades the destination after moving to it rather than relying on Swiper's overlapping slide implementation. Speed is bounded to 0–2000 ms, and autoplay intervals to 3–30 seconds. Previous/Next controls now remain available even if a legacy slider had hidden arrows; this is intentional accessibility behavior.

The track is a native horizontally scrollable container with scroll snapping, so touch/swipe and no-JavaScript access work without cloned slides. Controls become visible only after enhancement. All slides stay in the accessibility tree and keyboard tab order; browser focus scrolling keeps offscreen links reachable. Arrow/Home/End navigation applies only when focus is on the track, not inside editable/link/control content. Pagination and manual movement update a polite status; automatic movement does not produce repeated screen-reader announcements.

Autoplay has an explicit pause/start control, pauses on hover/focus and in hidden tabs, stops after manual navigation/touch interaction, and respects reduced-motion changes. Reduced motion disables automatic rotation and animation. Focus outlines and minimum 44px control targets are provided. No autoplay is enabled by default.

## Styling and extension points

`reviews/style.css` uses `--font-body`, `--font-heading`, `--gray-200`, global block styles, and spacing presets. Component selectors are `.aj-reviews`, `.aj-review`, their descriptive children, `.aj-reviews--google|manual`, `.aj-reviews--grid|list|carousel|featured`, `.aj-reviews__empty`, and `.is-stale`. Grid columns and preview lines use local `--aj-reviews-columns` and `--aj-reviews-lines` variables. Carousel selectors use `.aj-carousel`.

Use WordPress block/global styles or scoped theme CSS for presentation changes. Do not remove Google attribution, selection disclosures, or access to the full unmodified text. The public PHP renderer entry points are `ajnanda_render_google_reviews()` and `ajnanda_render_manual_testimonials()`; normal block rendering additionally supplies native block-support styles. `ajnanda_carousel_markup($content, $args)` serves other theme components, and `window.AJNandaCarousel.init(element)` can enhance newly inserted frontend markup.

The editor and frontend JavaScript are plain browser scripts, following the theme's existing block architecture; no npm build is required. Registration and rendering live in `reviews/loader.php`, the editor in `reviews/editor.js`, expiry in `reviews/view.js`, and patterns in `patterns/reviews-*.php`.

## Verification

New WordPress PHPUnit tests cover registration, dependency and empty states, expiry, escaping, source links, attribution, layouts, manual/Google distinction, export exclusions, and reuse of the shared carousel. Node tests exercise the real carousel controller with a minimal DOM harness, including keyboard targeting, touch interaction, autoplay, focus/hover, reduced motion, and removal. They do not replace real browser/screen-reader testing.

The repositories had no test-framework installation/configuration. `phpunit-reviews.xml` uses a separately installed WordPress test library and disposable test database. No dependency is installed automatically; unmatched HTTP calls fail locally. Tests load the relevant production modules, not unrelated theme/plugin setup or schema upgrades.

From this checkout, with an existing `phpunit` executable and configured `WP_TESTS_DIR`:

```sh
phpunit -c /Users/sandip/Projects/ajwp/ajnanda/phpunit-reviews.xml
AJCORE_TESTS_DISABLED=1 phpunit -c /Users/sandip/Projects/ajwp/ajnanda/phpunit-reviews.xml
node --test /Users/sandip/Projects/ajwp/ajnanda/tests/reviews/carousel.test.js
```

The default integration test bootstrap loads the sibling `ajcore` repository; override `AJCORE_TEST_PLUGIN_DIR` for another checkout. The inactive-plugin run skips integration cases and exercises registration/fallback behavior. Review new and existing slider instances in the editor/frontend at narrow/wide sizes, keyboard-only, touch, reduced-motion, dark/light styles, and with JavaScript disabled. Check transitions on existing slides as part of manual regression verification. No build, browser test, or test suite was run during implementation; see AJ Core's implementation report for the exact checks that were run.

## Header Rate Us invitation

AJNanda renders an optional "Rate Us" bar using AJ Core's **Display Settings**.
Enable the prompt and configure both destinations there. Its colors and fonts use
theme tokens. It is hidden when AJ Core is unavailable, the feature is disabled,
or either destination is missing. No business-specific address, phone, or URL is
embedded in the theme.

**Placement** is a theme concern, set in **Customizer → Reviews & Testimonials**,
with an independent value for desktop/tablet and for phones:

- **top** / **bottom** — a full-width bar. `top` is in normal flow above the
  header; `bottom` is fixed to the bottom of the viewport.
- **left** / **right** — a compact card (`position: fixed`) pinned to that edge
  and vertically centred, contents stacked.
- **top-card** / **bottom-card** — a compact card fixed to the top or bottom
  edge and horizontally centred, contents kept on one row.

All four compact-card positions drop the address and social rows, and carry a
**collapse handle** on the card's inner edge: a chevron button that folds the
card down to a 44px handle and back. The choice is stored in the visitor's own
browser (`localStorage`, key `ajnandaReviewPrompt`) and nothing is sent
anywhere. Because desktop and phones can use different positions, `prompt.js`
resolves which one is live at the current breakpoint and only then marks the
element (`--collapsible` plus `data-active-card`); with JavaScript off the
handle never appears and the card stays open. Full-width bars are never
collapsible.

Defaults are `top` for both. Only the phone `top` case loads any scroll logic.

The five individually labeled star links navigate directly: **1–4 stars** open
private feedback in the current tab; **5 stars** opens the configured Google
review URL in a new tab. There is no separate visible "Send private feedback"
link — the stars are the only control. By default the stars sit dimmed and fill
left-to-right, up to the pointer, on hover or keyboard focus, so the row reads as
an invitation rather than a filled-in rating. Ratings are not recorded, submitted,
or appended to destination URLs. Nothing is automatically published as a Manual
Testimonial.

Native links support keyboard activation and work without JavaScript, with visible
focus and 44px star targets (38px on phones). The existing expiry script and
no-store mechanism still apply when the Google link comes from a temporary API
snapshot.

When the **phone** position is `top`, the bar is not shown in place at the top of
the page: a small always-loaded script (`reviews/prompt.js`) pulls it out of the
flow and slides it back in as a fixed strip once the visitor scrolls past ~64px,
then hides it again at the very top; while it shows, the sticky header is offset
down by its height so nothing is covered. Without JavaScript the bar renders in
place. Any other phone position (`bottom` / `left` / `right`) is static CSS and
loads no script. On phones the address and social-icon rows are always dropped.

Files: `reviews/prompt.php`, `reviews/prompt.css`, and `reviews/prompt.js`
alongside the existing block files. `functions.php` loads the component;
`header.php` renders it after the skip link. No new blocks, forms, dependencies,
or build steps are added.

### Current non-AJNanda header

AJNanda's header cannot change an active Schema/child-theme header. That active
theme can consume AJ Core's public PHP interface without loading AJNanda. Configure
the explicit Google URL override for this minimal native-details example: it
intentionally does not emit temporary API-derived links requiring expiry handling.
Replace the old rating-dependent link group in the active theme's header template
with the following, and adapt its `.dm-rate-us` styling in that theme:

```php
<?php
$review_prompt = function_exists( 'ajcore_get_review_prompt_settings' )
    ? ajcore_get_review_prompt_settings()
    : array();
if ( ! empty( $review_prompt['enabled'] ) && ! empty( $review_prompt['available'] )
    && empty( $review_prompt['expires_at'] ) ) :
?>
<details class="dm-rate-us">
    <summary>
        <?php echo esc_html( $review_prompt['label'] ); ?>
        <span aria-hidden="true">★★★★★</span>
    </summary>
    <p><a href="<?php echo esc_url( $review_prompt['feedback_url'] ); ?>"><?php esc_html_e( 'Send private feedback', 'ajcore' ); ?></a></p>
    <p><a href="<?php echo esc_url( $review_prompt['google_review_url'] ); ?>"><?php esc_html_e( 'Leave a Google review', 'ajcore' ); ?></a></p>
</details>
<?php endif; ?>
```

All stars in this no-JavaScript legacy example belong to the same accessible
summary and open the same choices. It does not record a selected numeric rating.
No files on the live site were edited. Clear any page/CDN caches after changing
these administrator-managed navigation settings or the legacy header template.

### Manual verification for the prompt

- Enable it with two HTTPS destinations: stars 1–4 must open private feedback;
  star 5 must open the configured Google URL in a new tab.
- Test Tab/Enter activation and a narrow viewport.
- Disable JavaScript: all five star links and the separate feedback link must work.
- Remove either destination with no valid Google fallback: the prompt must disappear.
- With no Google override, check expiry/disconnection and cache bypass; with an
  explicit override, navigation remains independent of OAuth/synchronization.
- Confirm the feedback form opens normally and submissions are not automatically
  created or published as AJ Core testimonials.

Quick syntax commands (no WordPress runtime or builds):

```sh
php -n -l /Users/sandip/Projects/ajwp/ajcore/modules/reviews/public-api.php
php -n -l /Users/sandip/Projects/ajwp/ajcore/modules/reviews/class-ajcore-reviews-admin.php
php -n -l /Users/sandip/Projects/ajwp/ajnanda/blocks/ajnanda-blocks/reviews/prompt.php
php -n -l /Users/sandip/Projects/ajwp/ajnanda/header.php
php -n -l /Users/sandip/Projects/ajwp/ajnanda/functions.php
```
