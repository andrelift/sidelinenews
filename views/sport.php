<?php $route_for_pager = url("sport/$slug"); ?>
<header class="page-head" style="--c:<?= e(sport_color($slug)) ?>">
  <span class="wm"><?= sport_icon($slug) ?></span>
  <span class="kicker"><i></i>Section</span>
  <h1><?= e(sport_label($slug)) ?></h1>
  <p><?= $total ?> recent stor<?= $total === 1 ? 'y' : 'ies' ?>, newest first.</p>
</header>
<?php if (!$items): echo render('_empty'); else: ?>
<div class="grid grid-3">
  <?php foreach ($items as $it) echo card($it); ?>
</div>
<?= render('_pager', compact('page', 'pages', 'route_for_pager')) ?>
<?php endif; ?>
