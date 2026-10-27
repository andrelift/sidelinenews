<?php $c = sport_color($it['sport']); ?>
<article class="story" style="--c:<?= e($c) ?>">
  <nav class="crumbs"><a href="<?= e(url('')) ?>">Home</a> / <a href="<?= e(url('sport/' . $it['sport'])) ?>"><?= e(sport_label($it['sport'])) ?></a></nav>
  <div class="meta"><span class="tag"><i style="background:<?= e($c) ?>"></i><?= e(sport_label($it['sport'])) ?></span> <span><?= e($it['source']) ?></span> <time datetime="<?= e(gmdate('c', $it['published'])) ?>"><?= e(date('j M Y, H:i', $it['published'])) ?> · <?= e(time_ago($it['published'])) ?></time></div>
  <h1><?= e($it['title']) ?></h1>
  <?php if (!empty($it['image'])): ?>
    <figure><img src="<?= e($it['image']) ?>" alt="" referrerpolicy="no-referrer"><figcaption>Image: <?= e($it['source']) ?></figcaption></figure>
  <?php endif; ?>
  <?php if ($it['summary']): ?><p class="dek"><?= e($it['summary']) ?></p><?php endif; ?>
  <div class="cta">
    <a class="btn btn-lg" href="<?= e($it['link']) ?>" target="_blank" rel="noopener noreferrer nofollow">Read the full story on <?= e($it['source']) ?> ↗</a>
    <p class="small">You'll leave <?= e(config('site.name')) ?> and go to the original publisher.</p>
  </div>
</article>
<?php if ($related): ?>
<section class="sport-sec">
  <div class="sec-head" style="--c:<?= e($c) ?>"><h2><span class="bar"></span>More <?= e(sport_label($it['sport'])) ?></h2></div>
  <div class="grid"><?php foreach ($related as $r) echo card($r, 'compact'); ?></div>
</section>
<?php endif; ?>
