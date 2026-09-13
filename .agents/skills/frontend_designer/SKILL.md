---
name: Frontend UI/UX Designer
description: Focuses on building premium, high-quality, modern, responsive, and animated UIs using Bootstrap 5, CSS, and SPA-safe JavaScript.
---

# Frontend UI/UX Designer

**Purpose**: Design and assemble state-of-the-art, visually stunning, accessible, and responsive user interfaces for the TTU Enrollment System across the Public Landing Page, Applicant Portal, and Administrative Portals.

---

## 1. Design Aesthetics & Visual Excellence Standards

1. **Premium Visual Styling**:
   - Deliver modern, polished aesthetics utilizing Bootstrap 5, curated custom CSS, subtle gradients, glassmorphism cards, and Bootstrap Icons (`bi-*`).
   - Use curated typography (Google Fonts: Inter, Outfit, or Roboto) with proper hierarchy and line heights.
   - Avoid generic, plain colors; utilize deep navy, slate, crisp white, and contextual accent hues.
2. **Dynamic Interactions & Micro-Animations**:
   - Provide interactive states with smooth hover transitions (`transition: all 0.2s ease-in-out`), subtle scale effects on cards, and animated badge indicators.
   - Use skeleton loaders and interactive spinners during AJAX fetch requests.
3. **Action Debouncing & Submission States**:
   - High-impact modal submit buttons (e.g. Cashier payment verification, Admissions document approvals, Registrar enrollment finalization) MUST implement instant debouncing:
     - Disable the submit button immediately upon click.
     - Display a loading spinner icon (`<span class="spinner-border spinner-border-sm"></span> Processing...`).
     - Prevents duplicate POST submissions and ledger race conditions.
4. **No Empty Placeholders**:
   - Never render blank states or "lorem ipsum". Use empty state illustrations, helpful guidance tips, or realistic placeholder data.

---

## 2. Hybrid SPA Navigation Architecture (`spa-router.js`)

As documented in [[ADR-003 Hybrid SPA Navigation Design]] and [[ADR-004 Hybrid Navigation Adversarial Audit]]:
1. **Dynamic Fragment Swapping**:
   - Internal navigation links dynamically fetch and inject HTML into the primary `#main-content` container without full browser reload.
2. **Script Idempotency & Scope Isolation**:
   - Scripts included in views must encapsulate logic in Immediately Invoked Function Expressions (IIFEs) or attach cleanly to `document.addEventListener('DOMContentLoaded', ...)` AND `document.addEventListener('spa:navigated', ...)`.
   - Prevent re-declaring `const` or `let` variables in the global window scope to avoid duplicate declaration syntax errors.
3. **Opting Out of SPA Navigation**:
   - For heavy canvas tools, visual schedule builders, or complex form workflows that require a full browser state cycle, add `data-spa="false"` to the link.

---

## 3. Key Documentation References
- Views Catalog: [[05 - Views Catalog & Template Mapping]]
- SPA Architecture: [[ADR-003 Hybrid SPA Navigation Design]]
- Public Landing Customization: [[Landing Page & Program Card Customization]]
- Coding Standards: [[Coding Standards]]
