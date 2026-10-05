/**
 * TTU LMS shell behaviour shared by the student, faculty and admin layouts.
 *
 * - Sidebar quick search: filters the sidebar links; Enter opens the first match;
 *   Ctrl+K / Cmd+K focuses it.
 * - Mobile sidebar: keeps a backdrop in sync with the sidebar's `.show` state,
 *   and closes the sidebar on backdrop click, Escape, or after a page change.
 *
 * Presentation only: it does not change any route or request.
 */
(function () {
    'use strict';

    function init() {
        var sidebar = document.getElementById('lmsSidebar');
        if (!sidebar) {
            return;
        }

        var isMobile = function () {
            return window.innerWidth < 992;
        };

        // ---- Mobile backdrop -------------------------------------------------
        var backdrop = document.getElementById('sidebarBackdrop');
        if (!backdrop) {
            backdrop = document.createElement('div');
            backdrop.id = 'sidebarBackdrop';
            backdrop.className = 'sidebar-backdrop d-none';
            sidebar.parentNode.insertBefore(backdrop, sidebar);
        }

        var syncBackdrop = function () {
            backdrop.classList.toggle('d-none', !sidebar.classList.contains('show'));
        };
        var closeSidebar = function () {
            sidebar.classList.remove('show');
            syncBackdrop();
        };

        new MutationObserver(syncBackdrop).observe(sidebar, { attributes: true, attributeFilter: ['class'] });
        backdrop.addEventListener('click', closeSidebar);
        // After a link in the drawer opens a new page, hide the drawer so the page is visible.
        document.addEventListener('spa:navigated', function () {
            if (isMobile()) {
                closeSidebar();
            }
        });

        // ---- Quick search ----------------------------------------------------
        var input = sidebar.querySelector('.lms-sidebar-search');
        var links = Array.prototype.slice.call(sidebar.querySelectorAll('.lms-nav-link'));
        var labels = Array.prototype.slice.call(sidebar.querySelectorAll('.lms-section-label, .sidebar-section-header'));

        var kbd = sidebar.querySelector('.lms-kbd-shortcut');
        if (kbd && !/Mac|iPhone|iPad/.test(navigator.platform || navigator.userAgent)) {
            kbd.textContent = 'Ctrl K';
        }

        var filterLinks = function () {
            var query = input.value.trim().toLowerCase();
            links.forEach(function (link) {
                var match = query === '' || link.textContent.toLowerCase().indexOf(query) !== -1;
                link.classList.toggle('d-none', !match);
            });
            // Hide a section label when none of the links under it match.
            labels.forEach(function (label) {
                var node = label.nextElementSibling;
                var anyVisible = false;
                while (node && !node.matches('.lms-section-label, .sidebar-section-header')) {
                    if (node.matches('.lms-nav-link') && !node.classList.contains('d-none')) {
                        anyVisible = true;
                        break;
                    }
                    node = node.nextElementSibling;
                }
                label.classList.toggle('d-none', query !== '' && !anyVisible);
            });
        };

        if (input) {
            input.addEventListener('input', filterLinks);
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    var first = links.filter(function (l) { return !l.classList.contains('d-none'); })[0];
                    if (first) {
                        e.preventDefault();
                        input.value = '';
                        filterLinks();
                        input.blur();
                        first.click();
                    }
                } else if (e.key === 'Escape') {
                    input.value = '';
                    filterLinks();
                    input.blur();
                }
            });
        }

        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K') && input) {
                e.preventDefault();
                if (isMobile()) {
                    sidebar.classList.add('show');
                } else if (sidebar.classList.contains('collapsed') || sidebar.classList.contains('minimized')) {
                    // The search box is hidden in the collapsed rail; expand first.
                    var toggle = document.getElementById('sidebarMinimize') || document.getElementById('sidebarToggle');
                    if (toggle) {
                        toggle.click();
                    }
                }
                input.focus();
                input.select();
                return;
            }
            if (e.key === 'Escape' && isMobile() && sidebar.classList.contains('show') && !document.querySelector('.modal.show')) {
                closeSidebar();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
