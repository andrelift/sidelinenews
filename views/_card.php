<?php
$c     = sport_color($it['sport']);
$href  = story_url($it);
$has   = !empty($it['image']);
$tag   = '<span class="tag"><i style="background:' . e($c) . '"></i>' . e(sport_label($it['sport'])) . '</span>';
$time  = '<time datetime="' . e(gmdate('c', $it['published'])) . '">' . e(time_ago($it['published'])) . '</time>';
$wm    = '<span class="wm">' . sport_icon($it['sport']) . '</span>';
$thumb = fn(string $cls) => '<div class="thumb ' . $cls . '"><img src="' . e($it['image']) . '" alt="" loading="lazy" referrerpolicy="no-referrer"></div>';
$st    = ' style="--c:' . e($c) . '"';
?>
<?php if ($variant === 'lead'): ?>
<article class="lead <?= $has ? '' : 'lead-text' ?>"<?= $st ?>>
  <?php if ($has): ?><img src="<?= e($it['image']) ?>" alt="" referrerpolicy="no-referrer"><?php else: ?><?= $wm ?><?php endif; ?>
  <div class="lead-body">
    <div class="meta"><?= $tag ?> <span><?= e($it['source']) ?></span> <?= $time ?></div>
    <h2><a class="stretch" href="<?= e($href) ?>"><?= e($it['title']) ?></a></h2>
    <?php if ($it['summary']): ?><p><?= e($it['summary']) ?></p><?php endif; ?>
  </div>
</article>

<?php elseif ($variant === 'row'): ?>
<article class="row"<?= $st ?>>
  <?= $has ? $thumb('thumb-sm') : '<div class="chip">' . sport_icon($it['sport']) . '</div>' ?>
  <div class="row-body">
    <div class="meta"><?= $tag ?> <?= $time ?></div>
    <h3><a class="stretch" href="<?= e($href) ?>"><?= e($it['title']) ?></a></h3>
    <div class="src"><?= e($it['source']) ?></div>
  </div>
</article>

<?php elseif ($variant === 'feature'): ?>
<article class="feature <?= $has ? '' : 'feature-text' ?>"<?= $st ?>>
  <?= $has ? $thumb('thumb-lg') : $wm ?>
  <div class="card-body">
    <div class="meta"><?= $tag ?> <span><?= e($it['source']) ?></span> <?= $time ?></div>
    <h3><a class="stretch" href="<?= e($href) ?>"><?= e($it['title']) ?></a></h3>
    <?php if ($it['summary']): ?><p><?= e($it['summary']) ?></p><?php endif; ?>
  </div>
</article>

<?php else: /* card | compact */ ?>
<article class="card <?= $has ? '' : 'card-text' ?>"<?= $st ?>>
  <?= $has ? $thumb('thumb-md') : $wm ?>
  <div class="card-body">
    <div class="meta"><?= $tag ?> <?= $time ?></div>
    <h3><a class="stretch" href="<?= e($href) ?>"><?= e($it['title']) ?></a></h3>
    <?php if ($it['summary'] && ($variant !== 'compact' || !$has)): ?><p><?= e($it['summary']) ?></p><?php endif; ?>
    <div class="src"><?= e($it['source']) ?></div>
  </div>
</article>
<?php endif; ?>
