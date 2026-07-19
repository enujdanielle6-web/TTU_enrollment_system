<?php
// admin/components/footer.php
?>
  </div> <!-- Close admin-main -->
</div> <!-- Close admin-wrapper -->

<!-- Mobile Sidebar Overlay (optional, for off-canvas effect) -->
<div class="offcanvas-backdrop fade show d-none" id="sidebarBackdrop"></div>

<script>
  // Simple sidebar toggle for mobile
  document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('adminSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');

    if (sidebarToggle && sidebar) {
      sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('show');
        if (sidebar.classList.contains('show')) {
          backdrop.classList.remove('d-none');
        } else {
          backdrop.classList.add('d-none');
        }
      });
    }

    if (backdrop) {
      backdrop.addEventListener('click', function() {
        sidebar.classList.remove('show');
        backdrop.classList.add('d-none');
      });
    }
  });
</script>

<?php 
// Include the main footer which closes body and html
require_once __DIR__ . '/../../components/footer.php'; 
?>
