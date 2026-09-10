</div> <!-- End lms-main -->

<!-- Bootstrap JS -->
<script src="/sia/public/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script src="/sia/public/js/spa-router.js?v=<?= esc(filemtime(__DIR__ . '/../../../../public/js/spa-router.js')) ?>"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const sidebar = document.getElementById('lmsSidebar');
        const mainContent = document.querySelector('.lms-main');
        const toggleBtn = document.getElementById('sidebarToggle');
        
        if (sidebar && mainContent) {
            // Check preference on desktop
            const isMobile = window.innerWidth < 992;
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            
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
                    localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
                }
            });

            // Sidebar Notification Dropup Toggle & Dismissal
            document.addEventListener('click', function(e) {
                const notifBtn = e.target.closest('#sidebarNotificationBtn');
                const closeBtn = e.target.closest('#closeNotificationPanel');
                const notifLink = e.target.closest('.lms-notification-item');
                const panel = document.getElementById('sidebarNotificationPanel');

                if (notifBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (panel) {
                        panel.classList.toggle('show');
                        notifBtn.setAttribute('aria-expanded', panel.classList.contains('show'));
                    }
                    return;
                }

                if (closeBtn || notifLink) {
                    if (panel) panel.classList.remove('show');
                    const btn = document.getElementById('sidebarNotificationBtn');
                    if (btn) btn.setAttribute('aria-expanded', 'false');
                    return;
                }

                if (panel && panel.classList.contains('show')) {
                    if (!panel.contains(e.target)) {
                        panel.classList.remove('show');
                        const btn = document.getElementById('sidebarNotificationBtn');
                        if (btn) btn.setAttribute('aria-expanded', 'false');
                    }
                }
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const panel = document.getElementById('sidebarNotificationPanel');
                    if (panel && panel.classList.contains('show')) {
                        panel.classList.remove('show');
                        const btn = document.getElementById('sidebarNotificationBtn');
                        if (btn) btn.setAttribute('aria-expanded', 'false');
                    }
                }
            });
        }
    });
</script>
</body>
</html>

