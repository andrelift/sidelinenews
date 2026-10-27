<?php if (!$hero): echo render('_empty'); return; endif;

// Sport "board": stories per sport in the last 24h (fallback: 3 days)
$board = function (int $secs) use ($all) {
    $n = [];
    foreach ($all as $i) { if ($i['published'] >= time() - $secs) { $n[$i['sport']] = ($n[$i['sport']] ?? 0) + 1; } }
    arsort($n);
    return $n;
};
$counts = $board(86400);
$span = 'last 24 hours';
if (array_sum($counts) < 15) { $counts = $board(259200); $span = 'last 3 days'; }
$max = max($counts ?: [1]);
$heroIds = array_column($hero, 'id');
?>
<section class="hero">
  <?= card($hero[0], 'lead') ?>
  <div class="hero-side">
    <?php foreach (array_slice($hero, 1) as $it) echo card($it, 'row'); ?>
  </div>
</section>

<div class="cols">
  <section>
    <div class="sec-head"><h2><span class="bar" style="background:var(--accent)"></span>Latest</h2></div>
    <div class="list">
      <?php foreach ($latest as $it) echo card($it, 'row'); ?>
    </div>
  </section>

  <aside class="side">
    <div class="panel">
      <h3>Today's board</h3>
      <p class="small">Stories by sport, <?= e($span) ?></p>
      <ul class="board">
        <?php foreach ($counts as $slug => $n): ?>
          <li>
            <a href="<?= e(url("sport/$slug")) ?>">
              <span><?= e(sport_label($slug)) ?></span><b><?= $n ?></b>
              <span class="meter" style="--c:<?= e(sport_color($slug)) ?>;--w:<?= round($n / $max * 100) ?>%"><i></i></span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div class="panel panel-accent">
      <h3>Never miss a story</h3>
      <p>Subscribe to our RSS feed and get every headline in your reader.</p>
      <a class="btn" href="<?= e(url('feed.xml')) ?>">RSS feed</a>
    </div>
  </aside>
</div>

<?php foreach (sports() as $slug => [$label, $color]):
    $list = array_slice(array_values(array_filter($all, fn($i) => $i['sport'] === $slug && !in_array($i['id'], $heroIds, true))), 0, 4);
    if (count($list) < 2) continue; ?>
  <section class="sport-sec">
    <div class="sec-head" style="--c:<?= e($color) ?>">
      <h2><span class="bar"></span><?= e($label) ?></h2>
      <a href="<?= e(url("sport/$slug")) ?>">All <?= e($label) ?> →</a>
    </div>
    <div class="grid">
      <?php foreach ($list as $it) echo card($it, 'compact'); ?>
    </div>
  </section>
<?php endforeach; ?>
