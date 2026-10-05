/**
 * Student lesson preview window with a scroll tracker.
 *
 * Any element with class "js-lesson-open" and data-lesson-id opens the lesson in
 * #lessonPreviewModal (app/Views/lms/student/components/lesson_preview_modal.php).
 * How far the student scrolls is saved to /lms/student/material/{id}/progress, and
 * the lesson badges and the "Your Progress" card are updated from the response.
 *
 * spa-router.js re-runs this file after every page swap, so everything below is set
 * up once and finds the modal again on each open.
 */
(function () {
    if (window.__lessonViewerInit) {
        return;
    }
    window.__lessonViewerInit = true;

    var SAVE_EVERY_MS = 1500;
    var SAVE_STEP_PERCENT = 5;

    var state = null;

    function el(sel, root) {
        return (root || document).querySelector(sel);
    }

    function escapeHtml(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatSize(bytes) {
        if (!bytes) return '';
        if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
        return Math.max(1, Math.round(bytes / 1024)) + ' KB';
    }

    var KIND_LABELS = {
        pdf: 'PDF document',
        image: 'Image',
        media: 'Video / audio',
        office: 'Slides / document',
        text: 'Text document',
        other: 'File'
    };

    // ---- Opening ------------------------------------------------------------

    document.addEventListener('click', function (e) {
        var link = e.target.closest('.js-lesson-open');
        if (!link) return;
        var modalEl = document.getElementById('lessonPreviewModal');
        if (!modalEl || !window.bootstrap) return; // fall back to the plain download link
        e.preventDefault();
        openLesson(modalEl, link.getAttribute('data-lesson-id'));
    });

    function openLesson(modalEl, id) {
        closeCurrent(false);

        state = {
            id: id,
            modalEl: modalEl,
            base: modalEl.getAttribute('data-preview-base'),
            pdfjsBase: modalEl.getAttribute('data-pdfjs-base'),
            csrf: modalEl.getAttribute('data-csrf'),
            completeAt: parseFloat(modalEl.getAttribute('data-complete-at')) || 95,
            scrollEl: el('[data-lesson-scroll]', modalEl),
            contentEl: el('[data-lesson-content]', modalEl),
            maxPercent: 0,
            savedPercent: 0,
            completed: false,
            tracking: false,
            lastSaveAt: 0,
            saveTimer: null,
            pdfDoc: null,
            observer: null,
            media: null,
            closed: false,
            shown: null
        };

        // Measure only once the window is on screen: before that it has no size, and an
        // unsized lesson would look short enough to count as read.
        var current = state;
        current.shown = new Promise(function (resolve) {
            if (modalEl.classList.contains('show')) {
                resolve();
                return;
            }
            modalEl.addEventListener('shown.bs.modal', function onShown() {
                modalEl.removeEventListener('shown.bs.modal', onShown);
                resolve();
            });
        });

        el('#lessonPreviewTitle', modalEl).textContent = 'Loading lesson...';
        el('[data-lesson-meta]', modalEl).textContent = '';
        el('[data-lesson-download]', modalEl).setAttribute('href', '#');
        state.contentEl.innerHTML = '<div class="text-center text-muted py-5"><div class="spinner-border spinner-border-sm me-2"></div>Loading preview...</div>';
        state.scrollEl.scrollTop = 0;
        setReadBar(0);
        setStatus('Scroll to the end to mark this lesson as read.');

        bindModalEvents(modalEl);
        bootstrap.Modal.getOrCreateInstance(modalEl).show();

        fetch(current.base + encodeURIComponent(id) + '/preview', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                return current.shown.then(function () { return data; });
            })
            .then(function (data) {
                if (current !== state) return;
                if (!data || !data.success) {
                    showMessage('bi-exclamation-triangle', (data && data.error) || 'This lesson could not be opened.');
                    el('#lessonPreviewTitle', modalEl).textContent = 'Lesson';
                    return;
                }
                fillHeader(data);
                if (data.progress) {
                    current.maxPercent = current.savedPercent = data.progress.percent || 0;
                    current.completed = !!data.progress.completed;
                    setReadBar(current.completed ? 100 : current.maxPercent);
                    updateBadges(current.id, data.progress);
                }
                renderLesson(data);
            })
            .catch(function () {
                if (current !== state) return;
                el('#lessonPreviewTitle', modalEl).textContent = 'Lesson';
                showMessage('bi-wifi-off', 'The preview could not be loaded. Check your connection, or use Download.');
            });
    }

    function fillHeader(data) {
        var modalEl = state.modalEl;
        el('#lessonPreviewTitle', modalEl).textContent = data.title;
        var meta = [KIND_LABELS[data.kind] || 'File'];
        if (data.file_size) meta.push(formatSize(data.file_size));
        el('[data-lesson-meta]', modalEl).textContent = meta.join(' • ');
        el('[data-lesson-download]', modalEl).setAttribute('href', data.download_url);
    }

    function bindModalEvents(modalEl) {
        if (modalEl.__lessonViewerBound) return;
        modalEl.__lessonViewerBound = true;
        modalEl.addEventListener('hidden.bs.modal', function () {
            closeCurrent(true);
        });
        el('[data-lesson-scroll]', modalEl).addEventListener('scroll', function () {
            if (state && state.tracking) measure();
        }, { passive: true });
    }

    function closeCurrent(saveNow) {
        if (!state) return;
        var old = state;
        old.closed = true;
        if (saveNow) flushSave(old, true);
        if (old.saveTimer) clearTimeout(old.saveTimer);
        if (old.observer) old.observer.disconnect();
        if (old.media) old.media.pause();
        if (old.pdfDoc) old.pdfDoc.destroy();
        old.contentEl.innerHTML = '';
        state = null;
    }

    // ---- Rendering ----------------------------------------------------------

    function showMessage(icon, text, withDownload) {
        var html = '<div class="text-center text-muted py-5">' +
            '<i class="bi ' + icon + ' display-5 d-block mb-3"></i>' +
            '<p class="mb-3">' + escapeHtml(text) + '</p>';
        if (withDownload) {
            html += '<a class="btn btn-primary rounded-pill px-4" data-spa="false" href="' +
                escapeHtml(withDownload) + '"><i class="bi bi-download me-1"></i> Download</a>';
        }
        state.contentEl.innerHTML = html + '</div>';
    }

    function renderLesson(data) {
        if (!data.file_available) {
            showMessage('bi-file-earmark-x', 'The file for this lesson is missing on the server. Please tell your instructor.');
            setStatus('This lesson has no file to read yet.');
            return;
        }

        switch (data.kind) {
            case 'pdf':
                renderPdf(data);
                break;
            case 'image':
                renderImage(data);
                break;
            case 'media':
                renderMedia(data);
                break;
            case 'office':
            case 'text':
                renderSections(data);
                break;
            default:
                showMessage('bi-file-earmark-arrow-down', 'This file type cannot be previewed here. Download it to read it; downloading marks it as read.', data.download_url);
                setStatus('Downloading this file marks it as read.');
        }
    }

    function renderImage(data) {
        var img = document.createElement('img');
        img.className = 'lesson-preview-image rounded-3';
        img.alt = data.title;
        img.onload = startTracking;
        img.onerror = function () {
            showMessage('bi-image', 'The image could not be shown. Use Download instead.');
        };
        img.src = data.view_url;
        state.contentEl.innerHTML = '';
        state.contentEl.appendChild(img);
    }

    function renderMedia(data) {
        // A <video> element also plays audio files.
        var media = document.createElement('video');
        media.controls = true;
        media.preload = 'metadata';
        media.className = 'w-100 rounded-3 bg-dark';
        media.src = data.view_url;
        state.media = media;
        state.contentEl.innerHTML = '';
        state.contentEl.appendChild(media);
        setStatus('Play to the end to mark this lesson as read.');

        var current = state;
        media.addEventListener('timeupdate', function () {
            if (current !== state || !media.duration) return;
            track(media.currentTime / media.duration * 100);
        });
        media.addEventListener('ended', function () {
            if (current === state) track(100);
        });
    }

    function renderSections(data) {
        var sections = data.sections || [];
        if (!sections.length) {
            showMessage('bi-file-earmark-text', 'No text could be read from this file. Download it to view it; downloading marks it as read.', data.download_url);
            return;
        }
        var html = '<div class="alert alert-light border small py-2 mb-3"><i class="bi bi-info-circle me-1"></i>' +
            'This is a text-only preview. Download the file to see the full design.</div>';
        sections.forEach(function (section) {
            html += '<section class="lesson-text-section">';
            if (section.label) {
                html += '<h6 class="fw-bold text-primary mb-2">' + escapeHtml(section.label) + '</h6>';
            }
            (section.paragraphs || []).forEach(function (p) {
                html += '<p class="mb-2">' + escapeHtml(p) + '</p>';
            });
            html += '</section>';
        });
        state.contentEl.innerHTML = html;
        startTracking();
    }

    function loadPdfJs(base) {
        if (window.pdfjsLib) return Promise.resolve(window.pdfjsLib);
        if (window.__lessonPdfJsLoading) return window.__lessonPdfJsLoading;
        window.__lessonPdfJsLoading = new Promise(function (resolve, reject) {
            var s = document.createElement('script');
            s.src = base + 'pdf.min.js';
            s.onload = function () {
                if (!window.pdfjsLib) {
                    reject(new Error('pdf.js missing'));
                    return;
                }
                window.pdfjsLib.GlobalWorkerOptions.workerSrc = base + 'pdf.worker.min.js';
                resolve(window.pdfjsLib);
            };
            s.onerror = function () {
                window.__lessonPdfJsLoading = null;
                reject(new Error('pdf.js failed to load'));
            };
            document.head.appendChild(s);
        });
        return window.__lessonPdfJsLoading;
    }

    function renderPdf(data) {
        var current = state;
        loadPdfJs(current.pdfjsBase)
            .then(function (pdfjsLib) {
                // isEvalSupported:false closes the font-eval hole (CVE-2024-4367).
                return pdfjsLib.getDocument({ url: data.view_url, isEvalSupported: false, withCredentials: true }).promise;
            })
            .then(function (pdf) {
                if (current !== state) {
                    pdf.destroy();
                    return;
                }
                current.pdfDoc = pdf;
                return layoutPdfPages(current, pdf);
            })
            .then(function () {
                if (current === state && current.pdfDoc) startTracking();
            })
            .catch(function () {
                if (current !== state) return;
                showMessage('bi-file-earmark-pdf', 'This PDF could not be shown here. Download it to read it.', data.download_url);
            });
    }

    function layoutPdfPages(current, pdf) {
        current.contentEl.innerHTML = '';
        var width = Math.max(240, current.contentEl.clientWidth || current.scrollEl.clientWidth - 32);
        var pages = [];
        for (var i = 1; i <= pdf.numPages; i++) pages.push(i);

        // Size every page first so the scroll height is right before anything is drawn.
        return Promise.all(pages.map(function (n) { return pdf.getPage(n); })).then(function (loaded) {
            if (current !== state) return;
            current.observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        current.observer.unobserve(entry.target);
                        drawPdfPage(current, entry.target);
                    }
                });
            }, { root: current.scrollEl, rootMargin: '400px 0px' });

            loaded.forEach(function (page) {
                var base = page.getViewport({ scale: 1 });
                var scale = width / base.width;
                var viewport = page.getViewport({ scale: scale });
                var holder = document.createElement('div');
                holder.className = 'lesson-pdf-page';
                holder.style.width = Math.floor(viewport.width) + 'px';
                holder.style.height = Math.floor(viewport.height) + 'px';
                holder.setAttribute('aria-label', 'Page ' + page.pageNumber);
                holder.__page = page;
                holder.__scale = scale;
                current.contentEl.appendChild(holder);
                current.observer.observe(holder);
            });
        });
    }

    function drawPdfPage(current, holder) {
        if (current !== state) return;
        var page = holder.__page;
        var ratio = window.devicePixelRatio || 1;
        var viewport = page.getViewport({ scale: holder.__scale * ratio });
        var canvas = document.createElement('canvas');
        canvas.width = Math.floor(viewport.width);
        canvas.height = Math.floor(viewport.height);
        holder.appendChild(canvas);
        page.render({ canvasContext: canvas.getContext('2d'), viewport: viewport }).promise.catch(function () {});
    }

    // ---- Scroll tracking -------------------------------------------------------

    function startTracking() {
        if (!state) return;
        state.tracking = true;
        // Short lessons that fit without scrolling count as read straight away.
        requestAnimationFrame(measure);
    }

    function measure() {
        if (!state) return;
        var s = state.scrollEl;
        var percent = s.scrollHeight <= s.clientHeight + 2
            ? 100
            : (s.scrollTop + s.clientHeight) / s.scrollHeight * 100;
        track(percent);
    }

    function track(percent) {
        var current = state;
        if (!current || current.closed) return;
        percent = Math.max(0, Math.min(100, percent));
        if (percent >= current.completeAt - 0.01) percent = 100;
        if (percent <= current.maxPercent) return;

        current.maxPercent = percent;
        if (!current.completed) {
            setReadBar(percent);
            setStatus(percent >= 100 ? 'Saving...' : Math.floor(percent) + '% read. Keep scrolling to the end.');
        }

        var big = percent - current.savedPercent >= SAVE_STEP_PERCENT || percent >= 100;
        if (!big) return;
        var wait = SAVE_EVERY_MS - (Date.now() - current.lastSaveAt);
        if (percent >= 100 || wait <= 0) {
            flushSave(current, false);
        } else if (!current.saveTimer) {
            current.saveTimer = setTimeout(function () {
                current.saveTimer = null;
                flushSave(current, false);
            }, wait);
        }
    }

    function flushSave(current, closing) {
        if (current.saveTimer) {
            clearTimeout(current.saveTimer);
            current.saveTimer = null;
        }
        if (current.maxPercent <= current.savedPercent || (current.completed && closing)) return;

        var sent = current.maxPercent;
        current.savedPercent = sent;
        current.lastSaveAt = Date.now();

        var body = new URLSearchParams();
        body.append('percent', sent.toFixed(2));
        body.append('csrf_token', current.csrf);

        fetch(current.base + encodeURIComponent(current.id) + '/progress', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': current.csrf
            },
            body: body,
            credentials: 'same-origin',
            keepalive: !!closing
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data || !data.success) throw new Error('save failed');
                updateBadges(current.id, data.lesson);
                updateCourseCard(data.course);
                if (current === state && data.lesson.completed) {
                    current.completed = true;
                    setReadBar(100);
                    setStatus('Lesson marked as read.', true);
                }
            })
            .catch(function () {
                // Let the next scroll try again.
                if (current.savedPercent === sent) current.savedPercent = 0;
                if (current === state) setStatus('Progress could not be saved. It will retry as you scroll.');
            });
    }

    // ---- Page updates ---------------------------------------------------------

    function setReadBar(percent) {
        if (!state) return;
        var bar = el('[data-lesson-read-bar]', state.modalEl);
        if (bar) bar.style.width = Math.floor(percent) + '%';
    }

    function setStatus(text, done) {
        if (!state) return;
        var status = el('[data-lesson-status]', state.modalEl);
        if (!status) return;
        status.className = done ? 'text-success fw-semibold' : 'text-muted';
        status.innerHTML = done ? '<i class="bi bi-check-circle-fill me-1"></i>' + escapeHtml(text) : escapeHtml(text);
    }

    function updateBadges(id, lesson) {
        if (!lesson) return;
        var percent = Math.floor(lesson.percent || 0);
        document.querySelectorAll('[data-lesson-badge="' + String(id).replace(/"/g, '') + '"]').forEach(function (badge) {
            badge.classList.remove('bg-success', 'bg-primary', 'bg-opacity-10', 'text-primary', 'bg-light', 'text-muted', 'border');
            if (lesson.completed) {
                badge.classList.add('bg-success');
                badge.innerHTML = '<i class="bi bi-check-lg"></i> Read';
            } else if (percent > 0) {
                badge.classList.add('bg-primary', 'bg-opacity-10', 'text-primary');
                badge.textContent = percent + '% read';
            } else {
                badge.classList.add('bg-light', 'text-muted', 'border');
                badge.textContent = 'Not started';
            }
        });
    }

    function updateCourseCard(course) {
        var card = document.getElementById('courseProgressCard');
        if (!card || !course) return;
        var set = function (key, value) {
            card.querySelectorAll('[data-progress="' + key + '"]').forEach(function (node) {
                node.textContent = value;
            });
        };
        set('percent', course.percent + '%');
        set('done', course.done);
        set('total', course.total);
        if (course.lessons) {
            set('lessons-done', course.lessons.done);
            set('lessons-total', course.lessons.total);
        }
        card.querySelectorAll('[data-progress="bar"]').forEach(function (bar) {
            bar.style.width = course.percent + '%';
        });
    }
})();
