/**
 * TTU Hybrid SPA Navigation Router
 * Converts standard MPA links into instantaneous AJAX fetches.
 */

document.addEventListener('DOMContentLoaded', () => {
    
    // Global click interceptor
    document.addEventListener('click', async (e) => {
        const link = e.target.closest('a');
        
        // Exclusions
        if (!link) return;
        if (!link.href) return;
        if (link.hasAttribute('download')) return;
        if (link.target === '_blank') return;
        if (link.href.includes('#') && link.href.split('#')[0] === window.location.href.split('#')[0]) return;
        if (link.dataset.spa === 'false') return;
        if (link.href.includes('logout.php')) return;
        
        // Must be same origin
        const url = new URL(link.href);
        if (url.origin !== window.location.origin) return;

        // Ensure we actually have a target container on this page
        const mainContainer = document.getElementById('spa-main');
        if (!mainContainer) return;

        e.preventDefault();
        
        navigateTo(url.href);
    });

    // Handle Browser Back/Forward
    window.addEventListener('popstate', (e) => {
        navigateTo(window.location.href, false);
    });
});

// SPA Progress Bar Helpers
function getProgressBar() {
    let bar = document.getElementById('spa-progress-bar');
    if (!bar) {
        bar = document.createElement('div');
        bar.id = 'spa-progress-bar';
        document.body.appendChild(bar);
    }
    return bar;
}

function startProgressBar() {
    const bar = getProgressBar();
    bar.classList.add('active');
    bar.style.width = '0%';
    bar.style.opacity = '1';
    requestAnimationFrame(() => {
        bar.style.width = '70%';
    });
}

function completeProgressBar() {
    const bar = getProgressBar();
    bar.style.width = '100%';
    setTimeout(() => {
        bar.style.opacity = '0';
        setTimeout(() => {
            bar.classList.remove('active');
            bar.style.width = '0%';
        }, 250);
    }, 120);
}

async function navigateTo(url, pushHistory = true) {
    const mainContainer = document.getElementById('spa-main');
    if (!mainContainer) {
        window.location.href = url;
        return;
    }

    try {
        startProgressBar();

        const response = await fetch(url, {
            headers: {
                'X-SPA-Request': 'true'
            }
        });

        // Handle Session Timeout / Redirects
        if (response.redirected) {
            window.location.href = response.url;
            return;
        }

        const html = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        
        const newMain = doc.getElementById('spa-main');
        if (!newMain) {
            // Target page doesn't support SPA, do a hard navigation
            window.location.href = url;
            return;
        }

        // Smart Course Sub-Tab Navigation (prevents banner/header flashing)
        const currentTabContent = document.getElementById('course-tab-content');
        const newTabContent = doc.getElementById('course-tab-content');

        if (currentTabContent && newTabContent) {
            // Update active tab styling immediately
            const targetUrl = new URL(url);
            document.querySelectorAll('.course-nav-link').forEach(link => {
                const linkUrl = new URL(link.href);
                if (linkUrl.pathname === targetUrl.pathname && linkUrl.search === targetUrl.search) {
                    link.classList.add('active');
                } else if (linkUrl.pathname === targetUrl.pathname && !linkUrl.search && !targetUrl.search) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            });

            // Smoothly cross-fade only the inner tab content
            currentTabContent.classList.add('tab-fade-out');
            setTimeout(async () => {
                cleanupEnvironment();
                currentTabContent.innerHTML = newTabContent.innerHTML;
                currentTabContent.classList.remove('tab-fade-out');
                currentTabContent.classList.add('tab-fade-in');

                await executeInlineScripts(currentTabContent);
                completeProgressBar();

                if (pushHistory) {
                    history.pushState(null, doc.title, url);
                }
                document.title = doc.title;
                document.dispatchEvent(new Event('DOMContentLoaded'));
                document.dispatchEvent(new Event('spa:navigated'));

                setTimeout(() => {
                    currentTabContent.classList.remove('tab-fade-in');
                }, 220);
            }, 80);
            return;
        }

        // Global Page Swap (e.g. from Course to Dashboard)
        cleanupEnvironment();
        mainContainer.innerHTML = newMain.innerHTML;

        // Carry over any modals that were placed in doc.body outside #spa-main
        doc.querySelectorAll('body > .modal, body > div.modal').forEach(modal => {
            if (!mainContainer.querySelector('#' + modal.id)) {
                mainContainer.appendChild(modal.cloneNode(true));
            }
        });

        // Carry over any page-specific scripts that were placed outside #spa-main
        doc.querySelectorAll('body script, head script').forEach(script => {
            const src = script.getAttribute('src') || '';
            // Skip core persistent scripts
            if (src.includes('spa-router.js') || src.includes('main.js') || src.includes('bootstrap.bundle.min.js')) {
                return;
            }
            if (newMain.contains(script)) {
                return;
            }
            mainContainer.appendChild(script.cloneNode(true));
        });

        completeProgressBar();

        // Swap Sidebar if present (LMS or Admin)
        const currentSidebar = document.getElementById('lmsSidebar');
        const newSidebar = doc.getElementById('lmsSidebar');
        if (currentSidebar && newSidebar) {
            currentSidebar.innerHTML = newSidebar.innerHTML;
        }

        const currentAdminSidebar = document.getElementById('adminSidebar');
        const newAdminSidebar = doc.getElementById('adminSidebar');
        if (currentAdminSidebar && newAdminSidebar) {
            currentAdminSidebar.innerHTML = newAdminSidebar.innerHTML;
        }
        
        // Update URL
        if (pushHistory) {
            history.pushState(null, doc.title, url);
        }
        document.title = doc.title;

        // Update sidebar active states
        document.querySelectorAll('.lms-nav-link, .admin-sidebar .nav-link').forEach(link => {
            if (link.href) {
                const linkBase = link.href.split('?')[0];
                const currentBase = window.location.href.split('?')[0];
                if (linkBase === currentBase) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            }
        });

        // Force script execution sequentially
        await executeInlineScripts(mainContainer);

        // Announce completion and fire ready events for loaded components
        document.dispatchEvent(new Event('DOMContentLoaded'));
        document.dispatchEvent(new Event('spa:navigated'));
        
        // Smooth scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });

    } catch (error) {
        console.error('SPA Navigation Error:', error);
        window.location.href = url;
    }
}

function cleanupEnvironment() {
    // Destroy DataTables
    if (window.jQuery && $.fn.DataTable) {
        $.fn.DataTable.tables({ api: true }).destroy();
    }
    
    // Clean up Bootstrap Modals
    if (window.jQuery) {
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css('padding-right', '');
    }
}

function executeInlineScripts(container) {
    return new Promise((resolve) => {
        const scripts = Array.from(container.querySelectorAll('script'));
        if (scripts.length === 0) {
            resolve();
            return;
        }
        
        const loadScript = (index) => {
            if (index >= scripts.length) {
                resolve();
                return;
            }
            
            const oldScript = scripts[index];
            const newScript = document.createElement('script');
            
            // Copy attributes
            Array.from(oldScript.attributes).forEach(attr => {
                newScript.setAttribute(attr.name, attr.value);
            });
            
            // Avoid clobbering existing libraries if already loaded in window
            const src = newScript.src || '';
            if (src.includes('jquery.min.js') && window.jQuery) {
                loadScript(index + 1);
                return;
            }
            if (src.includes('bootstrap.bundle.min.js') && window.bootstrap) {
                loadScript(index + 1);
                return;
            }
            
            // Copy content
            if (oldScript.innerHTML) {
                newScript.innerHTML = oldScript.innerHTML;
            }
            
            // If it's an external script, wait for it to load before proceeding
            if (newScript.src) {
                newScript.onload = () => loadScript(index + 1);
                newScript.onerror = () => loadScript(index + 1); // Continue even if one fails
                oldScript.parentNode.replaceChild(newScript, oldScript);
            } else {
                oldScript.parentNode.replaceChild(newScript, oldScript);
                loadScript(index + 1);
            }
        };
        
        loadScript(0);
    });
}
