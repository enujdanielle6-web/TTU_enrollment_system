# TTU System — Hybrid SPA Navigation Architecture & Implementation Plan

## 1. CURRENT ARCHITECTURE VERIFICATION

Based on a thorough inspection of the existing Vanilla PHP MVC architecture:
- **Routing**: `app/Routes/web.php` maps URIs to Controllers.
- **Controllers**: Extend `BaseController` which uses a simple `extract($params)` and `include_once` for views.
- **Views**: Directly include their own layouts via `require_once __DIR__ . '/../../components/header.php'` (or similar layout files) at the top and bottom of the file.
- **Business Logic Leakage**: Some views (e.g., `sysadmin_dashboard.php`) contain raw `$pdo->query(...)` logic, which must be respected.
- **HTML Structure**: Layout files open wrapping `<div>`s (like `.admin-main`), but views are expected to close them, creating tightly coupled HTML boundaries.

---

## 2. EXACT TARGET BEHAVIOR

The objective is to achieve instantaneous, flicker-free navigation across authenticated portals. The portal shell (Sidebar, Top Navbar) will remain completely persistent. Only the primary content container (e.g., `<div class="lms-main">` or `<main>`) will change.

**Request Flow**:
```text
User clicks <a>
   ↓
JS intercepts (e.preventDefault)
   ↓
fetch(URL)
   ↓
PHP Backend processes request exactly as normal (Middleware -> Controller -> View)
   ↓
Backend returns Full HTML string
   ↓
JS DOMParser extracts the `<main>` fragment
   ↓
Replaces current `#main-content`
   ↓
history.pushState() updates URL
```

---

## 3. FULL SPA VS HYBRID SPA

This is **NOT** a traditional SPA (Single Page Application). 
- In React/Vue, the backend is a REST API returning JSON, and the client renders HTML.
- In our **Hybrid SPA**, the backend is blissfully unaware. It continues returning fully-rendered HTML just as it always has. The client simply throws away the duplicate headers/sidebars and keeps the fresh content block.

---

## 4. FULL-PAGE VS PARTIAL RESPONSES (ANALYSIS)

How should the server respond?

- **Option A (HTTP Header Check)**: Controller checks `is_ajax()` and skips `require_once 'header.php'`. 
  - *Risk*: EXTREME. Because `require_once` is hardcoded inside over 100 view files (not the controller), we would have to modify every single view file to wrap the inclusion in an `if` statement.
- **Option B (`?partial=1`)**: Same massive refactoring risk as Option A.
- **Option C (Client-Side Parsing)**: **RECOMMENDED**. The server continues returning the 100% full HTML page. The JavaScript `fetch()` receives this string, uses `DOMParser` to extract the `<main>` tag, and swaps it in. 
  - *Why*: It requires **ZERO changes to the PHP backend**. It is infinitely safer and guarantees backward compatibility.

---

## 5. BaseController::render() ANALYSIS

Modifying `BaseController::render()` to intercept and strip out headers is impossible without using `ob_get_clean()` and complex regex to strip the HTML string before returning it. 
Therefore, **Do not modify `BaseController::render()`**. Rely entirely on Client-Side Parsing (Option C).

---

## 6. DESIGN THE PORTAL SHELL

### Admin Portal
- **Current Layout**: `components/admin_navbar.php` and `components/footer.php`
- **Current Content Container**: `<main class="py-5 bg-light min-vh-100">`
- **Potential Shell ID**: Add `id="spa-main"` to `<main>`.

### Student LMS
- **Current Layout**: `lms/student/layout_header.php`
- **Current Content Container**: `<div class="lms-main">`
- **Potential Shell ID**: Add `id="spa-main"` to `.lms-main`.

---

## 7. NAVIGATION MANAGER DESIGN (JavaScript)

The Navigation Manager will be a centralized script (`public/js/spa-router.js`):
1. **Intercept**: `document.addEventListener('click', ...)` on any `<a>` tag.
2. **Filter**: Ignore if `target="_blank"`, `download`, external domain, or `href="#"`.
3. **Fetch**: Call `fetch(href)`. Show a CSS spinner.
4. **Parse**: `new DOMParser().parseFromString(html, 'text/html')`.
5. **Swap**: `document.getElementById('spa-main').innerHTML = doc.getElementById('spa-main').innerHTML`.
6. **URL**: `history.pushState(null, doc.title, href)`.

---

## 8. BROWSER HISTORY

To support the back/forward buttons seamlessly:
```javascript
window.addEventListener('popstate', async (e) => {
    // URL has already changed, just fetch and swap
    const html = await fetchPage(location.href);
    swapContent(html);
});
```

---

## 9. DIRECT URL / REFRESH BEHAVIOR

Because we selected **Client-Side Parsing** (Option C), the backend always serves a full page. If a user hits `Refresh` (F5) or shares a link, the browser sends a normal GET request, and the server returns the fully formed page with sidebars and headers. **Graceful degradation is guaranteed by design.**

---

## 10. JAVASCRIPT INITIALIZATION PROBLEM

**The Threat**: When `innerHTML` is replaced, scripts bound to `DOMContentLoaded` do not fire again. DataTables, SweetAlerts, and Chart.js will break.
**The Solution**: 
Create a global initialization function `window.initTTU()` in `main.js`.
```javascript
function initTTU() {
    // Init tooltips
    // Init DataTables (if table exists)
}
// Run on first load
document.addEventListener('DOMContentLoaded', initTTU);
// Run after every SPA swap
document.addEventListener('spa:navigated', initTTU);
```

---

## 11. EXISTING AJAX / JQUERY ANALYSIS

Admin dashboards use some jQuery. 
- **Conflict**: If a jQuery DataTable exists in the DOM, and we overwrite `innerHTML`, memory leaks occur.
- **Solution**: Before swapping `innerHTML`, we must call `$('.dataTable').DataTable().destroy()` if it exists.

---

## 12. AUTHENTICATION & SECURITY

- **Middleware**: Remains 100% active.
- **Session Expiry Issue**: If the session expires, the server will issue a `302 Redirect` to `/auth/login.php`. `fetch()` automatically follows this and downloads the HTML of the login page.
- **Detection**: Before swapping, the SPA script must check if the downloaded HTML contains `<title>Login</title>` or a specific login form ID. If it does, the SPA script must abort the swap and execute `window.location.href = href;` to force a hard reload into the login screen.

---

## 13. ERROR HANDLING

- **Network Error**: Show `SweetAlert2` modal: "Connection lost. Retrying..."
- **500 Server Error**: The server will return the error page HTML. Swap it into `#spa-main`.
- **403/404**: Swap into `#spa-main` normally.

---

## 14. LOADING EXPERIENCE

To make it feel premium:
1. Apply `opacity: 0.5` to `#spa-main` during `fetch()`.
2. Add a fixed Bootstrap `<div class="spinner-border">` overlay.
3. Upon success, swap content, remove spinner, and fade opacity back to 1.

---

## 15. FORM SUBMISSION

**Rule**: DO NOT intercept `<form>` submissions initially. 
Forms involve complex CSRF tokens, file uploads, and specific POST behaviors. Let forms execute full page reloads to ensure stability. Only intercept standard `GET` anchor tags.

---

## 16. FILE DOWNLOADS

Exclude intercepts for:
```javascript
if (href.endsWith('.pdf') || href.endsWith('.zip') || link.hasAttribute('download')) {
    return; // Let browser handle normally
}
```

---

## 17. MODALS & COMPONENTS

Before swapping content, the SPA script MUST destroy active modals to prevent grey backdrop locks:
```javascript
$('.modal-backdrop').remove();
$('body').removeClass('modal-open').css('padding-right', '');
```

---

## 18. DATA TABLES

As analyzed, DataTables must be destroyed before the DOM wipe.
```javascript
if ($.fn.DataTable) {
    $('.dataTable').DataTable().destroy();
}
```

---

## 19. LMS-SPECIFIC CONCERNS

| LMS Page       | Hybrid Navigation? | Special Handling |
| -------------- | ------------------ | ---------------- |
| Dashboard      | Yes                | Re-init Charts   |
| Courses        | Yes                | None             |
| Assignments    | Yes                | None             |
| Quizzes        | **NO**             | Add `data-spa="false"` to quiz links. Navigating away during a timed quiz via SPA could bypass warnings or break timers. |

---

## 20. APPLICANT PORTAL ANALYSIS

**Recommendation**: NOT RECOMMENDED.
The Applicant portal consists of heavily serialized, multi-step forms and document uploads. It is a linear process, not a sprawling dashboard. The risk outweighs the reward. Keep it as an MPA.

---

## 21. ADMIN PORTAL ANALYSIS

**Recommendation**: HIGHLY RECOMMENDED.
The Admin portal is a classic dashboard. SPA navigation will make navigating between `Registrar`, `Finance`, and `Users` feel instantaneous and premium.

---

## 22. PERFORMANCE ANALYSIS

- **Backend Performance**: Unchanged. Queries still execute.
- **Frontend Performance**: MASSIVE IMPROVEMENT. The browser no longer re-evaluates `bootstrap.min.css`, re-downloads images in the sidebar, or re-parses the DOM tree for the entire shell. Perceived speed will drop from ~800ms per click to ~150ms.

---

## 23. CACHING ANALYSIS

Not required. Let the browser handle standard HTTP caching for HTML fragments.

---

## 24. ACCESSIBILITY

After a successful swap, the SPA manager must reset focus:
```javascript
document.getElementById('spa-main').focus();
window.scrollTo(0, 0);
```

---

## 25. SEO

Not applicable. All target areas (Admin, LMS) are behind Authentication Middleware. Search engines cannot index them anyway.

---

## 26. IMPLEMENTATION OPTIONS

1. **Global AJAX (Backend Modified)** - Risk: High.
2. **Pjax/Turbolinks (Client-Side HTML Parse)** - Risk: Low. Compatible with Vanilla PHP.
3. **Iframe Shell** - Risk: Unacceptable (Breaks URLs).

**Recommendation**: Option 2 (Client-Side HTML parsing).

---

## 27. RECOMMENDED ARCHITECTURE

```text
                    Browser
                       │
             Persistent Portal Shell
          (Header, Sidebar, Vendor CSS/JS)
                       │
                ▼──────────────▼
            User Clicks Internal Link
                ▼──────────────▼
            spa-router.js Intercepts
                       │
              fetch(Full Page URL)
                       │
             PHP Backend (web.php)
                       │
               Returns Full HTML
                       │
          DOMParser extracts #spa-main
                       │
         Replaces current #spa-main HTML
                       │
              Re-initialize Plugins
```

---

## 28. EXACT FILES EXPECTED TO CHANGE

| File | Expected Change | Why | Risk |
| ---- | --------------- | --- | ---- |
| `public/js/spa-router.js` | **NEW FILE** | Contains the core navigation logic | Low |
| `app/Views/components/footer.php` | Add `<script src="spa-router.js">` | Include in Admin | Low |
| `app/Views/lms/student/layout_footer.php` | Add `<script src="spa-router.js">` | Include in LMS | Low |
| `app/Views/admin/system/sysadmin_dashboard.php` | Add `id="spa-main"` to `<main>` | Target container | Low |
| All other Admin/LMS views | Add `id="spa-main"` to `<main>` | Target container | Medium |

---

## 29. IMPLEMENTATION ORDER

1. Write `public/js/spa-router.js`.
2. Add `<main id="spa-main">` to LMS Student `dashboard.php` and `my_courses.php`.
3. Include `spa-router.js` in `lms/student/layout_footer.php`.
4. Test LMS navigation.
5. Apply to Admin portal.

---

## 30. TESTING PLAN

- **Authentication Bounce**: Click a link after the session expires. Expectation: SPA aborts and hard-redirects to Login.
- **History**: Navigate 3 pages deep, click browser Back button. Expectation: Content swaps backward correctly without full reload.
- **Modals**: Open an applicant detail modal, close it, navigate to another page. Expectation: Backdrop is cleanly removed.
- **Logout**: Click logout. Expectation: Session destroyed, hard redirect to login.

---

## 31. ROLLBACK STRATEGY

If the JavaScript fails catastrophically in production, simply remove `<script src="/sia/public/js/spa-router.js"></script>` from the footer. The entire system will instantly revert to a perfectly functioning Multi-Page Application. Graceful degradation is 100% native.

---

## 32. AI IMPLEMENTATION GUARDRAILS

1. **Never modify `BaseController.php` or `web.php`.**
2. **Never modify database schema.**
3. **Never attempt to make forms asynchronous** during the initial rollout.
4. **Never intercept `logout.php`.** Add `data-spa="false"` to the logout button.
5. **Never introduce a new JS framework.** Use Vanilla JS `fetch()`.
6. **Always destroy modals/tables** before replacing the DOM.
7. **Make the smallest safe change necessary.**

---

## 33. FINAL DECISION

### Recommended Approach
**Client-Side HTML Parsing (Pjax-style Hybrid Navigation)** using Vanilla JS `fetch()`.

### Why
It requires absolutely zero changes to the underlying PHP MVC architecture, backend routes, or security middleware. It is the lowest-risk approach with the highest UX payoff.

### Scope
Limit entirely to the **Student LMS** and **Admin Portal**. Exclude Applicant Portal.

### First Implementation Target
**The Student LMS**. It currently only has a few views (`dashboard.php`, `course.php`, `my_courses.php`), making it an incredibly safe proof-of-concept environment before rolling it out to the sprawling Admin portal.
