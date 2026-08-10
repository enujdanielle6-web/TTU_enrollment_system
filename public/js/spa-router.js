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

async function navigateTo(url, pushHistory = true) {
    const mainContainer = document.getElementById('spa-main');
    if (!mainContainer) {
        window.location.href = url;
        return;
    }

    try {
        // Show Loading State
        mainContainer.style.opacity = '0.4';
        mainContainer.style.pointerEvents = 'none';

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

        // Cleanup before DOM replacement
        cleanupEnvironment();

        // Swap Content
        mainContainer.innerHTML = newMain.innerHTML;
        mainContainer.style.opacity = '1';
        mainContainer.style.pointerEvents = 'auto';
        
        // Update URL
        if (pushHistory) {
            history.pushState(null, doc.title, url);
        }
        document.title = doc.title;

        // Update sidebar active states
        document.querySelectorAll('.nav-link').forEach(link => {
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
        executeInlineScripts(mainContainer);

        // Announce completion
        document.dispatchEvent(new Event('spa:navigated'));
        
        // Scroll to top
        window.scrollTo(0, 0);

    } catch (error) {
        console.error('SPA Navigation Error:', error);
        window.location.href = url; // Fallback to normal navigation
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
    const scripts = Array.from(container.querySelectorAll('script'));
    
    const loadScript = (index) => {
        if (index >= scripts.length) return;
        
        const oldScript = scripts[index];
        const newScript = document.createElement('script');
        
        // Copy attributes
        Array.from(oldScript.attributes).forEach(attr => {
            newScript.setAttribute(attr.name, attr.value);
        });
        
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
}
