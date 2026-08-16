# LMS Navigation & Rendering Fixes
**Date**: 2026-08-16
**Status**: Resolved

## Overview
During final review of the TTU LMS subsystem (Phases 5-9 implementations), several UI/UX and routing issues were discovered. These bugs primarily affected the Student and Faculty navigation sidebars, dynamic view rendering, and the SPA (Single Page Application) router behavior.

## Bugs Resolved

### 1. Broken Logo & Relative Asset Paths in Deep Routes
- **Issue**: Navigating to deep nested routes like `/lms/student/course/12/announcements` caused the CSS and Logo images to 404 because their paths in `layout_header.php` were relative (e.g., `../../images/TTU_LOGO.png`).
- **Fix**: Updated `student/layout_header.php` and `faculty/layout_header.php` to use absolute root-relative paths (`/sia/images/TTU_LOGO.png` and `/sia/css/lms.css`).

### 2. Missing Sidebar Overlay Shadows (Hover State)
- **Issue**: The LMS sidebar is designed to auto-collapse to 80px and expand to 260px on hover. However, when it expanded, it overlaid the main content without a drop-shadow, making it look like a broken CSS clipping issue.
- **Fix**: Added a `box-shadow` to `.lms-sidebar` in `lms.css` to properly style it as an intentional floating overlay.

### 3. Blank White Screens on Assessment Routes
- **Issue**: Clicking on Sidebar links for "Announcements", "Assignments", "Quizzes", "Gradebook", or "Calendar" resulted in a completely blank white screen. No fatal errors were logged.
- **Root Cause**: `BaseController::render()` was designed to return the generated HTML as a `string` (via `ob_get_clean()`). However, the newly implemented controller methods omitted the `return` keyword (e.g., calling `$this->render(...)` instead of `return $this->render(...)`). This caused the router to output `null`.
- **Fix**: Ran a bulk script to prepend `return ` to `$this->render(...)` across all Student and Faculty controllers in `app/Controllers/Lms/`.

### 4. SPA Router Breaking the Course Sidebar
- **Issue**: The `spa-router.js` intercepts link clicks and fetches new pages dynamically without reloading the browser. It was configured to only swap the `#spa-main` container. Because the sidebar was outside this container, the sidebar never updated during SPA navigation. This meant navigating from the Global Dashboard into a Course failed to show the Course-specific menu.
- **Fix**: Updated `spa-router.js` to also extract and swap `#lmsSidebar.innerHTML` if it exists on the fetched page. This enables the sidebar to instantly morph into the Course Menu or Global Menu depending on the route, while safely preserving its event listeners.

### 5. 500 Internal Server Errors in Calendar & Assessments
- **Issue A (Calendar)**: A `500 Error` was triggered because the Calendar Controllers used `$request->get('month')`, but the custom `Request` class only implements `$request->input('month')`.
  - **Fix**: Refactored `StudentCalendarController`, `FacultyCalendarController`, and `FacultyController` to correctly use `input()`.
- **Issue B (Quiz Deadlines)**: A `PDOException` was triggered in `LmsCalendarService` because the SQL query attempted to fetch `available_until` from `lms_quizzes`. The correct column name in the database schema is `end_date`.
  - **Fix**: Updated the SQL query in `LmsCalendarService::fetchEvents()` to alias `end_date as available_until`.
- **Issue C (Missing Assignment Index)**: The student sidebar contained links to `/assignments` and `/quizzes`, but the `index` routes and views were never implemented (originally assumed to only live inside "Modules").
  - **Fix**: Registered the index routes in `web.php`, added the `index` methods to `StudentAssignmentController` and `StudentQuizController`, and created cleanly formatted `index.php` view templates for both. Fixed a minor typo calling `LmsAssignmentService` instead of `LmsService`.

## Conclusion
All sidebar navigation routes, dynamic layouts, SPA transitions, and Controller responses in the LMS subsystem are now fully stabilized and functional.
