  </div> <!-- End lms-main -->
</div> <!-- End lms-wrapper -->

<!-- Bootstrap JS -->
<script src="<?= BASE_PATH ?>/public/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_PATH ?>/public/js/spa-router.js?v=<?= esc(filemtime(__DIR__ . '/../../../../public/js/spa-router.js')) ?>"></script>
<script src="<?= BASE_PATH ?>/public/js/lms-shell.js?v=<?= esc(filemtime(__DIR__ . '/../../../../public/js/lms-shell.js')) ?>"></script>
<script src="<?= BASE_PATH ?>/public/js/lms-notifications.js?v=<?= esc(filemtime(__DIR__ . '/../../../../public/js/lms-notifications.js')) ?>"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const sidebar = document.getElementById('lmsSidebar');
        const mainContent = document.getElementById('spa-main');
        const minimizeBtn = document.getElementById('sidebarMinimize');
        const mobileToggleBtn = document.getElementById('sidebarToggle');
        const sidebarCloseBtn = document.getElementById('sidebarClose');
        const sidebarBackdrop = document.getElementById('sidebarBackdrop');
        
        if (sidebar && mainContent) {
            // Check preference on desktop
            const isMobile = window.innerWidth < 992;
            const isMinimized = localStorage.getItem('sidebarMinimized') === 'true' || localStorage.getItem('sidebarCollapsed') === 'true';
            
            if (!isMobile && isMinimized) {
                sidebar.classList.add('minimized', 'collapsed');
                mainContent.classList.add('minimized', 'collapsed');
            }

            // Desktop Floating Minimize Toggle. Listen on the document: the SPA router
            // replaces the sidebar's contents (including this button) on every page change.
            if (minimizeBtn) {
                document.addEventListener('click', function(e) {
                    if (!e.target.closest('#sidebarMinimize')) return;
                    e.preventDefault();
                    sidebar.classList.toggle('minimized');
                    sidebar.classList.toggle('collapsed');
                    mainContent.classList.toggle('minimized');
                    mainContent.classList.toggle('collapsed');
                    const hasMinimized = sidebar.classList.contains('minimized');
                    localStorage.setItem('sidebarMinimized', hasMinimized);
                    localStorage.setItem('sidebarCollapsed', hasMinimized);
                });
            }

            // Mobile Hamburger Toggle
            if (mobileToggleBtn) {
                document.addEventListener('click', function(e) {
                    if (!e.target.closest('#sidebarToggle')) return;
                    e.preventDefault();
                    sidebar.classList.toggle('show');
                    if (sidebarBackdrop) {
                        sidebarBackdrop.classList.toggle('d-none', !sidebar.classList.contains('show'));
                    }
                });
            }

            // Mobile Close Button
            if (sidebarCloseBtn) {
                sidebarCloseBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    sidebar.classList.remove('show');
                    if (sidebarBackdrop) {
                        sidebarBackdrop.classList.add('d-none');
                    }
                });
            }

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

