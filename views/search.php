<?php $route_for_pager = url('search'); $query = ['q' => $q]; ?>
<header class="page-head" style="--c:var(--accent)">
  <span class="kicker"><i></i>Search</span>
  <h1><?= $q === '' ? 'Search' : '“' . e($q) . '”' ?></h1>
  <p><?= $q === '' ? 'Type a team, player or league above.' : $total . ' result' . ($total === 1 ? '' : 's') ?></p>
</header>
<?php if ($q !== '' && !$items): ?>
  <div class="empty"><h2>Nothing found</h2><p>Try a shorter query or a different spelling.</p></div>
<?php elseif ($items): ?>
  <div class="grid grid-3"><?php foreach ($items as $it) echo card($it); ?></div>
  <?= render('_pager', compact('page', 'pages', 'route_for_pager', 'query')) ?>
<?php endif; ?>
