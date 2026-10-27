<?php if ($pages > 1):
    $q = $query ?? [];
    $mk = fn($p) => $route_for_pager . (str_contains($route_for_pager, '?') ? '&' : '?') . http_build_query($q + ['page' => $p]);
?>
<nav class="pager" aria-label="Pagination">
  <?php if ($page > 1): ?><a href="<?= e($mk($page - 1)) ?>">← Newer</a><?php else: ?><span></span><?php endif; ?>
  <span>Page <?= $page ?> of <?= $pages ?></span>
  <?php if ($page < $pages): ?><a href="<?= e($mk($page + 1)) ?>">Older →</a><?php else: ?><span></span><?php endif; ?>
</nav>
<?php endif; ?>
