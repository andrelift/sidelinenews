<?php echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n"; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<url><loc><?= e(abs_url('')) ?></loc></url>
<?php foreach (array_keys(sports()) as $s): ?><url><loc><?= e(abs_url("sport/$s")) ?></loc></url>
<?php endforeach; foreach ($items as $it): ?><url><loc><?= e(abs_url('story/' . $it['id'])) ?></loc><lastmod><?= gmdate('c', $it['published']) ?></lastmod></url>
<?php endforeach; ?>
</urlset>
