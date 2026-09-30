<?php
// app/Views/components/pagination.php - Universal Table Pagination Partial
$page = isset($page) ? max(1, (int)$page) : 1;
$totalPages = isset($totalPages) ? max(1, (int)$totalPages) : (isset($total_pages) ? max(1, (int)$total_pages) : 1);
$totalCount = isset($totalCount) ? (int)$totalCount : (isset($total_items) ? (int)$total_items : null);
$limit = isset($limit) ? max(1, (int)$limit) : 10;

// Helper to preserve active query parameters while updating page
$queryParams = $_GET ?? [];
$buildPageUrl = function($targetPage) use ($queryParams) {
    $params = $queryParams;
    $params['page'] = $targetPage;
    return '?' . http_build_query($params);
};

// Render footer if there is count data or multiple pages
if (($totalCount !== null && $totalCount > 0) || $totalPages > 1):
    $startEntry = min(($page - 1) * $limit + 1, $totalCount ?? ($page * $limit));
    $endEntry = min($page * $limit, $totalCount ?? ($page * $limit));
?>
<div class="border-top py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2 pagination-container">
  <?php if ($totalCount !== null): ?>
    <div class="small text-muted">
      Showing <span class="fw-semibold text-dark"><?= esc($startEntry) ?></span> to <span class="fw-semibold text-dark"><?= esc($endEntry) ?></span> of <span class="fw-semibold text-dark"><?= esc($totalCount) ?></span> entries
    </div>
  <?php else: ?>
    <div></div>
  <?php endif; ?>

  <?php if ($totalPages > 1): ?>
  <nav aria-label="Table Pagination">
    <ul class="pagination pagination-sm mb-0 align-items-center gap-1">
      <!-- Previous Page -->
      <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
        <a class="page-link rounded-pill px-3 fw-medium" href="<?= esc($buildPageUrl(max(1, $page - 1))) ?>" aria-label="Previous Page">
          <i class="bi bi-chevron-left me-1"></i> Prev
        </a>
      </li>

      <?php
      // Windowing calculations
      $range = 2; // numbers before and after current
      $start = max(1, $page - $range);
      $end = min($totalPages, $page + $range);

      if ($start > 1) {
          echo '<li class="page-item"><a class="page-link rounded-circle text-center p-0 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" href="' . esc($buildPageUrl(1)) . '">1</a></li>';
          if ($start > 2) {
              echo '<li class="page-item disabled"><span class="page-link border-0 text-muted">...</span></li>';
          }
      }

      for ($i = $start; $i <= $end; $i++) {
          $isActive = ($i === $page);
          echo '<li class="page-item ' . ($isActive ? 'active' : '') . '">';
          echo '<a class="page-link rounded-circle text-center p-0 d-flex align-items-center justify-content-center ' . ($isActive ? 'fw-bold' : '') . '" style="width: 32px; height: 32px;" href="' . esc($buildPageUrl($i)) . '"' . ($isActive ? ' aria-current="page"' : '') . '>' . esc($i) . '</a>';
          echo '</li>';
      }

      if ($end < $totalPages) {
          if ($end < $totalPages - 1) {
              echo '<li class="page-item disabled"><span class="page-link border-0 text-muted">...</span></li>';
          }
          echo '<li class="page-item"><a class="page-link rounded-circle text-center p-0 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" href="' . esc($buildPageUrl($totalPages)) . '">' . esc($totalPages) . '</a></li>';
      }
      ?>

      <!-- Next Page -->
      <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
        <a class="page-link rounded-pill px-3 fw-medium" href="<?= esc($buildPageUrl(min($totalPages, $page + 1))) ?>" aria-label="Next Page">
          Next <i class="bi bi-chevron-right ms-1"></i>
        </a>
      </li>
    </ul>
  </nav>
  <?php endif; ?>
</div>
<?php endif; ?>
