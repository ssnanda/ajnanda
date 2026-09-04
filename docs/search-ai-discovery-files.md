# Search & AI discovery-file integration

AJNanda generates `/robots.txt`, `/llms.txt`, `/llms-full.txt`, `/ai.txt`, and
`/.well-known/security.txt` dynamically. Do not create physical copies of these files in
the document root; LiteSpeed would serve those copies before WordPress and they would
become stale.

## Apache / LiteSpeed redirects

Place these rules before the standard WordPress rewrite block in the document-root
`.htaccess` file:

```apache
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule ^\.well-known/llms\.txt$ /llms.txt [R=302,L,NE]
RewriteRule ^\.well-known/change-password$ /client-portal/ [R=302,L,NE]
</IfModule>
```

The temporary redirects are intentional: they avoid a long-lived browser or CDN cache
while these nonstandard aliases are in use. They may be changed to `R=301` after the
destinations are considered permanent.

## WordPress head discovery

The theme registers this equivalent output on `wp_head`:

```html
<link rel="alternate" type="text/plain" href="https://example.com/llms.txt" title="LLMs.txt">
```

The link is an additional discovery hint. It is not a standardized crawler directive.

## Required response checks

```bash
curl -i https://example.com/robots.txt
curl -i https://example.com/llms.txt
curl -i https://example.com/llms-full.txt
curl -i https://example.com/ai.txt
curl -i https://example.com/.well-known/security.txt
curl -I https://example.com/.well-known/llms.txt
curl -I https://example.com/.well-known/change-password
```

Purge LiteSpeed and Cloudflare caches after deploying or changing redirects. If a
response lacks normal WordPress headers and has an old `Last-Modified` value, remove the
conflicting physical file or disable the host plugin that generated it.
