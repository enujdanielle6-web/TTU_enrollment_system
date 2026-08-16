</div> <!-- End lms-main -->

<!-- Bootstrap JS -->
<script src="/sia/public/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script src="/sia/public/js/spa-router.js?v=<?= filemtime(__DIR__ . '/../../../../public/js/spa-router.js') ?>"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const sidebar = document.getElementById('lmsSidebar');
        const mainContent = document.querySelector('.lms-main');
        const toggleBtn = document.getElementById('sidebarToggle');
        
        if (sidebar && mainContent) {
            // Check preference
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            
            if (isCollapsed) {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('collapsed');
            } else {
                // Auto collapse after 3 seconds if not already collapsed by preference
                setTimeout(() => {
                    if (!sidebar.classList.contains('collapsed')) {
                        sidebar.classList.add('collapsed');
                        mainContent.classList.add('collapsed');
                        localStorage.setItem('sidebarCollapsed', 'true');
                    }
                }, 3000);
            }

            // Hover to expand
            sidebar.addEventListener('mouseenter', function() {
                if (sidebar.classList.contains('collapsed')) {
                    sidebar.classList.remove('collapsed');
                    sidebar.dataset.hoverExpanded = 'true';
                }
            });

            sidebar.addEventListener('mouseleave', function() {
                if (sidebar.dataset.hoverExpanded === 'true') {
                    sidebar.classList.add('collapsed');
                    sidebar.dataset.hoverExpanded = 'false';
                }
            });

            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('collapsed');
                    mainContent.classList.toggle('collapsed');
                    
                    // Clear the hover flag so it stays open/closed properly
                    sidebar.dataset.hoverExpanded = 'false';
                    
                    // Save preference
                    localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
                });
            }
        }
    });
</script>
</body>
</html>

