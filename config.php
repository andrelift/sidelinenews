<?php
/**
 * SIDELINE — configuration.
 * Edit this file to rename the site, add sports, or add/remove RSS feeds.
 */
return [
    'site' => [
        'name'     => 'Sideline',
        'tagline'  => 'Every score, every story. Updated automatically.',
        'url'      => '',          // e.g. 'https://example.com' (leave empty to auto-detect)
        'timezone' => 'UTC',
        'lang'     => 'en',
    ],

    'cache' => [
        'dir'                => __DIR__ . '/cache',
        'ttl'                => 900,   // seconds between feed refreshes (15 min)
        'max_age_days'       => 10,    // ignore stories older than this
        'max_items'          => 600,   // total stories kept
        'refresh_on_request' => true,  // set false if you use cron/refresh.php
    ],

    // Set a secret to enable /refresh?key=SECRET (handy for web cron services). Empty = disabled.
    'refresh_key' => '',

    // true  => /sport/football   (needs .htaccess / nginx rewrite, or php -S)
    // false => /index.php?route=sport/football (works anywhere)
    'pretty_urls' => true,

    'per_page' => 18,

    // slug => [label, accent colour]
    'sports' => [
        'football'   => ['Football',     '#22c55e'],
        'basketball' => ['Basketball',   '#f97316'],
        'nfl'        => ['NFL',          '#3b82f6'],
        'baseball'   => ['Baseball',     '#ef4444'],
        'hockey'     => ['Hockey',       '#06b6d4'],
        'tennis'     => ['Tennis',       '#eab308'],
        'f1'         => ['Formula 1',    '#e11d48'],
        'cricket'    => ['Cricket',      '#14b8a6'],
        'rugby'      => ['Rugby',        '#a855f7'],
        'golf'       => ['Golf',         '#84cc16'],
        'boxing'     => ['Boxing',       '#ec4899'],
    ],

    // Public RSS feeds. 'sport' must match a slug above.
    'feeds' => [
        ['sport' => 'football',   'source' => 'BBC Sport',    'url' => 'https://feeds.bbci.co.uk/sport/football/rss.xml'],
        ['sport' => 'football',   'source' => 'The Guardian', 'url' => 'https://www.theguardian.com/football/rss'],
        ['sport' => 'football',   'source' => 'ESPN',         'url' => 'https://www.espn.com/espn/rss/soccer/news'],
        ['sport' => 'basketball', 'source' => 'ESPN',         'url' => 'https://www.espn.com/espn/rss/nba/news'],
        ['sport' => 'basketball', 'source' => 'The Guardian', 'url' => 'https://www.theguardian.com/sport/nba/rss'],
        ['sport' => 'nfl',        'source' => 'ESPN',         'url' => 'https://www.espn.com/espn/rss/nfl/news'],
        ['sport' => 'nfl',        'source' => 'The Guardian', 'url' => 'https://www.theguardian.com/sport/nfl/rss'],
        ['sport' => 'baseball',   'source' => 'ESPN',         'url' => 'https://www.espn.com/espn/rss/mlb/news'],
        ['sport' => 'hockey',     'source' => 'ESPN',         'url' => 'https://www.espn.com/espn/rss/nhl/news'],
        ['sport' => 'tennis',     'source' => 'BBC Sport',    'url' => 'https://feeds.bbci.co.uk/sport/tennis/rss.xml'],
        ['sport' => 'tennis',     'source' => 'The Guardian', 'url' => 'https://www.theguardian.com/sport/tennis/rss'],
        ['sport' => 'f1',         'source' => 'BBC Sport',    'url' => 'https://feeds.bbci.co.uk/sport/formula1/rss.xml'],
        ['sport' => 'f1',         'source' => 'The Guardian', 'url' => 'https://www.theguardian.com/sport/formulaone/rss'],
        ['sport' => 'cricket',    'source' => 'BBC Sport',    'url' => 'https://feeds.bbci.co.uk/sport/cricket/rss.xml'],
        ['sport' => 'cricket',    'source' => 'The Guardian', 'url' => 'https://www.theguardian.com/sport/cricket/rss'],
        ['sport' => 'rugby',      'source' => 'BBC Sport',    'url' => 'https://feeds.bbci.co.uk/sport/rugby-union/rss.xml'],
        ['sport' => 'rugby',      'source' => 'The Guardian', 'url' => 'https://www.theguardian.com/sport/rugby-union/rss'],
        ['sport' => 'golf',       'source' => 'BBC Sport',    'url' => 'https://feeds.bbci.co.uk/sport/golf/rss.xml'],
        ['sport' => 'golf',       'source' => 'The Guardian', 'url' => 'https://www.theguardian.com/sport/golf/rss'],
        ['sport' => 'boxing',     'source' => 'BBC Sport',    'url' => 'https://feeds.bbci.co.uk/sport/boxing/rss.xml'],
    ],
];
