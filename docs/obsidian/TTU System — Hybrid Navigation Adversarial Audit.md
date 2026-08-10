# TTU System — Hybrid Navigation Adversarial Audit

## 1. VERIFICATION OF MAJOR ASSUMPTIONS

| Assumption | Status | Evidence | Concern | Recommendation |
| ---------- | ------ | -------- | ------- | -------------- |
| Existing MVC architecture is stable | **VERIFIED** | `public/index.php` -> Router -> Controllers is strictly enforced. | None | Safe to build upon. |
| Student LMS can safely use Hybrid Navigation | **VERIFIED** | `app/Views/lms/student/*.php` contain zero inline scripts or complex forms. | None | Ideal first target. |
| Admin can safely use Hybrid Navigation | **INCORRECT** | `app/Views/admin/*/*.php` contain heavy inline jQuery scripts. | Scripts won't execute on DOM replacement. | Exclude Admin completely for now. |
| Full-page responses can be fetched and parsed | **VERIFIED** | Backend consistently wraps content in predictable `<div class="lms-main">` or `<main>`. | Network payload remains large. | Accept as UX optimization, not network optimization. |
| Existing middleware works correctly for fetch requests | **VERIFIED** | `AuthMiddleware` intercepts missing sessions and issues 302 Redirects. | `fetch()` follows redirects transparently. | Detect `response.redirected` instead of parsing HTML. |
| Logout can remain normal navigation | **VERIFIED** | Standard `<a>` tag to `/auth/logout.php`. | Must not be intercepted. | Add `data-spa="false"` to logout links. |
| Rollback strategy actually works | **VERIFIED** | Backend still returns 100% full HTML pages. | None | Disable JS script = instant rollback to MPA. |

---

## 2. CRITICAL CONTRADICTIONS

### Contradiction 1: Admin Portal Viability
- **Claim from Previous Plan**: Admin Portal is HIGHLY RECOMMENDED for Hybrid SPA because it will make dashboard navigation instantaneous.
- **Actual Behavior**: The Admin portal relies extensively on inline `<script>` tags at the bottom of almost every view (e.g., `admissions/review.php`, `registrar/college_sections.php`). These scripts use jQuery to bind directly to DOM elements (e.g., `$('.app-checkbox').on('change')`).
- **Severity**: **CRITICAL**
- **Impact**: If `#spa-main` is dynamically replaced, the browser will NOT execute the incoming inline `<script>` tags. The page will render, but all interactivity (DataTables, Bulk Actions, Filters) will be dead. Attempting to manually extract and `eval()` these scripts will cause duplicate event bindings and memory leaks because event delegation is not used.
- **Required Correction**: **Do NOT implement Hybrid SPA on the Admin portal** under the current codebase.

### Contradiction 2: Session Expiration Detection
- **Claim from Previous Plan**: The JS router should detect session timeouts by checking if the downloaded HTML contains `<title>Login</title>`.
- **Actual Behavior**: `AuthMiddleware.php` handles timeouts via `header('Location: /sia/auth/login.php')` (302 Redirect). The `fetch()` API transparently follows redirects.
- **Severity**: **HIGH**
- **Impact**: Parsing HTML to detect auth failure is brittle and slow.
- **Required Correction**: Check the `Response` object natively:
  ```javascript
  if (response.redirected && response.url.includes('/login.php')) {
      window.location.href = response.url; // Force hard redirect
      return;
  }
  ```

---

## 3. AUDIT OF THE `#spa-main` STRATEGY

| Portal | Existing Content Container | Compatible? | Problem |
| ------ | -------------------------- | ----------- | ------- |
| **Student LMS** | `<div class="lms-main">` (opened in layout_header, closed in layout_footer) | **YES** | Content is cleanly injected here. |
| **Admin** | `<main class="py-5 bg-light min-vh-100">` (mixed layout) | **NO** | Layout tags are inconsistently closed between views and footers. |
| **Applicant** | Generic `.container` | **NO** | Multi-step forms, highly linear. |

**Conclusion**: The `#spa-main` strategy can ONLY be safely standardized inside the **Student LMS**. Modifying the Admin layouts right now would require significant HTML refactoring.

---

## 4. JAVASCRIPT LIFECYCLE RISK MATRIX

What happens when `#spa-main` is replaced dynamically?

| Script Location | Behavior on Initial Load | Behavior after SPA Navigation | Risk |
| --------------- | ------------------------ | ----------------------------- | ---- |
| `layout_footer.php` (LMS Sidebar logic) | Attaches to DOM nodes (`.lms-main`). | Survives beautifully, because `.lms-main` itself is not destroyed, only its interior HTML. | **SAFE** |
| `main.js` (Global Form Spinner) | Attaches to all `<form>` tags. | **DEAD**. Forms injected via SPA will not have the listener attached. | **HIGH** |
| `review.php` (Admin Inline Scripts) | Attaches to DataTables, checkboxes. | **DEAD**. Browser completely ignores inline scripts injected via `innerHTML`. | **CRITICAL** |

---

## 5. FORMS AND FILE DOWNLOADS

- **LMS Forms**: Verified that `app/Views/lms/student/*.php` currently contains exactly 0 `<form>` elements. This makes the LMS incredibly safe for Phase 1.
- **LMS Downloads**: Course materials often link to PDFs.
- **Requirement**: The SPA router MUST contain an exclusion array.
  ```javascript
  if (href.match(/\.(pdf|zip|doc|docx|csv|xlsx)$/i) || link.hasAttribute('download')) {
      return; // Do not intercept
  }
  ```

---

## 6. AUDIT PERFORMANCE CLAIMS

- **Speculative Claim**: "Speeds up navigation drastically."
- **Audit Reality**: Because the PHP backend still renders the entire layout, and `fetch()` downloads the *full* HTML document, **the network payload size is identical to an MPA**. 
- **Actual Benefit**: The performance gain is purely perceptual and client-side computational. The browser avoids destroying the DOM tree, avoids reloading `bootstrap.css`, and avoids repainting the sidebar. This feels significantly faster, but database query times remain exactly the same.

---

## 7. LMS-SPECIFIC RISKS

| LMS Feature | SPA Safety | Reason |
| ----------- | ---------- | ------ |
| Dashboard | 🟢 SAFE | Static content, no forms. |
| My Courses | 🟢 SAFE | Static list of courses. |
| Course View | 🟢 SAFE | Static syllabus and resources. |
| **Quizzes** | 🔴 NOT SAFE | *Future Risk*: When implemented, navigating away from a quiz via SPA might bypass `onbeforeunload` warnings, causing students to lose timed answers. |

---

## 8. IMPLEMENTATION BLOCKERS

| Blocker | Severity | Evidence | Resolution |
| ------- | -------- | -------- | ---------- |
| **Admin Inline Scripts** | **CRITICAL** | `app/Views/admin/admissions/review.php` uses `$(function(){...})` inside the view. | Do NOT implement SPA on Admin. Restrict scope exclusively to Student LMS. |
| **Missing Global Delegation** | **HIGH** | `js/main.js` binds to `document.querySelectorAll('form')` instead of `document.addEventListener('submit')`. | Update `js/main.js` to use event delegation for form spinners, OR ensure LMS doesn't use forms yet. |

---

## 9. SAFE TO IMPLEMENT CHECKLIST

- [x] Architecture verified
- [x] Portal shell verified (LMS Only)
- [x] Session expiry behavior verified (Use `response.redirected`)
- [x] Browser history verified
- [x] JavaScript lifecycle understood (Inline scripts blocked)
- [x] Rollback verified (100% graceful degradation)
- [x] Performance claims corrected (UX optimization, not network)

---

## 10. FINAL VERDICT

### 🟡 APPROVED WITH REQUIRED CHANGES

The proposed Hybrid SPA Navigation architecture is fundamentally brilliant in its simplicity (Client-Side HTML Parsing prevents backend rewrites), but the previous document's scope was dangerously broad.

### Required Changes Before Implementation:
1. **Scope Reduction**: The Admin Portal is explicitly **REJECTED** from this implementation due to heavy reliance on inline, non-delegated jQuery scripts.
2. **Session Handling**: Must use `response.redirected` instead of parsing `<title>Login</title>`.
3. **Form Spinner Fix**: The global form spinner in `js/main.js` must be rewritten to use event delegation, otherwise dynamically loaded forms will not trigger the spinner.

### First Safe Proof of Concept:
**The Student LMS (`/lms/student/`)**. It has clean HTML separation (`.lms-main`), no inline scripts, and no complex forms, making it the perfect, 100% safe sandbox for the `spa-router.js` implementation.

### Implementation Boundary
The audit is complete. Implementation of the `spa-router.js` for the **Student LMS ONLY** may now begin.
