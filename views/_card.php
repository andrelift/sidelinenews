<?php
$c = sport_color($it['sport']);
$href = story_url($it);
$tag = '<span class="tag"><i style="background:' . e($c) . '"></i>' . e(sport_label($it['sport'])) . '</span>';
$thumb = function (string $cls) use ($it, $c) {
    $s = '<div class="thumb ' . $cls . '" style="--c:' . e($c) . '"><span class="ph">' . e(sport_label($it['sport'])) . '</span>';
    if (!empty($it['image'])) {
        $s .= '<img src="' . e($it['image']) . '" alt="" loading="lazy" referrerpolicy="no-referrer">';
    }
    return $s . '</div>';
};
?>
<?php if ($variant === 'lead'): ?>
<article class="lead" style="--c:<?= e($c) ?>">
  <?php if (!empty($it['image'])): ?><img src="<?= e($it['image']) ?>" alt="" referrerpolicy="no-referrer"><?php endif; ?>
  <div class="lead-body">
    <div class="meta"><?= $tag ?> <span><?= e($it['source']) ?></span> <time datetime="<?= e(gmdate('c', $it['published'])) ?>"><?= e(time_ago($it['published'])) ?></time></div>
    <h2><a class="stretch" href="<?= e($href) ?>"><?= e($it['title']) ?></a></h2>
    <?php if ($it['summary']): ?><p><?= e($it['summary']) ?></p><?php endif; ?>
  </div>
</article>
<?php elseif ($variant === 'row'): ?>
<article class="row">
  <?= $thumb('thumb-sm') ?>
  <div>
    <div class="meta"><?= $tag ?> <time datetime="<?= e(gmdate('c', $it['published'])) ?>"><?= e(time_ago($it['published'])) ?></time></div>
    <h3><a class="stretch" href="<?= e($href) ?>"><?= e($it['title']) ?></a></h3>
    <div class="src"><?= e($it['source']) ?></div>
  </div>
</article>
<?php else: ?>
<article class="card">
  <?= $thumb('thumb-md') ?>
  <div class="card-body">
    <div class="meta"><?= $tag ?> <time datetime="<?= e(gmdate('c', $it['published'])) ?>"><?= e(time_ago($it['published'])) ?></time></div>
    <h3><a class="stretch" href="<?= e($href) ?>"><?= e($it['title']) ?></a></h3>
    <?php if ($variant !== 'compact' && $it['summary']): ?><p><?= e($it['summary']) ?></p><?php endif; ?>
    <div class="src"><?= e($it['source']) ?></div>
  </div>
</article>
<?php endif; ?>
