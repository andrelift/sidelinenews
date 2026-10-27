<header class="page-head" style="--c:var(--accent)"><span class="kicker"><i></i>About</span><h1>How <?= e(config('site.name')) ?> works</h1></header>
<div class="prose">
  <p><?= e(config('site.name')) ?> is a fully automatic sports news hub. It reads public RSS feeds from respected publishers, merges them into one timeline, removes duplicates and shows the freshest stories first. No editors, no database, no manual updates.</p>
  <h2>Where the stories come from</h2>
  <p>Current sources: <?= e(implode(', ', array_unique(array_column(config('feeds'), 'source')))) ?>. We only show headlines, short summaries and thumbnails supplied in those feeds. Every story links to the original article, and all rights remain with the publishers.</p>
  <h2>How often it updates</h2>
  <p>Feeds are refreshed about every <?= round((int) config('cache.ttl') / 60) ?> minutes. If a source is temporarily unavailable, its last known stories stay on the site until it comes back.</p>
</div>
