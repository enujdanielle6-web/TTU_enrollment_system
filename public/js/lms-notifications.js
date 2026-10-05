/**
 * LMS bell notifications: clicking one opens #lmsNotificationModal with the full
 * text and marks it as read; "Mark all as read" clears the lot. The red bell dot,
 * the "N New" count and the unread highlight are updated in place.
 * Markup: app/Views/lms/components/notification_panel.php
 */
(function () {
    if (window.__lmsNotificationsInit) {
        return;
    }
    window.__lmsNotificationsInit = true;

    function panel() {
        return document.getElementById('sidebarNotificationPanel');
    }

    function setText(root, sel, text) {
        var node = root.querySelector(sel);
        if (node) node.textContent = text || '';
    }

    function toggleRow(root, sel, show) {
        var node = root.querySelector(sel);
        if (node) node.classList.toggle('d-none', !show);
    }

    function showUnread(count) {
        var p = panel();
        if (p) {
            var badge = p.querySelector('[data-notif-count]');
            if (badge) {
                badge.textContent = count + ' New';
                badge.classList.toggle('d-none', count <= 0);
            }
            var readAll = p.querySelector('[data-notif-read-all]');
            if (readAll) readAll.classList.toggle('invisible', count <= 0);
        }
        document.querySelectorAll('[data-notif-dot]').forEach(function (dot) {
            dot.classList.toggle('d-none', count <= 0);
        });
    }

    function countUnread() {
        var p = panel();
        return p ? p.querySelectorAll('.lms-notification-item.is-unread').length : 0;
    }

    function post(url, data) {
        var p = panel();
        var body = new URLSearchParams(data || {});
        var csrf = p ? p.getAttribute('data-csrf') : '';
        body.append('csrf_token', csrf);
        return fetch(url, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf },
            body: body,
            credentials: 'same-origin'
        }).then(function (res) { return res.json(); });
    }

    function markRead(item, data) {
        if (!data.tracked || !item.classList.contains('is-unread')) return;
        // Update the page straight away; the server only stores it.
        item.classList.remove('is-unread');
        showUnread(countUnread());
        var p = panel();
        post(p.getAttribute('data-read-url'), { type: data.type, id: data.id })
            .then(function (res) {
                if (res && res.success && typeof res.unread === 'number') showUnread(res.unread);
            })
            .catch(function () {});
    }

    function openModal(data) {
        var modalEl = document.getElementById('lmsNotificationModal');
        if (!modalEl || !window.bootstrap) return false;

        setText(modalEl, '#lmsNotificationModalTitle', data.title);
        setText(modalEl, '[data-notif-modal-course]', data.course);
        setText(modalEl, '[data-notif-modal-type]', data.typeLabel);
        setText(modalEl, '[data-notif-modal-person]', data.person);
        setText(modalEl, '[data-notif-modal-when]', data.when);
        setText(modalEl, '[data-notif-modal-due]', data.due);
        setText(modalEl, '[data-notif-modal-status]', data.status);
        setText(modalEl, '[data-notif-modal-file]', data.file);
        toggleRow(modalEl, '[data-notif-modal-due-row]', !!data.due);
        toggleRow(modalEl, '[data-notif-modal-status-row]', !!data.status);
        toggleRow(modalEl, '[data-notif-modal-file-row]', !!data.file);

        var icon = modalEl.querySelector('[data-notif-modal-icon]');
        if (icon) icon.className = 'bi ' + (data.icon || 'bi-bell-fill');

        var body = modalEl.querySelector('[data-notif-modal-body]');
        if (body) {
            body.textContent = data.body || data.emptyBody || '';
            body.classList.toggle('text-muted', !data.body);
        }

        var link = modalEl.querySelector('[data-notif-modal-link]');
        if (link) {
            link.textContent = data.action || 'Open';
            link.setAttribute('href', data.url || '#');
            link.classList.toggle('d-none', !data.url);
        }

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
        return true;
    }

    document.addEventListener('click', function (e) {
        var item = e.target.closest('.lms-notification-item[data-notif]');
        if (item) {
            var data;
            try {
                data = JSON.parse(item.getAttribute('data-notif'));
            } catch (err) {
                return; // fall back to following the link
            }
            if (!openModal(data)) return;
            e.preventDefault();
            markRead(item, data);
            return;
        }

        var readAll = e.target.closest('[data-notif-read-all]');
        if (readAll) {
            e.preventDefault();
            e.stopPropagation();
            var p = panel();
            if (!p) return;
            p.querySelectorAll('.lms-notification-item.is-unread').forEach(function (n) {
                n.classList.remove('is-unread');
            });
            showUnread(0);
            post(p.getAttribute('data-read-all-url'))
                .then(function (res) {
                    if (res && res.success && typeof res.unread === 'number') showUnread(res.unread);
                })
                .catch(function () {});
        }
    });

    // The button inside the window leaves through the SPA router like any other link.
    document.addEventListener('click', function (e) {
        var link = e.target.closest('[data-notif-modal-link]');
        if (!link) return;
        var modalEl = document.getElementById('lmsNotificationModal');
        if (modalEl && window.bootstrap) bootstrap.Modal.getOrCreateInstance(modalEl).hide();
    });
})();
