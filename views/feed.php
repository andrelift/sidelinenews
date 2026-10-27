<?php echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n"; ?>
<rss version="2.0"><channel>
<title><?= e(config('site.name')) ?></title>
<link><?= e(abs_url('')) ?></link>
<description><?= e(config('site.tagline')) ?></description>
<language><?= e(config('site.lang', 'en')) ?></language>
<?php foreach ($items as $it): ?>
<item>
<title><?= e($it['title']) ?></title>
<link><?= e(abs_url('story/' . $it['id'])) ?></link>
<guid isPermaLink="false"><?= e($it['id']) ?></guid>
<pubDate><?= gmdate('D, d M Y H:i:s', $it['published']) ?> GMT</pubDate>
<category><?= e(sport_label($it['sport'])) ?></category>
<description><?= e($it['summary']) ?></description>
</item>
<?php endforeach; ?>
</channel></rss>
