# Sideline — automatic sports news template (pure PHP)

A news site that fills itself. It reads public RSS feeds (BBC Sport, The Guardian, ESPN), merges and de-duplicates them,
and renders a fast, responsive site. **No database, no JavaScript, no Composer** — just PHP 8.0+ with `curl` (or `allow_url_fopen`), `simplexml` and `mbstring`.

## Run it

Locally:

    php -S localhost:8000 router.php

On Apache: upload the folder, make sure `mod_rewrite` is on and `cache/` is writable (`chmod 775 cache`). Done.

On nginx:

    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ ^/(cache|lib|views|cron)/ { deny all; }

No rewrites available? Set `'pretty_urls' => false` in `config.php`.

## How the content pooling works

1. A visitor opens a page. If the cache is older than `cache.ttl` (15 min), the site fetches all feeds **in parallel** (cURL multi), parses RSS/Atom, extracts title, summary, image, date.
2. Everything is saved as JSON in `cache/`. Only one request refreshes at a time (file lock); others get the previous cache instantly.
3. If a feed fails, its last good stories are kept.

For zero visitor-facing delay, set `'refresh_on_request' => false` and add a cron job:

    */15 * * * * php /path/to/sideline/cron/refresh.php

Or set `refresh_key` in `config.php` and ping `/refresh?key=YOUR_KEY` from any web cron service.

## Customise

- `config.php`: site name, tagline, sports (label + colour), feeds (any RSS/Atom URL tagged with a sport slug), page size.
- `assets/style.css`: all design tokens are at the top (`:root`); dark mode is automatic.
- `views/`: plain PHP templates (`home.php`, `sport.php`, `story.php`, ...).

Built-ins: story pages, sport sections, search, pagination, ticker, `/feed.xml`, `/sitemap.xml`, security headers (CSP with scripts disabled).

## Content & legal notes

The site shows only headlines, short summaries and thumbnails from the feeds and links to the original article. Check each publisher's RSS terms before going commercial (some restrict commercial reuse), and swap in feeds you're licensed to use. Add your own domain to `site.url` once live.
