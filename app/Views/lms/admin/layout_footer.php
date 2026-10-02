<?php
// app/Views/lms/admin/layout_footer.php
?>
</div> <!-- End lms-main -->

<!-- Bootstrap JS -->
<script src="<?= BASE_PATH ?>/public/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_PATH ?>/public/js/spa-router.js?v=<?= esc(file_exists(dirname(__DIR__, 4) . '/public/js/spa-router.js') ? filemtime(dirname(__DIR__, 4) . '/public/js/spa-router.js') : '1.0') ?>"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const sidebar = document.getElementById('lmsSidebar');
        const mainContent = document.querySelector('.lms-main');
        
        if (sidebar && mainContent) {
            const isMobile = window.innerWidth < 992;
            const isCollapsed = localStorage.getItem('lmsAdminSidebarCollapsed') === 'true';
            
            if (!isMobile && isCollapsed) {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('collapsed');
            }

            document.addEventListener('click', function(e) {
                const toggle = e.target.closest('#sidebarToggle');
                if (!toggle) return;
                e.preventDefault();
                if (window.innerWidth < 992) {
                    sidebar.classList.toggle('show');
                } else {
                    sidebar.classList.toggle('collapsed');
                    mainContent.classList.toggle('collapsed');
                    localStorage.setItem('lmsAdminSidebarCollapsed', sidebar.classList.contains('collapsed'));
                }
            });
        }
    });
</script>
</body>
</html>
