# AJNanda Search & AI Roadmap

AJNanda is building Search & AI features that help websites clearly communicate their identity, content, services, locations, audiences, and access preferences to traditional search engines and modern AI systems.

This roadmap shows what we're considering next and what AJNanda already supports.

## What's Next

### Target Audience

Tell search engines and AI systems whether your website or content is intended for adults, children, parents, businesses, professionals, students, or other audiences.

### Multiple Business Locations

Let businesses define multiple real offices, stores, branches, clinics, or other physical locations without confusing them with service areas.

### Opening Hours

Add structured business hours that can be reused across location displays and machine-readable business information.

### Location Blocks

Let pages display structured business-location information from the same source used by Search & AI instead of entering addresses and contact information repeatedly.

### Structured Pricing

Turn visible AJNanda pricing into reusable structured information instead of trying to interpret free-form price text.

### Offers

Connect structured prices and purchasing terms to the Service or Product they actually describe.

### Richer Product Information

Add optional product details such as brand, manufacturer, model, SKU, MPN, GTIN, and other identifiers when the website genuinely has that information.

### Reviews & Ratings

Connect visible reviews and ratings to the specific Service, Product, or other entity they actually review while respecting search-engine policies.

### Markdown / AI-Friendly Content

Explore clean machine-readable versions of important content that AI systems can consume without replacing the normal website experience.

### Faster Search Discovery

Explore technologies such as IndexNow that can help supported search engines discover newly published or updated content faster.

### Content Update Signals

Explore safe ways to notify compatible services when important website content changes.

### Cloudflare Crawler Visibility

Allow crawler reporting to include requests handled at the Cloudflare edge that never reach WordPress.

### SEO Plugin Integration

Allow AJNanda's semantic intent to work more deeply with supported SEO plugins such as Yoast, Rank Math, SEOPress, and AIOSEO when those plugins own schema output.

### Product Geography

Allow Product-related geographic information only where there is a clear real-world use case and reliable structured data.

### Customer Geography

Explore whether there is a useful and semantically correct way to describe where customers may come from without confusing customer origin with where a Service is available.

### Service-Area Landing Pages

Let a Service page explicitly target a geographic service area such as Charlotte or Huntersville without implying that the business has a physical office there.

## Already in AJNanda

AJNanda already includes these Search & AI capabilities.

### Site Profile

Give search engines and AI systems a consistent understanding of your business, organization, identity, contact information, and website.

### Physical Location

Describe the business's real physical address while allowing service-area and non-public-location businesses to keep addresses private.

### Geographic & Service Areas

Clearly separate where a business is physically located from where its services are available, with reusable service areas and service-specific coverage.

### Content Access

Choose which pages and content search engines and AI systems may discover while keeping excluded content out of discovery.

### AI Discovery Controls

Control AI search/retrieval and AI model-training access separately instead of treating every AI crawler the same.

### Search Engine Discovery

Help traditional search engines discover public AJNanda content through WordPress sitemaps, robots policies, and related discovery signals.

### LLMS.TXT

Provide AI systems with a simple overview of the site, important pages, and recent content through llms.txt.

### Important Pages

Explicitly identify the pages that best explain the website instead of relying on automatic guesses.

### Structured Site Identity

Connect Organization, WebSite, WebPage, and Article information into one coherent machine-readable graph.

### Articles

Describe WordPress posts as Articles connected to the website, publisher, and page they belong to.

### FAQ Content

Let AJNanda FAQ blocks explicitly describe visible questions and answers to search engines and AI systems.

### How-To Content

Let AJNanda How To blocks describe visible instructions and their ordered steps.

### People

Allow Team blocks to explicitly describe a person using the visible name, image, and biography without requiring duplicate profile information.

### Page Meaning

Explicitly tell AJNanda whether a page primarily describes a general page, Service, Product, or primary business location.

### Services

Describe Service pages as real Service entities connected to the organization providing them.

### Products

Describe Product pages as Product entities without inventing prices, offers, brands, availability, or identifiers that the site does not actually provide.

### Primary Business Location Pages

Connect a location-focused page to the business's existing Site Profile identity without creating duplicate business/location entities.

### Readiness Checks

Show whether important Search & AI capabilities are configured correctly and explain what needs attention.

### Search Insights

Surface useful search-performance opportunities from available site data without overstating what the data proves.

### AI & Search Crawler Log

Record supported search and AI crawler visits that reach WordPress so site owners can see who is accessing the site.

### Crawler Identity Verification

Verify supported crawler identities such as Google and Bing where possible instead of trusting the reported user-agent name alone.

### Crawler Privacy & Retention

Control crawler-log retention and IP privacy while keeping crawler observation useful.

### Suspicious Bot Activity

Identify high-confidence credential, configuration, and exploit-path probes in observed crawler traffic while keeping behavioral suspicion separate from verified identity.

### SEO Plugin Ownership

Detect when another SEO plugin owns a capability and avoid blindly producing conflicting duplicate output.

## Development Principle

AJNanda prefers explicit, reliable information over guessing.

For example:

- We do not assume a page is a Service because its URL contains `/services/`.
- We do not assume Charlotte is a physical office just because a business serves Charlotte.
- We do not invent prices, ratings, product identifiers, audiences, or locations when the site has not explicitly provided them.

## Roadmap Note

Roadmap items describe areas being considered for future AJNanda releases. Their scope, order, and implementation may change as the theme evolves.
